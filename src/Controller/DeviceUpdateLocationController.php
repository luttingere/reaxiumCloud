<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 26/05/2016
 * Time: 12:48 PM
 */

namespace App\Controller;


use App\Util\ReaxiumUtil;
use Cake\Log\Log;
use App\Controller\TrafficController;
use App\Util\ReaxiumApiMessages;
use Cake\ORM\TableRegistry;

class DeviceUpdateLocationController extends ReaxiumAPIController
{


    public function notifyLocation()
    {
        Log::info("notifyLocation service invoked ");
        parent::setResultAsAJson();
        $objectReceived = parent::getJsonReceived();
        $response = parent::getDefaultReaxiumMessage();
        Log::info("Object received: ");
        Log::info(json_encode($objectReceived));
        try {
            if (parent::validReaxiumJsonHeader($objectReceived)) {
                if (isset($objectReceived['ReaxiumParameters']['DeviceUpdateLocation'])) {
                    $arrayToTest = array('device_id', 'latitude', 'longitude');
                    $validationResult = ReaxiumUtil::validateParameters($arrayToTest, $objectReceived['ReaxiumParameters']['DeviceUpdateLocation']);
                    if ($validationResult['code'] == 0) {

                        $deviceId = $objectReceived['ReaxiumParameters']['DeviceUpdateLocation']['device_id'];
                        $driverUserId = $objectReceived['ReaxiumParameters']['DeviceUpdateLocation']['driver_user_id'];
                        $latitude = $objectReceived['ReaxiumParameters']['DeviceUpdateLocation']['latitude'];
                        $longitude = $objectReceived['ReaxiumParameters']['DeviceUpdateLocation']['longitude'];


                        //update the actual position of the device
                        $this->updatePosition($deviceId, $driverUserId, $latitude, $longitude);
                        Log::info("Position updated successfully: ");


                        $datePlusSeconds = ReaxiumUtil::getSystemDateMinusTime(300, 'S');

                        Log::info($datePlusSeconds);

                        $deviceLocationRegistryTable = TableRegistry::get("DeviceLocationStakeHolder");
//                        $deviceLocationRegistry = $deviceLocationRegistryTable->find('all')->where(
//                            array(
//                                'device_id' => $deviceId,
//                                'registry_date BETWEEN :start AND :end'))
//                            ->bind(':start', $dateNow, 'datetime')
//                            ->bind(':end', $datePlusSeconds, 'datetime');

                        $deviceLocationRegistry = $deviceLocationRegistryTable->find('all')->where(array('device_id' => $deviceId, 'registry_date >' => $datePlusSeconds));

                        if ($deviceLocationRegistry->count() > 0) {

                            Log::info("Prcessing the stakeholder to be notified with the device location ");

                            $deviceLocationRegistry = $deviceLocationRegistry->toArray();

                            $locationPushMessage = $this->getLocationUpdateNotificationMessage(4, $latitude, $longitude, $deviceId, $deviceLocationRegistry);

                            if ($locationPushMessage != null) {
                                AndroidPushController::sendBulkPush($locationPushMessage['bulkAndroid']);
                                //IOSPushController::bulkSendIOSNotification($locationPushMessage['bulkIOS']);
                            }

                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                            $response['ReaxiumResponse']['message'] = sizeof($deviceLocationRegistry) . ' people notified with the location';
                            $response['ReaxiumResponse']['object'] = array($locationPushMessage);


                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                            $response['ReaxiumResponse']['message'] = 'No one has been notified with the current location';
                        }

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
            Log::info("Error solicitando ubnicacion de autobus: Error message " . $e->getMessage());
            $response = parent::setInternalServiceError($response);
        }
        Log::info("Response Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    private function updatePosition($deviceId, $driverUserId, $latitude, $longitude)
    {
        $deviceLocationTable = TableRegistry::get("DeviceLocation");
        $deviceLocation = $deviceLocationTable->findByDeviceId($deviceId);
        if ($deviceLocation->count() > 0) {

            $deviceLocationTable->updateAll(
            //Set
                array(
                    'user_id' => $driverUserId,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'date_location' => ReaxiumUtil::getSystemDate()),
                //Where
                array('device_id' => $deviceId));

        } else {
            $devicePositionEntity = $deviceLocationTable->newEntity();
            $devicePositionEntity->user_id = $driverUserId;
            $devicePositionEntity->device_id = $deviceId;
            $devicePositionEntity->latitude = $latitude;
            $devicePositionEntity->longitude = $longitude;
            $devicePositionEntity->date_location = ReaxiumUtil::getSystemDate();
            $deviceLocationTable->save($devicePositionEntity);
        }
    }


    public function getLocationUpdateNotificationMessage($trafficType, $latitude, $longitude, $deviceId, $arrayOfLocationStakeholderRegistry)
    {
        $accessMessage = null;
        $arrayOfPushMessagesAndroid = array();
        $arrayOfPushMessagesIOS = array();
        $messageItem = null;
        foreach ($arrayOfLocationStakeholderRegistry as $registry) {

            $accessMessage = array(
                'traffic_info' => '',
                'traffic_type' => array('traffic_type_id' => $trafficType, 'traffic_type_name' => TrafficController::getTrafficTypeName($trafficType)),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'reaxium_device' => array('device_id' => $deviceId),
                'access_message_id' => 100);

            $messageItem = array('deviceId' => $registry['device_token'], 'message' => $accessMessage);
            if ($registry['device_platform'] == 'ANDROID') {
                array_push($arrayOfPushMessagesAndroid, $messageItem);
            } else if (isset($stakeholder['IOS'])) {
                array_push($arrayOfPushMessagesIOS, $messageItem);
            }
        }
        $result = array('bulkAndroid' => $arrayOfPushMessagesAndroid, 'bulkIOS' => $arrayOfPushMessagesIOS);
        return $result;

    }


    public function requestDeviceUpdateLocation()
    {
        Log::info("requestDeviceUpdateLocation service invoked ");
        parent::setResultAsAJson();
        $objectReceived = parent::getJsonReceived();
        $response = parent::getDefaultReaxiumMessage();
        Log::info("Object received: ");
        Log::info(json_encode($objectReceived));
        try {
            if (parent::validReaxiumJsonHeader($objectReceived)) {
                if (isset($objectReceived['ReaxiumParameters']['DeviceUpdateLocation'])) {
                    $arrayToTest = array('user_in_track_id', 'user_stakeholder_id', 'device_token', 'device_platform');
                    $validationResult = ReaxiumUtil::validateParameters($arrayToTest, $objectReceived['ReaxiumParameters']['DeviceUpdateLocation']);
                    if ($validationResult['code'] == 0) {

                        $userInTrack = $objectReceived['ReaxiumParameters']['DeviceUpdateLocation']['user_in_track_id'];

                        $trafficController = new TrafficController();
                        $lastTrafficResult = $trafficController->getLastTrafficOfAUser($userInTrack);
                        if ($lastTrafficResult['userInABus']) {

                            //fill variables
                            $lastTrafficObject = $lastTrafficResult['object'];
                            $userStakeholderId = $objectReceived['ReaxiumParameters']['DeviceUpdateLocation']['user_stakeholder_id'];
                            $deviceToken = $objectReceived['ReaxiumParameters']['DeviceUpdateLocation']['device_token'];
                            $devicePlatform = $objectReceived['ReaxiumParameters']['DeviceUpdateLocation']['device_platform'];
                            $deviceId = $lastTrafficObject[0]['device_id'];

                            //lookup for another registry with the same user id and device platform ('ANDROID','IOS')
                            $deviceLocationRegistryTable = TableRegistry::get("DeviceLocationStakeHolder");
                            $deviceLocationRegistry = $deviceLocationRegistryTable->find('all', array(
                                'conditions' => array(
                                    'user_stakeholder_id' => $userStakeholderId,
                                    'user_in_track_id' => $userInTrack,
                                    'device_platform' => strtoupper($devicePlatform))));


                            if ($deviceLocationRegistry->count() > 0) {
                                //update the datetime  of the record found

                                $deviceLocationRegistryTable->updateAll(
                                    array( //SET
                                        'registry_date' => ReaxiumUtil::getSystemDate(),
                                        'device_id' => $deviceId,
                                        'device_token' => $deviceToken),
                                    array( //WHERE
                                        'user_stakeholder_id' => $userStakeholderId,
                                        'user_in_track_id' => $userInTrack,
                                        'device_platform' => strtoupper($devicePlatform)));


                            } else {
                                //registry the user for update location notifications of the device
                                $deviceLocationEntry = $deviceLocationRegistryTable->newEntity();
                                $deviceLocationEntry->user_stakeholder_id = $userStakeholderId;
                                $deviceLocationEntry->user_in_track_id = $userInTrack;
                                $deviceLocationEntry->device_token = $deviceToken;
                                $deviceLocationEntry->device_platform = strtoupper($devicePlatform);
                                $deviceLocationEntry->device_id = $deviceId;
                                $deviceLocationEntry->registry_date = ReaxiumUtil::getSystemDate();
                                $deviceLocationRegistryTable->save($deviceLocationEntry);
                            }


                            //get the last position of the device
                            $deviceLocationTable = TableRegistry::get("DeviceLocation");
                            $deviceLocation = $deviceLocationTable->findByDeviceId($deviceId);

                            if ($deviceLocation->count() > 0) {
                                $deviceLocation = $deviceLocation->toArray();
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                                $response['ReaxiumResponse']['message'] = 'update location request successful created';
                                $response['ReaxiumResponse']['object'] = array($deviceLocation[0]);
                            } else {
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                                $response['ReaxiumResponse']['message'] = 'The system fail looking for the device number: ' . $deviceId;
                            }
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'The student is not in a Bus';
                        }
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
            Log::info("Error solicitando ubnicacion de autobus: Error message " . $e->getMessage());
            $response = parent::setInternalServiceError($response);
        }
        Log::info("Response Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

}