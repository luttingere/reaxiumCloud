<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 05/05/2016
 * Time: 06:21 PM
 */

namespace App\Controller;


use App\Util\ReaxiumUtil;
use Cake\Database\Schema\Table;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class UserAccessController extends ReaxiumAPIController
{


    public function executeAnAccessOfAUser()
    {
        parent::setResultAsAJson();
        $result = parent::getDefaultReaxiumMessage();
        $jsonObjectReceived = parent::getJsonReceived();
        Log::info("Parameter Received:");
        Log::info(json_encode($jsonObjectReceived));
        try {
            if (parent::validReaxiumJsonHeader($jsonObjectReceived)) {
                if (isset($jsonObjectReceived['ReaxiumParameters']['UserAccess'])) {
                    $arrayOfParametersToValidate = array('user_id', 'device_id', 'traffic_type', 'access_type', 'traffic_info');
                    $validation = ReaxiumUtil::validateParameters($arrayOfParametersToValidate, $jsonObjectReceived['ReaxiumParameters']['UserAccess']);
                    if ($validation['code'] == '0') {
                        $userId = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['user_id'];
                        $deviceId = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['device_id'];
                        $trafficType = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['traffic_type'];
                        $accessType = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['access_type'];
                        $trafficInfo = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['traffic_info'];
                        $accessController = new AccessController();
                        $accessObject = $accessController->registerAUserAccess($userId, $deviceId, $accessType, $trafficType, $trafficInfo);
                        if (isset($accessObject)) {

                            try{
                                $this->executePushNotificationProcess($userId,$trafficType,$trafficInfo,$deviceId);
                            }catch (\Exception $e){
                                Log::info("Error enviando la notificacion push, ".$e->getMessage());
                            }

                            $result = parent::setSuccessfulResponse($result);
                            $result['ReaxiumResponse']['object'] = array($accessObject);

                        } else {
                            $result['ReaxiumResponse']['code'] = 15;
                            $result['ReaxiumResponse']['message'] = 'Error generating the user access in our server';
                            Log::info("Error generating the user access in our server");
                        }
                    } else {
                        $result['ReaxiumResponse']['code'] = 14;
                        $result['ReaxiumResponse']['message'] = $validation['message'];
                    }
                } else {
                    $result = parent::seInvalidParametersMessage($result);
                }
            } else {
                $result = parent::setInvalidJsonHeader($result);
            }
        } catch (\Exception $e) {
            $result = parent::setInternalServiceError($result);
            Log::info("Error executing the access of a user" . $e->getMessage());
        }
        Log::info("Response Object: " . json_encode($result));
        $this->response->body(json_encode($result));
    }

    /**
     * @param $userId
     * @param $trafficType
     * @param $trafficInfo
     * @param $deviceId
     */
    private function executePushNotificationProcess($userId, $trafficType, $trafficInfo, $deviceId)
    {
        $userRelationshipTable = TableRegistry::get("UsersRelationship");
        $stakeholders = $userRelationshipTable->find('all', array('fields' => array('stakeholder_id'), 'conditions' => array('user_id' => $userId)));
        if ($stakeholders->count() > 0) {

            $stakeholders = $stakeholders->toArray();
            $stakeholderTable = TableRegistry::get("Stakeholders");

            $accessMessage = array('user_id' => $userId,
                'traffic_info' => $trafficInfo,
                'traffic_type' => array('traffic_type_id' => $trafficType, 'traffic_type_name' => TrafficController::getTrafficTypeName($trafficType)),
                'datetime' => ReaxiumUtil::getSystemDate(),
                'reaxium_device' => array('device_id' => $deviceId),
                'access_message_id' => $trafficType);

            $androidBulkMessage = array();
            $iosBulkMessage = array();
            $stakeholderIdArray = array();
            foreach ($stakeholders as $stakeholder) {
                array_push($stakeholderIdArray, $stakeholder['stakeholder_id']);
            }
            Log::info("stakeholder selected");
            Log::info(json_encode($stakeholderIdArray));
            $iosAndroidTokens = $stakeholderTable->find('all', array('fields' => array('android_id', 'ios_id'), 'conditions' => array('stakeholder_id' => $stakeholderIdArray[0], 'status_id' => '1')));
            Log::info($iosAndroidTokens);
            if ($iosAndroidTokens->count() > 0) {
                $iosAndroidTokens = $iosAndroidTokens->toArray();
                Log::info("Device Tokens");
                Log::info($iosAndroidTokens);
                foreach ($iosAndroidTokens as $tokens) {
                    if (isset($tokens['android_id']) && '' != $tokens['android_id']) {
                        $messageItem = array('deviceId' => $tokens['android_id'], 'message' => $accessMessage);
                        array_push($androidBulkMessage,$messageItem);
                    }
                    if (isset($tokens['ios_id']) && '' != $tokens['ios_id']) {
                        $messageItem = array('deviceId' => $tokens['ios_id'], 'message' => $accessMessage);
                        array_push($iosBulkMessage,$messageItem);
                    }
                }
                AndroidPushController::sendBulkPush($androidBulkMessage);
                IOSPushController::bulkSendIOSNotification($iosBulkMessage);
            }else{
                Log::info("Stakeholders without device token associated");
            }
        }
    }


}