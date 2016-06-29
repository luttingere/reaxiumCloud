<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 18/03/2016
 * Time: 02:56 PM
 */

namespace App\Controller;


use Cake\Core\Exception\Exception;
use Cake\I18n\Time;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;

define("MODE_DEVICE_ID", 1);
define("MODE_ALL_DEVICE", 2);

class DeviceController extends ReaxiumAPIController
{

    /**
     * @api {post} /Device/deviceInfo Get a Device Information by ID
     * @apiName deviceInfo
     * @apiGroup Device
     *
     * @apiParamExample {json} Request-Example:
     *
     * {
     *  "ReaxiumParameters": {
     *      "ReaxiumDevice": {
     *          "device_id": "1"
     *           }
     *      }
     *  }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {"ReaxiumResponse": {
     *              "code": "",
     *              "message": "",
     *               "object": [{
     *                   "device_id": 1,
     *                   "device_name": "Test",
     *                   "device_description": "Device 1",
     *                   "status_id": 1,
     *                   "application": {
     *                   "application_id": 1,
     *                   "application_name": "Reaxium Access Control",
     *                   "status_id": 1,
     *                   "version": 1
     *                  }]
     *                }
     *              }
     *
     *
     * @apiErrorExample Error-Response Device Not Found:
     * {
     *  "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "Device Not found",
     *      "object": []
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {
     *  "ReaxiumResponse": {
     *      "code": 2,
     *      "message": "Invalid Parameters received, please checkout the api documentation",
     *      "object": []
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response Invalid Json Object:
     * {
     *  "ReaxiumResponse": {
     *      "code": 2,
     *      "message": "Invalid Parameters received, please checkout the api documentation",
     *      "object": []
     *       }
     *     }
     */
    public function deviceInfo()
    {
        Log::info("Device information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        $validate = false;
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumDevice"])) {
                    $this->loadModel("ReaxiumDevice");
                    Log::info($jsonObject['ReaxiumParameters']);

                    $device = $this->ReaxiumDevice->newEntity();
                    $device = $this->ReaxiumDevice->patchEntity($device, $jsonObject['ReaxiumParameters']);
                    $business_id = !isset($device->business_id) ? null : $device->business_id;

                    if (isset($device->device_id)) {

                        if (!isset($business_id)) {
                            $device = $this->getDeviceInfo($device->device_id);
                        } else {
                            $device = $this->getDeviceFilterBusiness($device->device_id, $business_id);

                        }

                        if (isset($device)) {

                            $response['ReaxiumResponse']['object'] = $device;
                            $response = parent::setSuccessfulResponse($response);

                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'Device Not found';
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
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
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
            ->where(array('device_id' => $deviceId, 'ReaxiumDevice.status_id' => 1))
            ->contain(array("Status", "Applications", "Business"));
        if ($device->count() > 0) {
            $device = $device->toArray();
        } else {
            $device = null;
        }

        return $device;
    }


    /**
     * select device.device_id,
     * device.device_name,
     * device_description,
     * device.status_id,
     * device.configured,
     * device_token,
     * busi.business_id,
     * busi.business_name,
     * busi.business_id_number
     * from reaxium.reaxium_device as device
     * inner join reaxium.device_business as devbusi
     * on device.device_id = devbusi.device_id
     * inner join reaxium.business as busi
     * on devbusi.business_id = busi.business_id
     * and devbusi.business_id = 2
     * and devbusi.device_id = 24
     * @param $device_id
     * @param $business_id
     * @return array|\Cake\ORM\Query|null
     */
    private function getDeviceFilterBusiness($device_id, $business_id)
    {

        $deviceTable = TableRegistry::get("ReaxiumDevice");
        $query = $deviceTable->find();
        $query->select(array(
            'ReaxiumDevice.device_id',
            'ReaxiumDevice.device_name',
            'ReaxiumDevice.device_description',
            'ReaxiumDevice.status_id',
            'ReaxiumDevice.configured',
            'ReaxiumDevice.device_token',
            'business.business_id',
            'business.business_name',
            'business.business_id_number'
        ));
        $query->hydrate(false);
        $query->join(array(
            'devbusi' => array(
                'table' => 'device_business',
                'type' => 'INNER',
                'conditions' => 'ReaxiumDevice.device_id = devbusi.device_id'
            ),
            'business' => array(
                'table' => 'business',
                'type' => 'INNER',
                'conditions' => 'devbusi.business_id = business.business_id'
            )
        ));

        $query->andWhere(array('devbusi.device_id' => $device_id, 'devbusi.business_id' => $business_id));

        if ($query->count() > 0) {
            $query = $query->toArray();
        } else {
            $query = null;
        }
        return $query;
    }


    /**
     * @api {get} /Device/allDeviceInfo get all devices in the system
     * @apiName allDevicesInfo
     * @apiGroup Device
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {"ReaxiumResponse": {
     *              "code": "",
     *              "message": "",
     *               "object": [
     *                  {"device_id": 1,
     *                   "device_name": "Test 1",
     *                   "device_description": "Device 1",
     *                   "status_id": 1,
     *                   "application": {
     *                   "application_id": 1,
     *                   "application_name": "Reaxium Access Control",
     *                   "status_id": 1,
     *                   "version": 1
     *                  },
     *                  {"device_id": 2,
     *                   "device_name": "Test 2",
     *                   "device_description": "Device 2",
     *                   "status_id": 1,
     *                   "application": {
     *                   "application_id": 1,
     *                   "application_name": "Reaxium Access Control",
     *                   "status_id": 1,
     *                   "version": 1}
     *                  ]
     *                }
     *              }
     *
     *
     * @apiErrorExample Error-Response No Deices Found:
     * {
     *  "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "No Deices Found",
     *      "object": []
     *      }
     *   }
     *
     */
    public function allDevicesInfo()
    {
        Log::info("All Device information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        try {
            $devices = $this->getAllDevices();
            if (isset($devices)) {
                $response['ReaxiumResponse']['object'] = $devices;
            } else {
                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                $response['ReaxiumResponse']['message'] = 'No Deices Found';
            }
        } catch (\Exception $e) {
            Log::info("Error: " . $e->getMessage());
            $response = parent::setInternalServiceError($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    /**
     *
     * obtain all information related to the devices in the system
     *
     * @return \Cake\ORM\Table  --All Devices information
     */
    private function getAllDevices()
    {
        $devices = TableRegistry::get("ReaxiumDevice");
        $devices = $devices->find()->contain(array("Applications", "Status"))->order(array('device_id'));
        if ($devices->count() > 0) {
            $devices = $devices->toArray();
        } else {
            $devices = null;
        }
        return $devices;
    }


    /**
     * @api {post} /Device/deleteDevice Delete a device from the system
     * @apiName deleteDevice
     * @apiGroup Device
     *
     * @apiParamExample {json} Request-Example:
     *
     * {"ReaxiumParameters": {
     *      "ReaxiumDevice": {
     *          "device_id": "1"
     *            }
     *          }
     *      }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {"ReaxiumResponse": {
     *              "code": "00",
     *              "message": "SUCCESSFUL DELETE",
     *               "object": []
     *
     *
     * @apiErrorExample Error-Response Device Not Found:
     *      {"ReaxiumResponse": {
     *          "code": 404,
     *          "message": "Device Not found",
     *          "object": []
     *           }
     *          }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     *      {"ReaxiumResponse": {
     *          "code": 2,
     *          "message": "Invalid Parameters received, please checkout the api documentation",
     *          "object": []
     *           }
     *          }
     *
     *
     * @apiErrorExample Error-Response Invalid Json Object:
     *      {"ReaxiumResponse": {
     *           "code": 2,
     *           "message": "Invalid Parameters received, please checkout the api documentation",
     *           "object": []
     *          }
     *      }
     */
    public function deleteDevice()
    {
        Log::info("deleting  Device service is running");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $deviceId = null;
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumDevice"])) {
                    $this->loadModel("ReaxiumDevice");
                    $device = $this->ReaxiumDevice->newEntity();
                    $device = $this->ReaxiumDevice->patchEntity($device, $jsonObject['ReaxiumParameters']);
                    if (isset($device->device_id)) {
                        $deviceId = $device->device_id;
                        $device = $this->getDeviceInfo($deviceId);
                        if (isset($device)) {
                            $this->deleteADevice($deviceId);
                            $response = parent::setSuccessfulDelete($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'Device Not found';
                        }
                    } else {
                        $response = parent::seInvalidParametersMessage($response);
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error deleting the device: " . $deviceId . " error:" . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     * Delete a device from de system
     * @param $deviceId
     */
    private function deleteADevice($deviceId)
    {
        $this->loadModel("ReaxiumDevice");
        $this->loadModel("ApplicationsRelationship");
        $associatedDevice = $this->ApplicationsRelationship->findByDeviceId($deviceId);
        Log::info($associatedDevice);
        if ($associatedDevice->count() > 0) {
            $associatedDevice = $associatedDevice->toArray();
            Log::info("associatedDevice: " . $associatedDevice);
            Log::info("The device id: " . $associatedDevice[0]['device_id'] . "has an association and it will be deleted");
            $this->ApplicationsRelationship->deleteAll(array('device_id' => $associatedDevice[0]['device_id']));
        }
        $this->ReaxiumDevice->updateAll(array('status_id' => '3'), array('device_id' => $deviceId));
    }


    /**
     * @api {post} /Device/associateAnApplicationWithADevice Associate An Application With a Device
     * @apiName associateAnApplicationWithADevice
     * @apiGroup Device
     *
     * @apiParamExample {json} Request-Example:
     *   {"ReaxiumParameters": {
     *      "ApplicationsRelationship": {
     *       "application_id": "1",
     *       "device_id": "1"
     *       }
     *   }
     * }
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "ReaxiumResponse": {
     *           "code": 0,
     *           "message": "SAVED SUCCESSFUL",
     *           "object": {}
     *          }
     *      }
     *
     *
     * @apiErrorExample Error-Response: Association already exist
     *  {
     *          "ReaxiumResponse": {
     *              "code": 101,
     *              "message": "this association already exist in the system",
     *              "object": []
     *              }
     *          }
     *
     *
     * @apiErrorExample Error-Response:
     *  {
     *          "ReaxiumResponse": {
     *              "code": 03,
     *              "message":"Internal Server Error, Please contact with the api administrator"
     *              "object": []
     *              "errorInfo": "Please, validate if your device_id and application_id exist in the cloud",
     *              }
     *          }
     *
     */
    public function associateAnApplicationWithADevice()
    {
        Log::info("Associating an applications with a a Device Service running");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                $this->loadModel("ApplicationsRelationship");
                $relationship = $this->ApplicationsRelationship->newEntity();
                $relationship = $this->ApplicationsRelationship->patchEntity($relationship, $jsonObject['ReaxiumParameters']);
                if (isset($relationship->device_id)) {
                    $relationshipFound = $this->ApplicationsRelationship->findByDeviceId($relationship->device_id);
                    if ($relationshipFound->count() > 0) {
                        $relationshipFound = $relationshipFound->toArray();
                        if ($relationshipFound[0]['application_id'] != $relationship->application_id) {
                            $this->ApplicationsRelationship->updateAll(array('application_id' => $relationship->application_id), array('device_id' => $relationship->device_id));
                            $response = parent::setSuccessfulSave($response);
                            Log::info("The Device ID: " . $relationship->device_id . "has been associated with the application id: " . $relationship->application_id);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                            $response['ReaxiumResponse']['message'] = 'this association already exist in the system';
                        }
                    } else {
                        $this->ApplicationsRelationship->save($relationship);
                        $response = parent::setSuccessfulSave($response);
                        Log::info("The Device ID: " . $relationship->device_id . "has been associated with the application id: " . $relationship->application_id);
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error associating an Application with a device: " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
                $response['ReaxiumResponse']['errorInfo'] = 'Please, validate if your device_id and application_id exist in the cloud';
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        $this->response->body(json_encode($response));
    }


    /**
     * @api {post} /Device/changeDeviceStatus Change The Status Of A Device
     * @apiName changeDeviceStatus
     * @apiGroup Device
     *
     * @apiParamExample {json} Request-Example:
     *
     * {"ReaxiumParameters": {
     *      "ReaxiumDevice": {
     *          "device_id": "1"
     *          "status_id": "1"
     *            }
     *          }
     *      }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {"ReaxiumResponse": {
     *              "code": "00",
     *              "message": "SUCCESSFUL UPDATED",
     *               "object": []
     *
     *
     * @apiErrorExample Error-Response Device Not Found:
     *      {"ReaxiumResponse": {
     *          "code": 404,
     *          "message": "Device Not found",
     *          "object": []
     *           }
     *          }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     *      {"ReaxiumResponse": {
     *          "code": 2,
     *          "message": "Invalid Parameters received, please checkout the api documentation",
     *          "object": []
     *           }
     *          }
     *
     *
     * @apiErrorExample Error-Response Invalid Json Object:
     *      {"ReaxiumResponse": {
     *           "code": 2,
     *           "message": "Invalid Parameters received, please checkout the api documentation",
     *           "object": []
     *          }
     *      }
     */
    public function changeDeviceStatus()
    {
        Log::info("updating the status of a Device service is running");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $deviceId = null;
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumDevice"])) {
                    $this->loadModel("ReaxiumDevice");
                    $device = $this->ReaxiumDevice->newEntity();
                    $device = $this->ReaxiumDevice->patchEntity($device, $jsonObject['ReaxiumParameters']);
                    if (isset($device->device_id) && isset($device->status_id)) {
                        $deviceId = $device->device_id;
                        $deviceFound = $this->getDeviceInfo($deviceId);
                        if (isset($deviceFound)) {
                            $this->updateDevice(array('status_id' => $device->status_id), array('device_id' => $deviceId));
                            $response = parent::setSuccessfulUpdated($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'Device Not found';
                        }
                    } else {
                        $response = parent::seInvalidParametersMessage($response);
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error updating the status of the Device: " . $deviceId . " error:" . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
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


    /**
     * @api {post} /Device/deviceTrafficStatus get Device's Traffic Status
     * @apiName deviceTrafficStatus
     * @apiGroup Device
     *
     * @apiParamExample {json} Request-Example:
     *
     * {
     *  "ReaxiumParameters": {
     *      "ReaxiumDevice": {
     *          "device_id": "1"
     *        }
     *      }
     *   }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     * "ReaxiumResponse": {
     * "code": 0,
     * "message": "SUCCESSFUL REQUEST",
     * "object": {
     * "DeviceTrafficStatus": {
     * "IN": [{
     * "user_id": 3,
     * "document_id": "19055085",
     * "first_name": "Jhon",
     * "second_name": "Andrew",
     * "first_last_name": "Doe",
     * "second_last_name": "Smith",
     * "status_id": 1,
     * "trafficDate": "2016-03-28T19:11:46+0000"
     * }],
     * "OUT": [{
     * "user_id": 1,
     * "document_id": "19044081",
     * "first_name": "Eduardo",
     * "second_name": "Jose",
     * "first_last_name": "Luttinger",
     * "second_last_name": "Mogollon",
     * "status_id": 1,
     * "trafficDate": "2016-03-28T20:11:45+0000"
     * }]
     * }
     * }
     * }
     * }
     *
     *
     * @apiErrorExample Error-Response Device Not Found:
     * {
     *  "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "Device Not found",
     *      "object": []
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {
     *  "ReaxiumResponse": {
     *       "code": 2,
     *       "message": "Invalid Parameters received, please checkout the api documentation",
     *       "object": []
     *      }
     *   }
     *
     *
     * @apiErrorExample Error-Response Invalid Json Object:
     * {
     *      "ReaxiumResponse": {
     *          "code": 2,
     *          "message": "Invalid Parameters received, please checkout the api documentation",
     *          "object": []
     *       }
     *      }
     */
    public function deviceTrafficStatus()
    {
        Log::info("Device user traffic info");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumDevice"])) {
                    $this->loadModel("ReaxiumDevice");
                    Log::info($jsonObject['ReaxiumParameters']);
                    $deviceId = $jsonObject['ReaxiumParameters']["ReaxiumDevice"]['device_id'];
                    if (isset($deviceId)) {
                        $deviceTrafficInfo = $this->getDeviceTrafficInfo($deviceId);
                        if (isset($deviceTrafficInfo)) {
                            $response['ReaxiumResponse']['object'] = $deviceTrafficInfo;
                            $response = parent::setSuccessfulResponse($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'Device Not found';
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
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     *
     * Get the daily information of the device traffic accesss
     * @param $deviceId
     * @return array
     */
    private function getDeviceTrafficInfo($deviceId)
    {
        $trafficTable = TableRegistry::get("Traffic");
        $accessController = new AccessController();
        $userAccessControlTable = TableRegistry::get("UserAccessControl");
        $userWithAccessInDevice = $userAccessControlTable->findByDeviceId($deviceId)->contain("Users");
        $userTrafficType = null;
        $deviceTraffic['DeviceTrafficStatus']['IN'] = array();
        $deviceTraffic['DeviceTrafficStatus']['OUT'] = array();
        if (isset($userWithAccessInDevice)) {
            foreach ($userWithAccessInDevice as $user) {
                $userTrafficType = $accessController->getUserLastTraffic($user['user_id'], $trafficTable);
                if (isset($userTrafficType) && $userTrafficType['traffic_type_id'] == 1) {
                    $user['user']['trafficDate'] = $userTrafficType['datetime'];
                    array_push($deviceTraffic['DeviceTrafficStatus']['IN'], $user['user']);
                } else {
                    if (isset($userTrafficType['datetime'])) {
                        $user['user']['trafficDate'] = $userTrafficType['datetime'];
                    } else {
                        $user['user']['trafficDate'] = null;
                    }
                    array_push($deviceTraffic['DeviceTrafficStatus']['OUT'], $user['user']);
                }
            }
        } else {
            $deviceTraffic = null;
        }
        return $deviceTraffic;
    }


    /**
     * @api {post} /Device/configureDevice configure a device in  the server
     * @apiName configureDevice
     * @apiGroup Device
     *
     * @apiParamExample {json} Request-Example:
     *
     * {
     *  "ReaxiumParameters": {
     *      "ReaxiumDevice": {
     *          "device_id": "1"
     *          "device_token": "h3kj45h3k4h53kjhg43hg53hjg45345gkjhgd90fg0d9gf8dgknj"
     *        }
     *      }
     *   }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *  "ReaxiumResponse": {
     *  "code": 0,
     *  "message": "DEVICE CONFIGURED SUCCESSFULLY",
     *  "object": {}
     * }
     *  }
     *
     *
     * @apiErrorExample Error-Response Device Not Found:
     * {
     *  "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "Device Not found",
     *      "object": []
     *      }
     *  }
     *
     * @apiErrorExample Error-Response Device Already Configured:
     * {
     *  "ReaxiumResponse": {
     *      "code": 07,
     *      "message": "Device already configured",
     *      "object": []
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {
     *  "ReaxiumResponse": {
     *       "code": 2,
     *       "message": "Invalid Parameters received, please checkout the api documentation",
     *       "object": []
     *      }
     *   }
     *
     *
     * @apiErrorExample Error-Response Invalid Json Object:
     * {
     *      "ReaxiumResponse": {
     *          "code": 2,
     *          "message": "Invalid Parameters received, please checkout the api documentation",
     *          "object": []
     *       }
     *      }
     */
    public function configureDevice()
    {
        Log::info("Configuring a device service");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumDevice"])) {
                    $deviceId = $jsonObject['ReaxiumParameters']["ReaxiumDevice"]['device_id'];
                    $deviceToken = $jsonObject['ReaxiumParameters']["ReaxiumDevice"]['device_token'];
                    $deviceSerial = $jsonObject['ReaxiumParameters']["ReaxiumDevice"]['device_serial'];

                    if (isset($deviceId) && isset($deviceToken)) {
                        $reaxiumDevice = $this->getDeviceInfo($deviceId);
                        if (isset($reaxiumDevice)) {
                            if ($reaxiumDevice[0]['device_serial'] == $deviceSerial) {

                                $fields = array('configured' => 1, 'device_token' => $deviceToken);
                                $conditions = array('device_id' => $deviceId);
                                $this->updateDevice($fields, $conditions);

                                $reaxiumDevice[0]['configured'] = true;
                                $reaxiumDevice[0]['device_token'] = $deviceToken;

                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                                $response['ReaxiumResponse']['message'] = 'Device configured successfully';
                                $response['ReaxiumResponse']['object'] = $reaxiumDevice;

                            } else {
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$DEVICE_ALREADY_CONFIGURED_CODE;
                                $response['ReaxiumResponse']['message'] = 'No matching serial number: '.$deviceSerial.' with device ID'.$deviceId;
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
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    /**
     * @api {post} /Device/synchronizeDeviceAccess synchronize a device in  with the server
     * @apiName synchronizeDeviceAccess
     * @apiGroup Device
     *
     * @apiParamExample {json} Request-Example:
     *
     * {
     *  "ReaxiumParameters": {
     *      "ReaxiumDevice": {
     *          "device_id": "1"
     *          "device_token": "h3kj45h3k4h53kjhg43hg53hjg45345gkjhgd90fg0d9gf8dgknj"
     *        }
     *      }
     *   }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *  "ReaxiumResponse": {
     *  "code": 0,
     *  "message": "device synchronized successfully",
     *  "object": [
     *      {
     * "access_id": 3,
     * "device_id": 1,
     * "user_access_data_id": 1,
     * "user_access_data": {
     * "user_access_data_id": 1,
     * "user_id": 17,
     * "access_type_id": 3,
     * "user_login_name": null,
     * "user_password": null,
     * "rfid_code": "45623",
     * "biometric_code": null,
     * "status_id": 3
     * }
     * },
     * {
     * "access_id": 1,
     * "device_id": 1,
     * "user_access_data_id": 2,
     * "user_access_data": {
     * "user_access_data_id": 2,
     * "user_id": 1,
     * "access_type_id": 1,
     * "user_login_name": "reaxiumUser",
     * "user_password": "reaxiumPassword",
     * "rfid_code": null,
     * "biometric_code": null,
     * "status_id": 1
     * }
     * },
     * {
     * "access_id": 4,
     * "device_id": 1,
     * "user_access_data_id": 3,
     * "user_access_data": {
     * "user_access_data_id": 3,
     * "user_id": 17,
     * "access_type_id": 2,
     * "user_login_name": null,
     * "user_password": null,
     * "rfid_code": null,
     * "biometric_code": "4792v",
     * "status_id": 1
     * }
     * }
     * ]
     *    }
     *  }
     *
     *
     * @apiErrorExample Error-Response Device Not Found:
     * {
     *  "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "Device Not found",
     *      "object": []
     *      }
     *  }
     *
     * @apiErrorExample Error-Response Device not Configured:
     * {
     *  "ReaxiumResponse": {
     *      "code": 8,
     *      "message": "Device not configured",
     *      "object": []
     *      }
     *  }
     *
     * @apiErrorExample Error-Response Device with invalid status:
     * {
     *  "ReaxiumResponse": {
     *      "code": 9,
     *      "message": "Device with invalid status in system",
     *      "object": []
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {
     *  "ReaxiumResponse": {
     *       "code": 2,
     *       "message": "Invalid Parameters received, please checkout the api documentation",
     *       "object": []
     *      }
     *   }
     *
     *
     * @apiErrorExample Error-Response Invalid Json Object:
     * {
     *      "ReaxiumResponse": {
     *          "code": 2,
     *          "message": "Invalid Parameters received, please checkout the api documentation",
     *          "object": []
     *       }
     *      }
     */
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
                                    $response['ReaxiumResponse']['object'] = $deviceAccessData;

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
                'UserAccessData.document_id',
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

    //TODO modificado filter device_serial
    public function allDeviceWithPagination()
    {

        Log::info("All Device information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        try {

            if (isset($jsonObject['ReaxiumParameters']["page"])) {

                $page = $jsonObject['ReaxiumParameters']["page"];
                $sortedBy = !isset($jsonObject['ReaxiumParameters']["sortedBy"]) ? 'device_name' : $jsonObject['ReaxiumParameters']["sortedBy"];
                $sortDir = !isset($jsonObject['ReaxiumParameters']["sortDir"]) ? 'desc' : $jsonObject['ReaxiumParameters']["sortDir"];
                $filter = !isset($jsonObject['ReaxiumParameters']["filter"]) ? '' : $jsonObject['ReaxiumParameters']["filter"];
                $limit = !isset($jsonObject['ReaxiumParameters']["limit"]) ? 10 : $jsonObject['ReaxiumParameters']["limit"];
                $business_id = !isset($jsonObject['ReaxiumParameters']['business_id']) ? null : $jsonObject['ReaxiumParameters']['business_id'];

                $devicesTable = TableRegistry::get("ReaxiumDevice");

                if (isset($business_id)) {

                    $deviceFound = $this->getDeviceFilterBusinessWithPaginate($business_id, $filter, $sortedBy, $sortDir);

                } else {
                    if (trim($filter) != '') {
                        $whereCondition = array(array('OR' => array(
                            array('ReaxiumDevice.device_id' => $filter),
                            array('device_name LIKE' => '%' . $filter . '%'),
                            array('device_description LIKE' => '%' . $filter . '%'),
                            array('device_serial LIKE' => '%' . $filter . '%'))));

                        $deviceFound = $devicesTable->find()
                            ->where($whereCondition)
                            ->andWhere(array('ReaxiumDevice.status_id' => 1))
                            ->contain(array("Applications", "Status", "Business"))->order(array($sortedBy . ' ' . $sortDir));
                    } else {
                        $deviceFound = $devicesTable->find()
                            ->where(array('ReaxiumDevice.status_id' => 1))
                            ->contain(array("Applications", "Status", "Business"))->order(array($sortedBy . ' ' . $sortDir));
                    }
                }


                $count = $deviceFound->count();
                $this->paginate = array('limit' => $limit, 'page' => $page);
                $deviceFound = $this->paginate($deviceFound);


                if ($deviceFound->count() > 0) {
                    $maxPages = floor((($count - 1) / $limit) + 1);
                    $routeFound = $deviceFound->toArray();
                    $response['ReaxiumResponse']['totalRecords'] = $count;
                    $response['ReaxiumResponse']['totalPages'] = $maxPages;
                    $response['ReaxiumResponse']['object'] = $routeFound;
                    $response = parent::setSuccessfulResponse($response);
                } else {
                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                    $response['ReaxiumResponse']['message'] = 'No Device found';
                }

            } else {
                $response = parent::seInvalidParametersMessage($response);
            }
        } catch (\Exception $e) {
            Log::info('Error loading device information: ');
            Log::info($e->getMessage());
            $response = parent::setInternalServiceError($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    /**
     * select device.device_id,
     * device.device_name,
     * device_description,
     * device.status_id,
     * device.configured,
     * device_token,
     * busi.business_id,
     * busi.business_name,
     * busi.business_id_number
     * from reaxium.reaxium_device as device
     * inner join reaxium.device_business as devbusi
     * on device.device_id = devbusi.device_id
     * inner join reaxium.business as busi
     * on devbusi.business_id = busi.business_id
     * and devbusi.business_id = 2
     * and devbusi.device_id = 24
     * @param $business_id
     * @return array|\Cake\ORM\Query|null
     */
    private function getDeviceFilterBusinessWithPaginate($business_id, $filter, $sortedBy, $sortDir)
    {

        $deviceTable = TableRegistry::get("ReaxiumDevice");
        $query = $deviceTable->find();
        $query->select(array(
            'ReaxiumDevice.device_id',
            'ReaxiumDevice.device_name',
            'ReaxiumDevice.device_description',
            'ReaxiumDevice.device_serial',  //agregado
            'ReaxiumDevice.status_id',
            'ReaxiumDevice.configured',
            'ReaxiumDevice.device_token',
            'business.business_id',
            'business.business_name',
            'business.business_id_number'
        ));
        $query->hydrate(false);
        $query->join(array(
            'devbusi' => array(
                'table' => 'device_business',
                'type' => 'INNER',
                'conditions' => 'ReaxiumDevice.device_id = devbusi.device_id'
            ),
            'business' => array(
                'table' => 'business',
                'type' => 'INNER',
                'conditions' => 'devbusi.business_id = business.business_id'
            )
        ));

        if (trim($filter) != "") {

            $whereCondition = array(array('OR' => array(
                array('ReaxiumDevice.device_id' => $filter),
                array('ReaxiumDevice.device_name LIKE' => '%' . $filter . '%'),
                array('ReaxiumDevice.device_description LIKE' => '%' . $filter . '%'),
                array('ReaxiumDevice.device_serial LIKE' => '%' . $filter . '%'))));

            $query->where($whereCondition);
            $query->andWhere(array('devbusi.business_id' => $business_id, 'ReaxiumDevice.status_id' => 1));
            $query->contain(array("Applications", "Status"))->order(array($sortedBy . ' ' . $sortDir));
        } else {
            $query->andWhere(array('devbusi.business_id' => $business_id, 'ReaxiumDevice.status_id' => 1));
            $query->contain(array("Applications", "Status"))->order(array($sortedBy . ' ' . $sortDir));
        }


        return $query;
    }

    public function associateADeviceWithRoute()
    {

        Log::info("All Route information with filter Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            try {

                $deviceId = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id'];
                $routeId = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['id_route']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['id_route'];
                $start_date = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['start_date']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['start_date'];
                $end_date = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['end_date']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['end_date'];

                if (isset($deviceId) && isset($routeId)) {

                    $deviceRouteTable = TableRegistry::get("DeviceRoutes");

                    $validate = $this->existRouteByDevice($deviceId, $routeId, $deviceRouteTable);

                    Log::info($validate);

                    if ($validate == 0) {

                        //Time::createFromTime
                        $deviceRouteData = $deviceRouteTable->newEntity();
                        $deviceRouteData->id_route = $routeId;
                        $deviceRouteData->device_id = $deviceId;
                        $deviceRouteData->start_date = $start_date;
                        $deviceRouteData->end_date = $end_date;

                        $deviceRouteData = $deviceRouteTable->save($deviceRouteData);

                        Log::info(json_encode($deviceRouteData));

                        $response = parent::setSuccessfulResponse($response);

                    } else {
                        $response['ReaxiumResponse']['code'] = '1';
                        $response['ReaxiumResponse']['message'] = 'The route is already associated with the device';
                        $response['ReaxiumResponse']['object'] = [];
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }

            } catch (\Exception $e) {
                Log::info($e->getMessage());
                $response = parent::setInternalServiceError($response);
            }

        } else {
            $response = parent::seInvalidParametersMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));

    }


    private function existRouteByDevice($device_id, $routeId, $deviceRouteTable)
    {

        $id_device_route = 0;

        $deviceRouteData = $deviceRouteTable->findByIdRouteAndDeviceId($routeId, $device_id);

        if ($deviceRouteData->count() > 0) {

            $deviceRouteData = $deviceRouteData->toArray();

            foreach ($deviceRouteData as $obj) {
                $id_device_route = $obj['id_device_routes'];
            }
        }

        return $id_device_route;
    }


    public function deleteRouteByDevice()
    {

        Log::info("deleting  Route relation with device running");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $id_device_route = null;

        try {
            if (parent::validReaxiumJsonHeader($jsonObject)) {

                $id_device_route = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['id_device_routes']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['id_device_routes'];
                $deviceRouteTable = TableRegistry::get('DeviceRoutes');
                $whereCondition = array('id_device_routes' => $id_device_route);
                $deviceRouteTable->deleteAll($whereCondition);

                $response = parent::setSuccessfulDelete($response);


            } else {
                $response = parent::seInvalidParametersMessage($response);
            }
        } catch (\Exception $e) {
            Log::info("Error deleting the route: " . $id_device_route . " error:" . $e->getMessage());
            $response = parent::setInternalServiceError($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));

    }

    //TODO actualizado para resolver parametro business_id
    public function getUsersByDevice()
    {

        Log::info("getUsersByDevice service");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            try {

                $device_id = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id'];
                $page = $jsonObject['ReaxiumParameters']['ReaxiumDevice']["page"];
                $sortedBy = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']["sortedBy"]) ? 'first_last_name' : $jsonObject['ReaxiumParameters']['ReaxiumDevice']["sortedBy"];
                $sortDir = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']["sortDir"]) ? 'desc' : $jsonObject['ReaxiumParameters']['ReaxiumDevice']["sortDir"];
                $filter = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']["filter"]) ? '' : $jsonObject['ReaxiumParameters']['ReaxiumDevice']["filter"];
                $limit = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']["limit"]) ? 10 : $jsonObject['ReaxiumParameters']['ReaxiumDevice']["limit"];
                $business_id = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['business_id']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['business_id'];


                if (isset($device_id)) {

                    if (isset($business_id)) {

                        $reaxiumDevice = $this->getDeviceFilterBusiness($device_id, $business_id);
                    } else {
                        $reaxiumDevice = $this->getDeviceInfo($device_id);
                    }

                    if (isset($reaxiumDevice)) {

                        if ($reaxiumDevice[0]['status_id'] == 1) {

                            if ($reaxiumDevice[0]['configured'] == 1) {

                                if (isset($business_id)) {
                                    $deviceAccessData = $this->getDeviceAccessDataUsersFilterBusiness($device_id, $filter, $sortedBy, $sortDir, $business_id);
                                } else {
                                    $deviceAccessData = $this->getDeviceAccessDataUsers($device_id, $filter, $sortedBy, $sortDir);
                                }

                                $count = $deviceAccessData->count();
                                $this->paginate = array('limit' => $limit, 'page' => $page);
                                $deviceAccessFound = $this->paginate($deviceAccessData);


                                if ($deviceAccessFound->count() > 0) {

                                    $maxPages = floor((($count - 1) / $limit) + 1);
                                    $deviceAccessFound = $deviceAccessFound->toArray();
                                    $response['ReaxiumResponse']['totalRecords'] = $count;
                                    $response['ReaxiumResponse']['totalPages'] = $maxPages;
                                    $response['ReaxiumResponse']['object'] = $deviceAccessFound;
                                    $response = parent::setSuccessfulResponse($response);
                                } else {
                                    $response['ReaxiumResponse']['code'] = '99';
                                    $response['ReaxiumResponse']['message'] = 'Devices has user relation';
                                }

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
                        $response['ReaxiumResponse']['message'] = 'No device found with the id: ' . $device_id;

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

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    private function getDeviceAccessDataUsers($deviceId, $filter, $sortedBy, $sortDir)
    {

        $userAccessControlTable = TableRegistry::get("UserAccessControl");

        if ($filter != "") {

            $whereCondition = array(array('OR' => array(
                array('first_name LIKE' => '%' . $filter . '%'),
                array('first_last_name LIKE' => '%' . $filter . '%'),
                array('document_id LIKE' => '%' . $filter . '%')
            )));

            $userAccessControl = $userAccessControlTable->find('All',
                array('fields' => array('UserAccessData.user_id',
                    'UserAccessData.user_access_data_id',
                    'Users.first_name',
                    'Users.second_name',
                    'Users.first_last_name',
                    'Users.user_photo',
                    'Users.birthdate',
                    'Users.document_id',
                    'Users.fingerprint',
                    'Status.status_name',
                    'AccessType.access_type_name')))
                ->where($whereCondition)
                ->andWhere(array('UserAccessControl.device_id' => $deviceId, 'Users.status_id' => '1'))
                ->contain(array('UserAccessData' => array('AccessType', 'Users' => array('UserType', 'Business', 'Status'))))
                ->order(array($sortedBy . ' ' . $sortDir));

        } else {

            $userAccessControl = $userAccessControlTable->find('All',
                array('fields' => array('UserAccessData.user_id',
                    'UserAccessData.user_access_data_id',
                    'Users.first_name',
                    'Users.second_name',
                    'Users.first_last_name',
                    'Users.user_photo',
                    'Users.birthdate',
                    'Users.document_id',
                    'Users.fingerprint',
                    'Status.status_name',
                    'AccessType.access_type_name')))
                ->where(array('UserAccessControl.device_id' => $deviceId, 'Users.status_id' => '1'))
                ->contain(array('UserAccessData' => array('AccessType', 'Users' => array('UserType', 'Business', 'Status'))))
                ->order(array($sortedBy . ' ' . $sortDir));
        }

        return $userAccessControl;
    }


    private function getDeviceAccessDataUsersFilterBusiness($deviceId, $filter, $sortedBy, $sortDir, $business_id)
    {

        $userAccessControlTable = TableRegistry::get("UserAccessControl");

        if ($filter != "") {

            $whereCondition = array(array('OR' => array(
                array('first_name LIKE' => '%' . $filter . '%'),
                array('first_last_name LIKE' => '%' . $filter . '%'),
                array('document_id LIKE' => '%' . $filter . '%')
            )));

            $userAccessControl = $userAccessControlTable->find('All',
                array('fields' => array('UserAccessData.user_id',
                    'UserAccessData.user_access_data_id',
                    'Users.first_name',
                    'Users.second_name',
                    'Users.first_last_name',
                    'Users.user_photo',
                    'Users.birthdate',
                    'Users.document_id',
                    'Users.fingerprint',
                    'Status.status_name',
                    'AccessType.access_type_name')))
                ->where($whereCondition)
                ->andWhere(array('UserAccessControl.device_id' => $deviceId, 'Users.status_id' => '1', 'Users.business_id' => $business_id))
                ->contain(array('UserAccessData' => array('AccessType', 'Users' => array('UserType', 'Business', 'Status'))))
                ->order(array($sortedBy . ' ' . $sortDir));

        } else {

            $userAccessControl = $userAccessControlTable->find('All',
                array('fields' => array('UserAccessData.user_id',
                    'UserAccessData.user_access_data_id',
                    'Users.first_name',
                    'Users.second_name',
                    'Users.first_last_name',
                    'Users.user_photo',
                    'Users.birthdate',
                    'Users.document_id',
                    'Users.fingerprint',
                    'Status.status_name',
                    'AccessType.access_type_name')))
                ->where(array('UserAccessControl.device_id' => $deviceId, 'Users.status_id' => '1', 'Users.business_id' => $business_id))
                ->contain(array('UserAccessData' => array('AccessType', 'Users' => array('UserType', 'Business', 'Status'))))
                ->order(array($sortedBy . ' ' . $sortDir));
        }

        return $userAccessControl;
    }


    public function deleteUserAccessDevice()
    {

        Log::info("deleting  Route relation with device running");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            try {
                $device_id = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id'];
                $user_access_data_id = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['user_access_data_id']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['user_access_data_id'];

                if (isset($device_id) && isset($user_access_data_id)) {
                    $userAccessControlTable = TableRegistry::get("UsersAccessControl");
                    $whereCondition = array('device_id' => $device_id, 'user_access_data_id' => $user_access_data_id);
                    $userAccessControlTable->deleteAll($whereCondition);
                    $response = parent::setSuccessfulDelete($response);

                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }

            } catch (\Exception $e) {
                Log::info("Error deleting the user: " . $user_access_data_id . " error:" . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }

        } else {
            $response = parent::seInvalidParametersMessage($response);
        }


        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    public function getBusinessByDevice()
    {

        Log::info("All Business information the Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();


        if (parent::validReaxiumJsonHeader($jsonObject)) {

            try {

                $device_id = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id'];
                $page = $jsonObject['ReaxiumParameters']['ReaxiumDevice']["page"];
                $sortedBy = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']["sortedBy"]) ? 'Business.business_name' : $jsonObject['ReaxiumParameters']['ReaxiumDevice']["sortedBy"];
                $sortDir = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']["sortDir"]) ? 'desc' : $jsonObject['ReaxiumParameters']['ReaxiumDevice']["sortDir"];
                $filter = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']["filter"]) ? '' : $jsonObject['ReaxiumParameters']['ReaxiumDevice']["filter"];
                $limit = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']["limit"]) ? 10 : $jsonObject['ReaxiumParameters']['ReaxiumDevice']["limit"];


                if (isset($device_id)) {

                    $reaxiumDevice = $this->getDeviceInfo($device_id);

                    if (isset($reaxiumDevice)) {

                        if ($reaxiumDevice[0]['status_id'] == 1) {

                            if ($reaxiumDevice[0]['configured'] == 1) {

                                $businessDeviceData = $this->getBusinessDeviceData($device_id, $filter, $sortedBy, $sortDir);

                                $count = $businessDeviceData->count();
                                $this->paginate = array('limit' => $limit, 'page' => $page);
                                $businessDeviceFound = $this->paginate($businessDeviceData);

                                if ($businessDeviceFound->count() > 0) {

                                    $maxPages = floor((($count - 1) / $limit) + 1);
                                    $deviceAccessFound = $businessDeviceFound->toArray();
                                    $response['ReaxiumResponse']['totalRecords'] = $count;
                                    $response['ReaxiumResponse']['totalPages'] = $maxPages;
                                    $response['ReaxiumResponse']['object'] = $deviceAccessFound;
                                    $response = parent::setSuccessfulResponse($response);
                                } else {
                                    $response['ReaxiumResponse']['code'] = '99';
                                    $response['ReaxiumResponse']['message'] = 'Devices no has business relation';
                                }

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
                        $response['ReaxiumResponse']['message'] = 'No device found with the id: ' . $device_id;
                    }

                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error: " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::seInvalidParametersMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    private function getBusinessDeviceData($deviceId, $filter, $sortedBy, $sortDir)
    {

        $businessDeviceTable = TableRegistry::get("DeviceBusiness");

        if (trim($filter) != "") {

            $whereCondition = array(array('OR' => array(
                array('business_name LIKE' => '%' . $filter . '%'),
                array('business_id_number LIKE' => '%' . $filter . '%')
            )));

            $businessDeviceData = $businessDeviceTable->find()
                ->where($whereCondition)
                ->andWhere(array('device_id' => $deviceId))
                ->contain(array('Business'))
                ->order(array($sortedBy . ' ' . $sortDir));

        } else {
            $businessDeviceData = $businessDeviceTable->find()
                ->where(array('device_id' => $deviceId))
                ->contain(array('Business'))
                ->order(array($sortedBy . ' ' . $sortDir));
        }

        return $businessDeviceData;
    }


    public function deleteBusinessDevice()
    {

        Log::info("All delete business of device the Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            try {

                $deviceId = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id'];
                $businessId = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['business_id']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['business_id'];

                if (isset($deviceId) && isset($businessId)) {

                    $businessDeviceTable = TableRegistry::get("DeviceBusiness");
                    $whereCondition = array(array('device_id' => $deviceId, 'business_id' => $businessId));
                    $businessDeviceTable->deleteAll($whereCondition);
                    $response = parent::setSuccessfulDelete($response);
                } else {
                    $response = parent::setInvalidJsonMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error: " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    /**
     * @api {post} /Device/createDevice Create a new Reaxium Device
     * @apiName createDevice
     * @apiGroup Device
     *
     * @apiParamExample {json} Request-Example:
     *   {
     *      "ReaxiumParameters": {
     *      "ReaxiumDevice": {
     *          "device_name": "Another Device",
     *          "device_description": "Device working for Florida School"
     *          }
     *      }
     *  }
     *
     *
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 200 OK
     *    {
     *     "ReaxiumResponse": {
     *       "code": 0,
     *       "message": "SAVED SUCCESSFUL",
     *       "object": {
     *          "device_name": "Another Device",
     *          "device_description": "Device working for Florida School",
     *          "device_id": 18
     *          }
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response: Device already exist
     *  {
     *      "ReaxiumResponse": {
     *          "code": 101,
     *          "message": "Device name already exist in the system",
     *          "object": []
     *          }
     *      }
     *
     */

    public function createDevice()
    {

        Log::info("Create a new Device service has been invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {

            try {

                $device_name = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_name']) ?
                    null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_name'];

                $device_desc = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_description']) ?
                    null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_description'];

                $device_serial = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_serial']) ?
                    null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_serial'];

                $business = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['business']) ?
                    null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['business'];

                $device_id = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id']) ?
                    null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id'];


                if (isset($device_name) && isset($device_desc) && isset($business) && isset($device_serial)) {

                    $deviceTable = TableRegistry::get('ReaxiumDevice');
                    $deviceRelBusinessTable = TableRegistry::get('DeviceBusiness');

                    //creas un device nuevo

                    if ($device_id == null) {

                        Log::info('Creacion Nuevo dispositivo');

                        $validateSerial = $this->checkExitsSerialDevice($device_serial, $deviceTable);

                        if ($validateSerial) {

                            $result = $this->createANewDevice($device_name, $device_desc, $device_serial, $deviceTable);
                            Log::info('Resultado: ' . $result);

                            if (isset($result)) {

                                $relationResult = $this->DeviceRelationShipBusiness($result['device_id'], $business, $deviceRelBusinessTable);

                                if ($relationResult) {
                                    $response = parent::setSuccessfulSave($response);
                                    $response['ReaxiumResponse']['object'] = $result;
                                } else {
                                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                                    $response['ReaxiumResponse']['message'] = 'There was a problem trying to save the device, please try later';
                                }
                            } else {
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                                $response['ReaxiumResponse']['message'] = 'There was a problem trying to save the device, please try later';
                            }
                        } else {
                            $response['ReaxiumResponse']['code'] = "1";
                            $response['ReaxiumResponse']['message'] = 'The serial ' . $device_serial . ' device is already registered in the system';
                        }


                    } else {
                        /*delete business and create now*/
                        Log::info("Mode edition");
                        $deviceRelBusinessTable->deleteAll(array('device_id' => $device_id));

                        $relationResult = $this->DeviceRelationShipBusiness($device_id, $business, $deviceRelBusinessTable);

                        if ($relationResult) {
                            $response = parent::setSuccessfulSave($response);
                            $response['ReaxiumResponse']['object'] = $relationResult;
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                            $response['ReaxiumResponse']['message'] = 'There was a problem trying to save the device, please try later';
                        }
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error Saving the Device " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     * @param $serial_number
     * @param $reaxiumDeviceTable
     * @return bool
     */
    private function checkExitsSerialDevice($serial_number, $reaxiumDeviceTable)
    {

        $validate = true;
        $reaxiumDeviceFound = $reaxiumDeviceTable->findByDeviceSerial($serial_number);

        if ($reaxiumDeviceFound->count() > 0) {
            $validate = false;
        }

        return $validate;
    }

    /**
     * @param $device_name
     * @param $device_desc
     * @param $deviceTable
     * @return mixed
     */
    private function createANewDevice($device_name, $device_desc, $device_serial, $deviceTable)
    {

        $device = $deviceTable->newEntity();
        $device->device_name = $device_name;
        $device->device_description = $device_desc;
        $device->device_serial = $device_serial;

        $result = $deviceTable->save($device);

        return $result;
    }

    /**
     * @param $deviceId
     * @param $businessArray
     * @param $deviceRelBussiTable
     * @return bool
     */
    private function DeviceRelationShipBusiness($deviceId, $businessArray, $deviceRelBussiTable)
    {

        $arrayDeviceRelationBusiness = [];
        $validate = true;

        foreach ($businessArray as $business) {
            array_push($arrayDeviceRelationBusiness, array('device_id' => $deviceId, 'business_id' => $business['business_id']));
        }

        $deviceRelBusinessData = $deviceRelBussiTable->newEntities($arrayDeviceRelationBusiness);

        foreach ($deviceRelBusinessData as $entity) {

            if (!$deviceRelBussiTable->save($entity)) {
                $validate = false;
                break;
            }
        }

        return $validate;
    }


    public function getLocationDevice(){

        Log::info("Traking device service call");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if(parent::validReaxiumJsonHeader($jsonObject)){

            try{

                $device_id = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id'])? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id'];

                if($device_id){

                    $deviceLocationTable = TableRegistry::get("DeviceLocation");
                    $deviceLocationData = $deviceLocationTable->findByDeviceId($device_id);

                    if($deviceLocationData->count()>0){
                        $deviceLocationData = $deviceLocationData->toArray();

                    }else{
                        $deviceLocationData = null;
                    }

                    if(isset($deviceLocationData)){
                        $response = parent::setSuccessfulResponse($response);
                        $response['ReaxiumResponse']['object'] = $deviceLocationData;
                    }else{
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                        $response['ReaxiumResponse']['message'] = 'I not been found this device in the system to track';
                        $response['ReaxiumResponse']['object'] = [];
                    }


                }else{
                    $response = parent::setInvalidJsonMessage($response);
                }
             }
            catch (\Exception $e){
                Log::info("Error Traking the Device " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        }else{
            $response = parent::setInvalidJsonMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

}