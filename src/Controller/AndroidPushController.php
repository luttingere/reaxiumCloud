<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 28/01/2016
 * Time: 11:39 AM
 */

namespace App\Controller;


use Cake\Core\Exception\Exception;
use Cake\Log\Log;

/**
 * Google Api Key for app Mellevas (Server Mode)
 * @var string
 */
define("SERVER_KEY_API_ACCESS_KEY", "AIzaSyDP5BeGvYdsLyZyL8VNiGz1RgccNB5de0A");

/**
 * Google Api Key for app Mellevas (Navigator Mode)
 * @var string
 */
define("NAVIGATOR_KEY_API_ACCESS_KEY ", "AIzaSyBR-ll-6g8g0Aju1wBPErRBmddFwl_dfvU");

/**
 * Author Eduardo Luttinger
 * Class PushNotification
 * @package App\Controller
 */
class AndroidPushController extends AppController {

    /**
     * Header to be send to the server
     * @var array Header to be send to the server
     */
    public static $httpAndroidPushHeader = array('Authorization: key='. SERVER_KEY_API_ACCESS_KEY, 'Content-Type: application/json');
    /**
     *  Url of Google Push notification platform
     * @var string Url of Google Push notification platform
     */
    public static $googleCloudMessaginServerUrl = 'https://android.googleapis.com/gcm/send';



    public function test(){
        Log::info("Si funciona esta mierda");
    }


    public function send(){
        Log::info("Enviando notificacion Push");
        try{
            $this->sendAndroidPushNotification();
            $this->set("result","Push notification sent successfully");
        }catch(Exception $e){
            Log::error($e->getMessage());
            $this->set("result","Error sending the push notification");
        }
        Log::info("Push message sent successfull");
    }

    /**
     * load a simple json message to be sent using push
     * @param $type
     * @param $message
     * @return string
     */
    private static function loadSimpleMessage($params){
        return json_encode($params);
    }


    /**
     * send a push notification to the device id received as a paremeter
     */
    public static function sendPush($deviceId,$params){
        $httpCall = curl_init();
        curl_setopt( $httpCall,CURLOPT_URL, AndroidPushController::$googleCloudMessaginServerUrl);
        curl_setopt( $httpCall,CURLOPT_POST, true );
        curl_setopt( $httpCall,CURLOPT_HTTPHEADER, AndroidPushController::$httpAndroidPushHeader);
        curl_setopt( $httpCall,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $httpCall,CURLOPT_SSL_VERIFYPEER, false );
        $dataToSend = AndroidPushController::getPushData($deviceId,AndroidPushController::loadSimpleMessage($params));
        Log::info($dataToSend);
        curl_setopt( $httpCall,CURLOPT_POSTFIELDS, $dataToSend);
        $result = curl_exec($httpCall);
        Log::info($result);
        curl_close( $httpCall );
    }

    /**
     * send a push notification to the device id received as a paremeter
     * @param $arrayOfAndroidMessages
     */
    public static function sendBulkPush($arrayOfAndroidMessages){
        if($arrayOfAndroidMessages != null){
            if(sizeof($arrayOfAndroidMessages) > 0){
                $httpCall = curl_init();
                curl_setopt( $httpCall,CURLOPT_URL, AndroidPushController::$googleCloudMessaginServerUrl);
                curl_setopt( $httpCall,CURLOPT_POST, true );
                curl_setopt( $httpCall,CURLOPT_HTTPHEADER, AndroidPushController::$httpAndroidPushHeader);
                curl_setopt( $httpCall,CURLOPT_RETURNTRANSFER, true );
                curl_setopt( $httpCall,CURLOPT_SSL_VERIFYPEER, false );
                foreach($arrayOfAndroidMessages as $messages){
                    $dataToSend = AndroidPushController::getPushData($messages['deviceId'],AndroidPushController::loadSimpleMessage($messages['message']));
                    Log::info($dataToSend);
                    curl_setopt( $httpCall,CURLOPT_POSTFIELDS, $dataToSend);
                    $result = curl_exec($httpCall);
                    Log::info($result);
                }
                curl_close( $httpCall );
            }
        }
    }

    /**
     * send a push notification to the device id received as a paremeter
     */
    private function sendAndroidPushNotification(){
        $httpCall = curl_init();
        curl_setopt( $httpCall,CURLOPT_URL, AndroidPushController::$googleCloudMessaginServerUrl);
        curl_setopt( $httpCall,CURLOPT_POST, true );
        curl_setopt( $httpCall,CURLOPT_HTTPHEADER, AndroidPushController::$httpAndroidPushHeader);
        curl_setopt( $httpCall,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $httpCall,CURLOPT_SSL_VERIFYPEER, false );
        $dataToSend = $this->getAndroidPushData();
        Log::info($dataToSend);
        curl_setopt( $httpCall,CURLOPT_POSTFIELDS, $dataToSend);
        $result = curl_exec($httpCall);
        Log::info($result);
        curl_close( $httpCall );
    }


    /**
     * fill the necessary values por push notification
     */
    private static function getPushData($deviceId,$message){
        $data = array(
            'registration_ids' => array($deviceId),
            'data' => array("message"=>$message)
        );
        return json_encode($data);
    }

    /**
     * fill the necessary values por push notification
     */
    private function getAndroidPushData(){
        $dataMessage = $_POST['message'];
        $deviceId = array($_POST['deviceId']);
        $data = array(
            'registration_ids' => $deviceId,
            'data' => array("message"=>$dataMessage)
        );
        return json_encode($data);
    }

}