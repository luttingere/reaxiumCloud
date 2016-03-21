<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 18/03/2016
 * Time: 02:56 PM
 */

namespace App\Controller;


use Cake\Core\Exception\Exception;
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
            "ReaxiumDevice": {
                "device_id": "1"
                }
              }
            }
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
            {"ReaxiumResponse": {
                "code": 404,
                "message": "Device Not found",
                "object": []
                }
             }
     *
     *
        @apiErrorExample Error-Response Invalid Parameters:
        {"ReaxiumResponse": {
            "code": 2,
            "message": "Invalid Parameters received, please checkout the api documentation",
            "object": []
            }
          }
     *
     *
         @apiErrorExample Error-Response Invalid Json Object:
        {"ReaxiumResponse": {
            "code": 2,
            "message": "Invalid Parameters received, please checkout the api documentation",
            "object": []
            }
         }
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
                        if(isset($device)){
                            $response['ReaxiumResponse']['object'] = $device;
                            $response = parent::setSuccessfulResponse($response);
                        }else{
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
        $device = $device->findByDeviceId($deviceId)->contain(array("Status","Applications"));
        if($device->count() > 0){
            $device = $device->toArray();
        }else{
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
        {"ReaxiumResponse": {
             "code": 404,
             "message": "No Deices Found",
             "object": []
            }
         }
     *
     */
    public function allDevicesInfo()
    {
        Log::info("All Device information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
            try {
                $devices = $this->getAllDevices();
                if(isset($devices)){
                    $response['ReaxiumResponse']['object'] = $devices;
                }else{
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
    private function getAllDevices(){
        $devices = TableRegistry::get("ReaxiumDevice");
        $devices = $devices->find()->contain(array("Status","Applications"));
        if($devices->count() > 0){
            $devices = $devices->toArray();
        }else{
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
            "ReaxiumDevice": {
                "device_name": "Another Device",
                "device_description": "Device working for Florida School"
                }
            }
         }
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
                "ReaxiumResponse": {
                    "code": 0,
                    "message": "SAVED SUCCESSFUL",
                    "object": {
                        "device_name": "Another Device",
                        "device_description": "Device working for Florida School",
                        "device_id": 18
                    }
                }
            }
     *
     *
     * @apiErrorExample Error-Response: Device already exist
     *  {
                "ReaxiumResponse": {
                    "code": 101,
                    "message": "Device name already exist in the system",
                    "object": []
                }
            }
     *
     */
    public function createDevice(){
        Log::info("Create a new Device service has been invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: '.json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumDevice"])) {
                    $result = $this->createANewDevice($jsonObject['ReaxiumParameters']);
                    Log::info('Resultado: '. $result);
                    if($result){
                        $response = parent::setSuccessfulSave($response);
                        $response['ReaxiumResponse']['object'] = $result;
                    }else{
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
        }else{
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
    private function createANewDevice($deviceJSON){
        $this->loadModel("ReaxiumDevice");
        $device = $this->ReaxiumDevice->newEntity();
        $device = $this->ReaxiumDevice->patchEntity($device, $deviceJSON);
        $result = $this->ReaxiumDevice->save($device);
        return $result;
    }


}