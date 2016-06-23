<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 24/05/2016
 * Time: 05:54 PM
 */

namespace App\Controller;

use Cake\Core\Exception\Exception;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumUtil;
use App\Controller\TrafficController;
use App\Util\ReaxiumApiMessages;

define('SERVER_TRAFFIC_TYPE', 3);
define('EMERGENCY_ALARM', '1');
define('TRAFFIC_ALARM', '2');
define('CHECK_ENGINE_ALARM', '3');
define('ACCIDENT_ALARM', '4');
define('NEXT_STOP_AWARE', '5');

class AlarmController extends ReaxiumAPIController
{


    public static function getAlarmMessage($alarmId)
    {
        $alarmMessage = '';
        switch ($alarmId) {
            case EMERGENCY_ALARM:
                $alarmMessage = 'The Driver @DriverName@ fire an Emergency Alarm from the Reaxium Device ID @device_id@';
                break;
            case TRAFFIC_ALARM;
                $alarmMessage = 'The Driver @DriverName@ just reported a delay because traffic from the Reaxium Device ID @device_id@';
                break;
            case CHECK_ENGINE_ALARM;
                $alarmMessage = 'The Driver @DriverName@ fire a Check Engine Alarm from the Reaxium Device ID @device_id@';
                break;
            case ACCIDENT_ALARM;
                $alarmMessage = 'The Driver @DriverName@ fire a Accident Alarm from the Reaxium Device ID @device_id@';
                break;
            case NEXT_STOP_AWARE:
                $alarmMessage = 'Your stop is the next!';
                break;
        }
        return $alarmMessage;
    }


    public function sendNotification()
    {
        Log::info('sendNotification service involed ');
        parent::setResultAsAJson();
        $jsonObjectReceived = parent::getJsonReceived();
        $response = parent::getDefaultReaxiumMessage();

        Log::info('Object Received: ');
        Log::info(json_encode($jsonObjectReceived));
        try {
            if (parent::validReaxiumJsonHeader($jsonObjectReceived)) {
                if (isset($jsonObjectReceived['ReaxiumParameters']['Notification'])) {
                        $arrayParametersToTest = array('notification_type', 'users_id', 'device_id','driver_name');
                    $validationResult = ReaxiumUtil::validateParameters($arrayParametersToTest, $jsonObjectReceived['ReaxiumParameters']['Notification']);
                    if ($validationResult['code'] == '0') {

                        //parametros
                        $deviceId = $jsonObjectReceived['ReaxiumParameters']['Notification']['device_id'];
                        $notificationType = $jsonObjectReceived['ReaxiumParameters']['Notification']['notification_type'];
                        $arrayOfUsersID = $jsonObjectReceived['ReaxiumParameters']['Notification']['users_id'];
                        $driverName = $jsonObjectReceived['ReaxiumParameters']['Notification']['driver_name'];

                        //envio de la alarma como notification
                        $arrayOfStakeHolders = $this->getArrayOfStakeHolderToBeNotified($arrayOfUsersID);
                        $alarmMessage = self::getAlarmMessage($notificationType);
                        $alarmMessage = str_replace("@DriverName@", $driverName, $alarmMessage);
                        $alarmMessage = str_replace("@device_id@", $deviceId, $alarmMessage);

                        $notification = $this->getNotificationMessage(SERVER_TRAFFIC_TYPE, $alarmMessage, $deviceId, $arrayOfStakeHolders);


                        try{AndroidPushController::sendBulkPush($notification['bulkAndroid']);}catch(\Exception $e){Log::info("Error enviando notification push ANDROID".$e->getMessage());}
//                        try{IOSPushController::bulkSendIOSNotification($notification['bulkIOS']);}catch(\Exception $e){Log::info("Error enviando notification push IOS".$e->getMessage());}


                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                        $response['ReaxiumResponse']['message'] = 'Notification sent successfully';
                        $response['ReaxiumResponse']['object'] = array($notification);

                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_PARAMETERS_CODE;
                        $response['ReaxiumResponse']['message'] = $validationResult['message'];
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } else {
                $response = parent::setInvalidJsonHeader($response);
            }
        } catch (\Exception $e) {
            $response = parent::setInternalServiceError($response);
            Log::info("Error enviando la alarma, Error message: " . $e->getMessage());
        }
        Log::info('Respuesta del servicio de notificacion al Usuario:');
        Log::info(json_encode($response));
        $this->response->body(json_encode($response));
    }

    public function sendNextStopNotification()
    {
        Log::info('sendNotification service involed ');
        parent::setResultAsAJson();
        $jsonObjectReceived = parent::getJsonReceived();
        $response = parent::getDefaultReaxiumMessage();

        Log::info('Object Received: ');
        Log::info(json_encode($jsonObjectReceived));
        try {
            if (parent::validReaxiumJsonHeader($jsonObjectReceived)) {
                if (isset($jsonObjectReceived['ReaxiumParameters']['Notification'])) {
                    $arrayParametersToTest = array('notification_type', 'users_id', 'device_id','driver_name');
                    $validationResult = ReaxiumUtil::validateParameters($arrayParametersToTest, $jsonObjectReceived['ReaxiumParameters']['Notification']);
                    if ($validationResult['code'] == '0') {

                        //parametros
                        $deviceId = $jsonObjectReceived['ReaxiumParameters']['Notification']['device_id'];
                        $notificationType = $jsonObjectReceived['ReaxiumParameters']['Notification']['notification_type'];
                        $arrayOfUsersID = $jsonObjectReceived['ReaxiumParameters']['Notification']['users_id'];
                        $driverName = $jsonObjectReceived['ReaxiumParameters']['Notification']['driver_name'];

                        //envio de la alarma como notification
                        $arrayOfStakeHolders = $this->getArrayOfStakeHolderToBeNotified($arrayOfUsersID);
                        $alarmMessage = self::getAlarmMessage($notificationType);

                        $notification = $this->getNotificationMessage(SERVER_TRAFFIC_TYPE, $alarmMessage, $deviceId, $arrayOfStakeHolders);


                        try{AndroidPushController::sendBulkPush($notification['bulkAndroid']);}catch(\Exception $e){Log::info("Error enviando notification push ANDROID".$e->getMessage());}
//                        try{IOSPushController::bulkSendIOSNotification($notification['bulkIOS']);}catch(\Exception $e){Log::info("Error enviando notification push IOS".$e->getMessage());}


                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                        $response['ReaxiumResponse']['message'] = 'Notification sent successfully';
                        $response['ReaxiumResponse']['object'] = array($notification);

                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_PARAMETERS_CODE;
                        $response['ReaxiumResponse']['message'] = $validationResult['message'];
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } else {
                $response = parent::setInvalidJsonHeader($response);
            }
        } catch (\Exception $e) {
            $response = parent::setInternalServiceError($response);
            Log::info("Error enviando la alarma, Error message: " . $e->getMessage());
        }
        Log::info('Respuesta del servicio de notificacion al Usuario:');
        Log::info(json_encode($response));
        $this->response->body(json_encode($response));
    }

    public function getNotificationMessage($trafficType, $message, $deviceId, $arrayOfStakeHolder)
    {
        $accessMessage = null;
        $arrayOfPushMessagesAndroid = array();
        $arrayOfPushMessagesIOS = array();
        $messageItem = null;
        foreach ($arrayOfStakeHolder as $stakeholder) {

            $accessMessage = array('users_id' => $stakeholder['users_id'],
                'traffic_info' => $message,
                'traffic_type' => array('traffic_type_id' => $trafficType, 'traffic_type_name' => TrafficController::getTrafficTypeName($trafficType)),
                'datetime' => ReaxiumUtil::getSystemDate(),
                'reaxium_device' => array('device_id' => $deviceId),
                'access_message_id' => $trafficType);

            if (isset($stakeholder['android_id'])) {
                $messageItem = array('deviceId' => $stakeholder['android_id'], 'message' => $accessMessage);
                array_push($arrayOfPushMessagesAndroid, $messageItem);
            }

            if (isset($stakeholder['ios_id'])) {
                $messageItem = array('deviceId' => $stakeholder['ios_id'], 'message' => $accessMessage);
                array_push($arrayOfPushMessagesIOS, $messageItem);
            }
        }
        $result = array('bulkAndroid' => $arrayOfPushMessagesAndroid, 'bulkIOS' => $arrayOfPushMessagesIOS);
        return $result;

    }


    public function getArrayOfStakeHolderToBeNotified($userIdArray)
    {
        parent::setResultAsAJson();
        //$userIdArray = array('84', '81', '82', '80');
        $arrayOfStakeHolder = array();
        $stakeHoldersObject = null;
        $stakeholder = null;
        $userRelationshipTable = TableRegistry::get("UsersRelationship");
        $stakeholders = $userRelationshipTable->find('all', array(
            'fields' => array('user_id', 'stakeholder_id'),
            'conditions' => array('user_id IN' => $userIdArray)));

        if ($stakeholders->count() > 0) {

            $stakeholders = $stakeholders->toArray();
            $stakeHolderTable = TableRegistry::get("Stakeholders");


            foreach ($stakeholders as $holder) {

                $stakeholder = $this->getStakeHolderFromArray($arrayOfStakeHolder, $holder->stakeholder_id);

                if (isset($stakeholder)) {

                    array_push($stakeholder['stakeholder']['users_id'], $holder->user_id);
                    $arrayOfStakeHolder[$stakeholder['index']] = $stakeholder['stakeholder'];

                } else {
                    $deviceTokens = $stakeHolderTable->find('all', array('fields' => array('android_id', 'ios_id'), 'conditions' => array('stakeholder_id' => $holder->stakeholder_id)));
                    if ($deviceTokens->count() > 0) {
                        $deviceTokens = $deviceTokens->toArray();
                        $stakeHoldersObject = array('stakeholder_id' => $holder->stakeholder_id,
                            'users_id' => array($holder->user_id),
                            'android_id' => $deviceTokens[0]['android_id'],
                            'ios_id' => $deviceTokens[0]['ios_id']);

                        array_push($arrayOfStakeHolder, $stakeHoldersObject);

                    }
                }
            }
        }

        return $arrayOfStakeHolder;
    }

    private function getStakeHolderFromArray($stakeHolderArray, $stakeholder_id)
    {
        $stakeholderFound = null;
        $index = 0;
        if (sizeof($stakeHolderArray) > 0) {
            foreach ($stakeHolderArray as $stakeholder) {
                if ($stakeholder['stakeholder_id'] == $stakeholder_id) {
                    $stakeholderFound = array('stakeholder' => $stakeholder, 'index' => $index);
                    break;
                }
                $index++;
            }
        }
        return $stakeholderFound;
    }


}