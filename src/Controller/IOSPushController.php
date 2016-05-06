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

use Cake\Log\Log;

define("IOS_APNS_SANDBOX", "ssl://gateway.sandbox.push.apple.com:2195");
define("IOS_APNS", "ssl://gateway.push.apple.com:2195");
define("IOS_SERVICE_CERTIFICATE", "/var/www/html/app/ck.pem");
define("IOS_CERTIFICATE_PASS_PHRASE", "teravision");

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


    /**
     *
     */
    private function sendPushNotification()
    {
        $apnsConnection = stream_context_create();
        stream_context_set_option($apnsConnection, 'ssl', 'local_cert', IOSPushController::$apnsDataSandbox['certificate']);
        stream_context_set_option($apnsConnection, 'ssl', 'passphrase', IOS_CERTIFICATE_PASS_PHRASE);
        $apnSocketClient = stream_socket_client(IOSPushController::$apnsDataSandbox['ssl'], $error, $errorString, 60, STREAM_CLIENT_CONNECT, $apnsConnection);
        if ($apnSocketClient) {

            $payload = $_POST['message'];
            $deviceId = $_POST['deviceId'];
            Log::info($payload);
            Log::info("DeviceId: " . $deviceId);

            $pushMessage = chr(0) . pack('n', 32) . pack('H*', $deviceId) . pack('n', strlen($payload)) . $payload;

            $result = fwrite($apnSocketClient, $pushMessage, strlen($pushMessage));

            if ($result) {
                Log::info("Notificacion push enviada con exito Error Code:  " . $error . "  Error Message: " . $errorString . " " . PHP_EOL);
            } else {
                Log::error("ERROR enviando la notificacion push: " . $error . " " . $errorString . " " . PHP_EOL);
            }
            Log::info("Cerrada la conexion contra el servidor APNS");
            fclose($apnSocketClient);
        } else {
            Log::info("No se pudo realizar la conexion contra el servidor APNS " . $error . " " . $errorString . " " . PHP_EOL);
        }
    }

    /**
     *
     */
    private function setTestValues()
    {
        $this->data['deviceId'] = $_POST['deviceId'];
        $this->data['message'] = $_POST['message'];
    }


    /**
     *
     */
    public function send()
    {
        Log::info("Enviando notificacion Push");
        $this->setTestValues();
        try {
            $this->sendPushNotification();
            $this->set("result", "Push notification sent successfully");
        } catch (Exception $e) {
            Log::error($e->getMessage());
            $this->set("result", "Error sending the push notification");
        }
        Log::info("Push message sent successfull");
    }


    private static function loadParameters($message, $deviceId)
    {
        Log::info("Cargando los parametros de Envio");
        IOSPushController::$pushData['deviceId'] = $deviceId;
        IOSPushController::$pushData['message'] = $message;
    }

    public static function sendPush($message, $deviceId)
    {
        Log::info("Enviando notificacion Push");
        IOSPushController::loadParameters($message, $deviceId);
        Log::info("DeviceId: ".IOSPushController::$pushData['deviceId']);
        Log::info("Message: ".IOSPushController::$pushData['message']);
        IOSPushController::sendIOSNotification();
    }

    public static function sendBulkPush($arrayOfMessages)
    {
        Log::info("Enviando notificacion Push Masiva");
        foreach($arrayOfMessages as $pushContent){
            IOSPushController::sendPush($pushContent["message"], $pushContent["deviceId"]);
        }
        //IOSPushController::bulkSendIOSNotification($arrayOfMessages);
    }

    public static function getBasicIOSMessage($action){
        $body['aps'] = array(
            'alert'=>'Your ride has started',
            'sound'=>'default',
            'custom'=>array('view' => $action)
        );
        return json_encode($body);
    }

    public static function getIOSMessage($params){
        $message = null;
        if (isset($params['messageIOS'])){
            $message = $params['messageIOS'];
        }else{
            $message = $params['message'];
        }
        $body['aps'] = array(
            'alert'=>$message,
            'sound'=>'default',
            'custom'=>$params
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
    private static function bulkSendIOSNotification($arrayOfMessages)
    {
        $apnsConnection = stream_context_create();
        stream_context_set_option($apnsConnection, 'ssl', 'local_cert', IOSPushController::$apnsDataSandbox['certificate']);
        stream_context_set_option($apnsConnection, 'ssl', 'passphrase', IOS_CERTIFICATE_PASS_PHRASE);
        $apnSocketClient = stream_socket_client(IOSPushController::$apnsDataSandbox['ssl'], $error, $errorString, 60, STREAM_CLIENT_CONNECT, $apnsConnection);
        if ($apnSocketClient) {
            foreach($arrayOfMessages as $message){
                $pushMessage = chr(0) . pack('n', 32) . pack('H*', $message['deviceId']) . pack('n', strlen($message['message'])) . $message['message'];
                $result = fwrite($apnSocketClient, $pushMessage, strlen($pushMessage));
                if ($result) {
                    Log::info("Notificacion push enviada con exito Error Code:  " . $error . "  Error Message: " . $errorString . " " . PHP_EOL);
                } else {
                    Log::error("ERROR enviando la notificacion push: " . $error . " " . $errorString . " " . PHP_EOL);
                }
            }
            fclose($apnSocketClient);
        } else {
            Log::info("No se pudo realizar la conexion contra el servidor APNS " . $error . " " . $errorString . " " . PHP_EOL);
        }
    }


}