<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 05/05/2016
 * Time: 06:21 PM
 */

namespace App\Controller;


use App\Util\ReaxiumApiMessages;
use App\Util\ReaxiumUtil;
use Cake\Core\Exception\Exception;
use Cake\Database\Schema\Table;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class UserAccessController extends ReaxiumAPIController
{


    public function executeAnRFIDAccessInADevice()
    {
        parent::setResultAsAJson();
        $result = parent::getDefaultReaxiumMessage();
        $jsonObjectReceived = parent::getJsonReceived();
        Log::info("Parameter Received:");
        Log::info(json_encode($jsonObjectReceived));
        try {
            if (isset($jsonObjectReceived['ReaxiumParameters']['UserAccess'])) {
                $arrayOfParametersToValidate = array('user_id', 'device_id', 'card_code', 'device_token');
                $validation = ReaxiumUtil::validateParameters($arrayOfParametersToValidate, $jsonObjectReceived['ReaxiumParameters']['UserAccess']);
                if ($validation['code'] == '0') {

                    $deviceId = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['device_id'];
                    $userId = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['user_id'];
                    $cardCode = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['card_code'];

                    //validate if the access exist
                    $userAccessDataTable = TableRegistry::get("UserAccessData");
                    $accessData = $userAccessDataTable->find('all', array('fields' => array('user_access_data_id'), 'conditions' => array('user_id' => $userId, 'rfid_code' => $cardCode)));

                    if ($accessData->count() > 0) {

                        $userTable = TableRegistry::get("Users");
                        $userInfo = $userTable->find('all', array(
                            'fields' => array('Users.user_id',
                                'Users.first_name',
                                'Users.first_last_name',
                                'Users.document_id',
                                'Users.user_photo',
                                'Business.business_name',
                                'UserType.user_type_name'),
                            'conditions' => array('Users.user_id' => $userId, 'Users.status_id' => 1), 'contain' => array('Business', 'UserType')));

                        Log::info("query:");
                        Log::info($userInfo);
                        if ($userInfo->count()) {
                            $userInfo = $userInfo->toArray();
                            $userTypeName = strtolower($userInfo[0]['user_type']['user_type_name']);
                            $additionalMessage = '';
                            $finalObject = null;
                            if ($userTypeName == 'administrator' || $userTypeName == 'driver') {

                                if($userTypeName == 'driver'){
                                    //Init the process of getting all device information


                                    $accessBulkObject = !isset($jsonObjectReceived['ReaxiumParameters']['UserAccess']['accessBulkObject']) ? null : $jsonObjectReceived['ReaxiumParameters']['UserAccess']['accessBulkObject'];
                                    $deviceToken = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['device_token'];

                                    $synchroniceObject = new SynchroController();
                                    $synchroniceData = $synchroniceObject->synchronizeAtLogin($deviceId, $deviceToken, $accessBulkObject);

                                    if ($synchroniceData['code'] != ReaxiumApiMessages::$SUCCESS_CODE) {
                                        $additionalMessage = 'Synchronization failure';
                                    }

                                    $finalObject = array('user'=>$userInfo[0],'deviceData'=> $synchroniceData['object']);

                                }else{

                                    $finalObject = array('user'=>$userInfo[0],'deviceData'=> null);

                                }
                                $userName = $userInfo[0]['first_name'] . ' ' . $userInfo[0]['first_last_name'];
                                $result['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                                $result['ReaxiumResponse']['message'] = 'Access Granted, Welcome ' . $userName . ' ' . $additionalMessage;
                                $result['ReaxiumResponse']['object'] = array($finalObject);

                            } else {
                                $result['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_USER_ACCESS_CODE;
                                $result['ReaxiumResponse']['message'] = 'Invalid user type, access denied';
                            }
                        } else {
                            $result['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_USER_ACCESS_CODE;
                            $result['ReaxiumResponse']['message'] = 'The user information cannot be found';
                        }
                    } else {
                        $result['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_USER_ACCESS_CODE;
                        $result['ReaxiumResponse']['message'] = 'Invalid User';
                    }

                } else {
                    $result['ReaxiumResponse']['code'] = 14;
                    $result['ReaxiumResponse']['message'] = $validation['message'];
                }
            } else {
                $result = parent::seInvalidParametersMessage($result);
            }
        } catch (\Exception $e) {
            $result = parent::setInternalServiceError($result);
            Log::info("Error executing a RFID access into in a device" . $e->getMessage());
        }
        Log::info("Response Object: " . json_encode($result));

        $this->response->body(json_encode($result));
    }


    public function executeUserAndPasswordAccessInADevice()
    {
        parent::setResultAsAJson();
        $result = parent::getDefaultReaxiumMessage();
        $jsonObjectReceived = parent::getJsonReceived();
        Log::info("Parameter Received:");
        Log::info(json_encode($jsonObjectReceived));
        try {
            if (isset($jsonObjectReceived['ReaxiumParameters']['UserAccess'])) {
                $arrayOfParametersToValidate = array('user_name', 'user_password', 'device_id', 'device_token');
                $validation = ReaxiumUtil::validateParameters($arrayOfParametersToValidate, $jsonObjectReceived['ReaxiumParameters']['UserAccess']);
                if ($validation['code'] == '0') {

                    $useName = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['user_name'];
                    $userPassword = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['user_password'];

                    //validate if the access exist
                    $userAccessDataTable = TableRegistry::get("UserAccessData");
                    $accessData = $userAccessDataTable->find('all', array('fields' => array('user_id'), 'conditions' => array('user_login_name' => $useName, 'user_password' => $userPassword)));

                    if ($accessData->count() > 0) {
                        $accessData = $accessData->toArray();
                        $userTable = TableRegistry::get("Users");
                        $userInfo = $userTable->find('all', array(
                            'fields' => array('Users.user_id',
                                'Users.first_name',
                                'Users.first_last_name',
                                'Users.document_id',
                                'Users.user_photo',
                                'Business.business_name',
                                'UserType.user_type_name'),
                            'conditions' => array('Users.user_id' => $accessData[0]['user_id'], 'Users.status_id' => 1), 'contain' => array('Business', 'UserType')));

                        Log::info("query:");
                        Log::info($userInfo);
                        if ($userInfo->count()) {
                            $userInfo = $userInfo->toArray();
                            $userTypeName = strtolower($userInfo[0]['user_type']['user_type_name']);
                            $additionalMessage = '';
                            if ($userTypeName == 'administrator' || $userTypeName == 'driver') {

                                if($userTypeName == 'driver'){
                                    //Init the process of getting all device information

                                    $deviceId = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['device_id'];
                                    $accessBulkObject = !isset($jsonObjectReceived['ReaxiumParameters']['UserAccess']['accessBulkObject']) ? null : $jsonObjectReceived['ReaxiumParameters']['UserAccess']['accessBulkObject'];
                                    $deviceToken = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['device_token'];

                                    $synchroniceObject = new SynchroController();
                                    $synchroniceData = $synchroniceObject->synchronizeAtLogin($deviceId, $deviceToken, $accessBulkObject);

                                    if ($synchroniceData['code'] != ReaxiumApiMessages::$SUCCESS_CODE) {
                                        $additionalMessage = 'Synchronization failure';
                                    }

                                    $finalObject = array('user'=>$userInfo[0],'deviceData'=> $synchroniceData['object']);

                                }else{

                                    $finalObject = array('user'=>$userInfo[0],'deviceData'=> null);

                                }
                                $userName = $userInfo[0]['first_name'] . ' ' . $userInfo[0]['first_last_name'];
                                $result['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                                $result['ReaxiumResponse']['message'] = 'Access Granted, Welcome ' . $userName . ' ' . $additionalMessage;
                                $result['ReaxiumResponse']['object'] = array($finalObject);

                            } else {
                                $result['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_USER_ACCESS_CODE;
                                $result['ReaxiumResponse']['message'] = 'Invalid user type, access denied';
                            }
                        } else {
                            $result['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_USER_ACCESS_CODE;
                            $result['ReaxiumResponse']['message'] = 'The user information cannot be found';
                        }
                    } else {
                        $result['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_USER_ACCESS_CODE;
                        $result['ReaxiumResponse']['message'] = 'The username that you\'ve entered doesn\'t match any account.';
                    }

                } else {
                    $result['ReaxiumResponse']['code'] = 14;
                    $result['ReaxiumResponse']['message'] = $validation['message'];
                }
            } else {
                $result = parent::seInvalidParametersMessage($result);
            }
        } catch (\Exception $e) {
            $result = parent::setInternalServiceError($result);
            Log::info("Error executing a RFID access into in a device" . $e->getMessage());
        }
        Log::info("Response Object: " . json_encode($result));

        $this->response->body(json_encode($result));
    }


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

                            try {
                                $this->executePushNotificationProcess($userId, $trafficType, $trafficInfo, $deviceId);
                            } catch (\Exception $e) {
                                Log::info("Error enviando la notificacion push, " . $e->getMessage());
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
            $iosAndroidTokens = $stakeholderTable->find('all', array('fields' => array('android_id', 'ios_id'), 'conditions' => array('stakeholder_id IN' => $stakeholderIdArray, 'status_id' => '1')));
            Log::info($iosAndroidTokens);
            if ($iosAndroidTokens->count() > 0) {
                $iosAndroidTokens = $iosAndroidTokens->toArray();
                Log::info("Device Tokens");
                Log::info($iosAndroidTokens);
                foreach ($iosAndroidTokens as $tokens) {
                    if (isset($tokens['android_id']) && '' != $tokens['android_id']) {
                        $messageItem = array('deviceId' => $tokens['android_id'], 'message' => $accessMessage);
                        array_push($androidBulkMessage, $messageItem);
                    }
                    if (isset($tokens['ios_id']) && '' != $tokens['ios_id']) {
                        $messageItem = array('deviceId' => $tokens['ios_id'], 'message' => $accessMessage);
                        array_push($iosBulkMessage, $messageItem);
                    }
                }
                AndroidPushController::sendBulkPush($androidBulkMessage);
                IOSPushController::bulkSendIOSNotification($iosBulkMessage);
            } else {
                Log::info("Stakeholders without device token associated");
            }
        }
    }


}