<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 18/03/2016
 * Time: 02:56 PM
 */

namespace App\Controller;


use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;


class DeviceController extends ReaxiumAPIController
{

    /**
     * @api {post} /Device/deviceInfo getDeviceInformation
     * @apiName deviceInfo
     * @apiGroup Device
     *
     * @apiParamExample {json} Request-Example:
     *
     * {"ReaxiumParameters": {
     * "ReaxiumDevice": {
     * "device_id": "1"
     * }
     * }
     * }
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
     * {"ReaxiumResponse": {
     * "code": 404,
     * "message": "Device Not found",
     * "object": []
     * }
     * }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {"ReaxiumResponse": {
     * "code": 2,
     * "message": "Invalid Parameters received, please checkout the api documentation",
     * "object": []
     * }
     * }
     *
     *
     * @apiErrorExample Error-Response Invalid Json Object:
     * {"ReaxiumResponse": {
     * "code": 2,
     * "message": "Invalid Parameters received, please checkout the api documentation",
     * "object": []
     * }
     * }
     */
    public function deviceInfo()
    {
        Log::info("Device information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumDevice"])) {
                    $this->loadModel("ReaxiumDevice");
                    $device = $this->ReaxiumDevice->newEntity();
                    $device = $this->ReaxiumDevice->patchEntity($device, $jsonObject['ReaxiumParameters']);
                    if (isset($device->device_id)) {
                        $device = $this->getDeviceInfo($device->device_id);
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
                $response = parent::seInvalidParametersMessage($response);
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
        $device = $device->find()->where(array('ReaxiumDevice.device_id' => $deviceId))->contain(array("Status", "Applications"));
        if ($device->count() > 0) {
            $device = $device->toArray();
        } else {
            $device = null;
        }

        return $device;
    }


    /**
     * @api {get} /Device/allDeviceInfo getAllDevicesInSystem
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
     * {"ReaxiumResponse": {
     * "code": 404,
     * "message": "No Deices Found",
     * "object": []
     * }
     * }
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
     * @api {post} /Device/createDevice CreateNewDevice
     * @apiName createDevice
     * @apiGroup Device
     *
     * @apiParamExample {json} Request-Example:
     *   {"ReaxiumParameters": {
     * "ReaxiumDevice": {
     * "device_name": "Another Device",
     * "device_description": "Device working for Florida School"
     * }
     * }
     * }
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     * "ReaxiumResponse": {
     * "code": 0,
     * "message": "SAVED SUCCESSFUL",
     * "object": {
     * "device_name": "Another Device",
     * "device_description": "Device working for Florida School",
     * "device_id": 18
     * }
     * }
     * }
     *
     *
     * @apiErrorExample Error-Response: Device already exist
     *  {
     * "ReaxiumResponse": {
     * "code": 101,
     * "message": "Device name already exist in the system",
     * "object": []
     * }
     * }
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
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumDevice"])) {
                    $result = $this->createANewDevice($jsonObject['ReaxiumParameters']);
                    Log::info('Resultado: ' . $result);
                    if ($result) {
                        $response = parent::setSuccessfulSave($response);
                        $response['ReaxiumResponse']['object'] = $result;
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                        $response['ReaxiumResponse']['message'] = 'There was a problem trying to save the device, please try later';
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error Saving the Device " . $e->getMessage());
                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                $response['ReaxiumResponse']['message'] = 'Device name already exist in the system';
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     *
     * Register a new device in the system
     *
     * @param $deviceJSON
     * @return created device
     */
    private function createANewDevice($deviceJSON)
    {
        $this->loadModel("ReaxiumDevice");
        $device = $this->ReaxiumDevice->newEntity();
        $device = $this->ReaxiumDevice->patchEntity($device, $deviceJSON);
        $result = $this->ReaxiumDevice->save($device);
        return $result;
    }


    /**
     * @api {post} /Device/deleteDevice DeleteADeviceFromSystem
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
        if ($associatedDevice->count() > 0) {
            $associatedDevice = $associatedDevice->toArray();
            Log::info("The device id: " . $associatedDevice[0]['device_id'] . "has an association and it will be deleted");
            $this->ApplicationsRelationship->deleteAll(array('device_id' => $associatedDevice[0]['device_id']));
        }
        $this->ReaxiumDevice->updateAll(array('status_id' => '3'), array('device_id' => $associatedDevice[0]['device_id']));
    }


    /**
     * @api {post} /Device/associateAnApplicationWithADevice AssociateAnApplicationWithADevice
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
     *   @apiErrorExample Error-Response:
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
     * @api {post} /Device/changeDeviceStatus ChangeTheStatusOfADevice
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


}