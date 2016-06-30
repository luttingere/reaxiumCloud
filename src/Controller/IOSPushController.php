<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 01/02/2016
 * Time: 03:34 PM
 */
/**
 * @Autor Eduardo Luttinger
 */
namespace App\Controller;

use Cake\Core\Exception\Exception;
use Cake\Log\Log;

define("IOS_APNS_SANDBOX", "ssl://gateway.sandbox.push.apple.com:2195");
define("IOS_APNS", "ssl://gateway.push.apple.com:2195");
define("IOS_SERVICE_CERTIFICATE", "/var/www/html/reaxium/reaxiumCert.pem");
define("IOS_CERTIFICATE_PASS_PHRASE", "reaxium*t4ss/");

class IOSPushController extends AppController
{

    /**
     * Array of data for APNS connections (Development Environment)
     * @var array
     */
    private static $apnsDataSandbox = array('certificate' => IOS_SERVICE_CERTIFICATE, 'ssl' => IOS_APNS_SANDBOX);

    /**
     * Array of data for APNS connections (Production Environment)
     * @var array
     */
    private static $apnsDataProduction = array('certificate' => IOS_SERVICE_CERTIFICATE, 'ssl' => IOS_APNS);

    /**
     * Device Address and Message to be sent using Push
     * @var
     */
    private $data = array('message' => '', 'deviceId' => '');

    /**
     * Device Address and Message to be sent using Push
     * @var array
     */
    private static $pushData = array('message' => '', 'deviceId' => '');



    private static function loadParameters($message, $deviceId)
    {
        Log::info("Cargando los parametros de Envio");
        IOSPushController::$pushData['deviceId'] = $deviceId;
        IOSPushController::$pushData['message'] = load;
    }

    public static function sendPush($message, $deviceId)
    {
        Log::info("Enviando notificacion Push");
        IOSPushController::loadParameters($message, $deviceId);
        Log::info("DeviceId: " . IOSPushController::$pushData['deviceId']);
        Log::info("Message: " . IOSPushController::$pushData['message']);
        IOSPushController::sendIOSNotification();
    }


    private static function getIOSMessage($message)
    {
        $body['aps'] = array(
            'alert' => $message['traffic_info'],
            'sound' => 'default',
            'custom' => $message
        );
        return json_encode($body);
    }

    /**
     *
     */
    private static function sendIOSNotification()
    {
        $apnsConnection = stream_context_create();
        stream_context_set_option($apnsConnection, 'ssl', 'local_cert', IOSPushController::$apnsDataSandbox['certificate']);
        stream_context_set_option($apnsConnection, 'ssl', 'passphrase', IOS_CERTIFICATE_PASS_PHRASE);
        $apnSocketClient = stream_socket_client(IOSPushController::$apnsDataSandbox['ssl'], $error, $errorString, 60, STREAM_CLIENT_CONNECT, $apnsConnection);
        if ($apnSocketClient) {
            $pushMessage = chr(0) . pack('n', 32) . pack('H*', IOSPushController::$pushData['deviceId']) . pack('n', strlen(IOSPushController::$pushData['message'])) . IOSPushController::$pushData['message'];
            $result = fwrite($apnSocketClient, $pushMessage, strlen($pushMessage));
            if ($result) {
                Log::info("Notificacion push enviada con exito Error Code:  " . $error . "  Error Message: " . $errorString . " " . PHP_EOL);
            } else {
                Log::error("ERROR enviando la notificacion push: " . $error . " " . $errorString . " " . PHP_EOL);
            }
            fclose($apnSocketClient);
        } else {
            Log::info("No se pudo realizar la conexion contra el servidor APNS " . $error . " " . $errorString . " " . PHP_EOL);
        }
    }

    /**
     * @param $arrayOfMessages
     */
    public static function bulkSendIOSNotification($arrayOfMessages)
    {
        try{
            if(sizeof($arrayOfMessages) > 0){
                $apnsConnection = stream_context_create();
                stream_context_set_option($apnsConnection, 'ssl', 'local_cert', IOSPushController::$apnsDataSandbox['certificate']);
                stream_context_set_option($apnsConnection, 'ssl', 'passphrase', IOS_CERTIFICATE_PASS_PHRASE);
                $apnSocketClient = stream_socket_client(IOSPushController::$apnsDataSandbox['ssl'], $error, $errorString, 60, STREAM_CLIENT_CONNECT, $apnsConnection);
                if ($apnSocketClient) {
                    foreach ($arrayOfMessages as $message) {
                        Log::info(self::getIOSMessage($message['message']));
                        $pushMessage = chr(0) . pack('n', 32) . pack('H*', $message['deviceId']) . pack('n', strlen(self::getIOSMessage($message['message']))) .self::getIOSMessage($message['message']);
                        $result = fwrite($apnSocketClient, $pushMessage, strlen($pushMessage));
                        if ($result) {
                            Log::info("Notificacion push enviada con exito Error Code:  " . $error . "  Error Message: " . $errorString . " " . PHP_EOL);
                        } else {
                            Log::info("ERROR enviando la notificacion push: " . $error . " " . $errorString . " " . PHP_EOL);
                        }
                    }
                    fclose($apnSocketClient);
                } else {
                    Log::info("No se pudo realizar la conexion contra el servidor APNS " . $error . " " . $errorString . " " . PHP_EOL);
                }
            }
        }catch (\Exception $e){
            Log::info("error enviando notificacion push: ".$e->getMessage());
        }

    }


}