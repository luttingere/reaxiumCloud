<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 19/05/2016
 * Time: 04:32 PM
 */

namespace App\Controller;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;
use App\Controller\RoutesController;

class SynchroController extends ReaxiumAPIController
{


    public function synchronizeDeviceAccess()
    {
        Log::info("synchronize a device service");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumDevice"])) {
                    $deviceId = $jsonObject['ReaxiumParameters']["ReaxiumDevice"]['device_id'];
                    $deviceToken = $jsonObject['ReaxiumParameters']["ReaxiumDevice"]['device_token'];
                    $accessBulkObject = !isset($jsonObject['ReaxiumParameters']["ReaxiumDevice"]["accessBulkObject"]) ? null : $jsonObject['ReaxiumParameters']["ReaxiumDevice"]['accessBulkObject'];
                    if (isset($deviceId) && isset($deviceToken)) {
                        $reaxiumDevice = $this->getDeviceInfo($deviceId);
                        if (isset($reaxiumDevice)) {
                            if ($reaxiumDevice[0]['status_id'] == 1) {
                                if ($reaxiumDevice[0]['configured'] == 1) {

                                    //Save the device token in any synchronize action
                                    $fields = array('device_token' => $deviceToken);
                                    $conditions = array('device_id' => $deviceId);
                                    $this->updateDevice($fields, $conditions);

                                    $deviceAccessData = $this->getDeviceAccessData($deviceId);
                                    $deviceRoutesAndStopsData = $this->getDeviceRoutesInformation($deviceId);


                                    if (isset($accessBulkObject)) {
                                        try {
                                            Log::info("Saving the device access control records");
                                            $this->saveBulkOfDeviceAccess($accessBulkObject);
                                        } catch (\Exception $e) {
                                            Log::info("Error: " . $e->getMessage());
                                        }
                                    }

                                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                                    $response['ReaxiumResponse']['message'] = 'Device synchronized successfully';
                                    $response['ReaxiumResponse']['object'] = array(array('deviceData' => array('deviceAccessData' => $deviceAccessData, 'deviceRoutesInfo' => $deviceRoutesAndStopsData)));


                                } else {
                                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$DEVICE_NOT_CONFIGURED_CODE;
                                    $response['ReaxiumResponse']['message'] = ReaxiumApiMessages::$DEVICE_NOT_CONFIGURED_MESSAGE;
                                }
                            } else {
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_STATUS_CODE;
                                $response['ReaxiumResponse']['message'] = ReaxiumApiMessages::$INVALID_STATUS_MESSAGE;
                            }
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'No device found with the id: ' . $deviceId;
                        }
                    } else {
                        $response = parent::seInvalidParametersMessage($response);
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error: " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Response Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    public function synchronizeAtLogin($deviceId, $deviceToken, $accessBulkObject)
    {
        $finalResult = array('code' => ReaxiumApiMessages::$SUCCESS_CODE, 'message' => 'success', 'object' => array());
        try {
            $reaxiumDevice = $this->getDeviceInfo($deviceId);
            if (isset($reaxiumDevice)) {
                if ($reaxiumDevice[0]['status_id'] == 1) {
                    if ($reaxiumDevice[0]['configured'] == 1) {


                        //Save the device token in any synchronize action
                        $fields = array('device_token' => $deviceToken);
                        $conditions = array('device_id' => $deviceId);
                        $this->updateDevice($fields, $conditions);

                        $deviceAccessData = $this->getDeviceAccessData($deviceId);
                        $deviceRoutesAndStopsData = $this->getDeviceRoutesInformation($deviceId);

                        if (isset($accessBulkObject)) {
                            try {
                                Log::info("Saving the device access control records");
                                $this->saveBulkOfDeviceAccess($accessBulkObject);
                            } catch (\Exception $e) {
                                Log::info("Error: " . $e->getMessage());
                            }
                        }

                        $finalResult['object'] = array('deviceAccessData' => $deviceAccessData, 'deviceRoutesInfo' => $deviceRoutesAndStopsData);

                    } else {

                        Log::info("The synchronization for the device id" . $deviceId . ", fail because the device is not configured");
                        $finalResult['code'] = ReaxiumApiMessages::$GENERAL_ERROR_CODE;
                        $finalResult['message'] = 'Device not configured';
                    }
                } else {
                    Log::info("The synchronization for the device id" . $deviceId . ", fail because the device is not active");
                    $finalResult['code'] = ReaxiumApiMessages::$GENERAL_ERROR_CODE;
                    $finalResult['message'] = 'Device not active';
                }
            } else {
                Log::info("The synchronization for the device id" . $deviceId . ", fail because the device information is null");
                $finalResult['code'] = ReaxiumApiMessages::$GENERAL_ERROR_CODE;
                $finalResult['message'] = 'Device with invalid information';
            }
        } catch (\Exception $e) {
            Log::info("Error: " . $e->getMessage());
            $finalResult['code'] = ReaxiumApiMessages::$GENERAL_ERROR_CODE;
            $finalResult['message'] = 'Internal server Error';
        }
        return $finalResult;
    }


    private function saveBulkOfDeviceAccess($bulkOfAccessObject)
    {
        $accessController = new AccessController();
        foreach ($bulkOfAccessObject as $accessObject) {
            try {
                $userId = $accessObject['userId'];
                $deviceId = $accessObject['deviceId'];
                $trafficType = $accessObject['accessType'];
                $trafficTypeId = null;
                switch ($trafficType) {
                    case 'IN':
                        $trafficTypeId = 1;
                        break;
                    case 'OUT':
                        $trafficTypeId = 2;
                        break;
                }
                $accessType = $accessObject['userAccessType'];
                $accessTypeId = null;
                switch ($accessType) {
                    case 'BIOMETRIC':
                        $accessTypeId = 2;
                        break;
                    case 'RFID':
                        $accessTypeId = 3;
                        break;
                }

                $trafficInfo = "";
                $accessController->registerAUserAccess($userId,
                    $deviceId,
                    $accessTypeId,
                    $trafficTypeId,
                    $trafficInfo);

            } catch (\Exception $e) {
                Log::info("Error saving an access in the server, " . $e->getMessage());
            }
        }
    }

    private function getDeviceRoutesInformation($deviceId)
    {
        $routeController = new RoutesController();
        $routesAndStops = $routeController->getRoutesAndStopsByDevice($deviceId, 'start_date', 'desc');
        return $routesAndStops;
    }

    /**
     *
     * get all device access data information
     * @param $deviceId
     * @return deviceAccessData
     */
    private function getDeviceAccessData($deviceId)
    {
        $userAccessControlTable = TableRegistry::get("UserAccessControl");
        $userAccessControl = $userAccessControlTable->find('All',
            array('fields' => array('UserAccessData.user_id',
                'UserAccessData.biometric_code',
                'UserAccessData.rfid_code',
                'UserAccessData.user_login_name',
                'UserAccessData.user_password',
                'AccessType.access_type_name',
                'Users.first_name',
                'Users.second_name',
                'Users.first_last_name',
                'Users.user_photo',
                'Users.birthdate',
                'Users.document_id',
                'Users.fingerprint',
                'Business.business_name',
                'Status.status_name',
                'UserType.user_type_name')))
            ->where(array('UserAccessControl.device_id' => $deviceId, 'Users.status_id' => '1'))
            ->contain(array('UserAccessData' => array('AccessType', 'Users' => array('UserType', 'Business', 'Status'))));
        if ($userAccessControl->count() > 0) {
            $userAccessControl = $userAccessControl->toArray();
        } else {
            $userAccessControl = null;
        }
        return $userAccessControl;
    }

    /**
     *
     * obtain all de information related to an specific device id
     *
     * @param $deviceId
     * @return \Cake\ORM\Table  --Device information
     */
    private function getDeviceInfo($deviceId)
    {
        $device = TableRegistry::get("ReaxiumDevice");
        $device = $device->find()
            ->where(array('ReaxiumDevice.device_id' => $deviceId))
            ->contain(array("Status", "Applications", "Business"));
        if ($device->count() > 0) {
            $device = $device->toArray();
        } else {
            $device = null;
        }

        return $device;
    }

    /**
     * Update the attributes of a Device
     * @param $arrayFields
     * @param $arrayConditions
     */
    private function updateDevice($arrayFields, $arrayConditions)
    {
        $this->loadModel("ReaxiumDevice");
        $this->ReaxiumDevice->updateAll($arrayFields, $arrayConditions);
    }


}