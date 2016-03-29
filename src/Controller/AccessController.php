<?php
/**
 * Created by PhpStorm.
 * User: SinAsignari54GB1TB
 * Date: 22/03/2016
 * Time: 03:35 AM
 */

namespace App\Controller;

use Cake\Core\Exception\Exception;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;


class AccessController extends ReaxiumAPIController
{


    /**
     * @api {post} /Access/createDeviceAccess Create A Security Access For A Reaxium Device
     * @apiName createDeviceAccess
     * @apiGroup AccessControl
     *
     * @apiParamExample {json} Request-Example:
     *   {
     *    "ReaxiumParameters": {
     *      "DeviceAccessControl": {
     *          "device_id": "1",
     *          "application_id": "2",
     *          "access_type_id": "1"
     *           }
     *          }
     *         }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     * "ReaxiumResponse": {
     *      "code": 0,
     *      "message": "SAVED SUCCESSFUL",
     *      "object": {
     *          "access_device_control_id": "1"
     *          "device_id": "1",
     *          "application_id": "2",
     *          "access_type_id": "1",
     *          "status_id": "1",
     *          }
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response: Device Access already exists
     *  {
     *      "ReaxiumResponse": {
     *          "code": 101,
     *          "message": "Device access already exist in the system",
     *          "object": []
     *          }
     *      }
     *
     */
    public function createDeviceAccess()
    {
        Log::info("Create a new Device access service has been invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["DeviceAccessControl"])) {
                    $result = $this->createANewDeviceAccess($jsonObject['ReaxiumParameters']);
                    if ($result) {
                        $response = parent::setSuccessfulSave($response);
                        $response['ReaxiumResponse']['object'] = $result;
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                        $response['ReaxiumResponse']['message'] = 'There was a problem trying to save the device access, please try later';
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error Saving the Device " . $e->getMessage());
                $response = $this->setInternalServiceError($response);
                $response['ReaxiumResponse']['errorInfo'] = 'Perhaps your device access already exists in the system or u are trying to create an access with invalid data';
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     *
     * Register a new device access in the system
     *
     * @param $deviceAccessJSON
     * @return created device access
     */
    private function createANewDeviceAccess($deviceAccessJSON)
    {
        $this->loadModel("DeviceAccessControl");
        $deviceAccess = $this->DeviceAccessControl->newEntity();
        $deviceAccess = $this->DeviceAccessControl->patchEntity($deviceAccess, $deviceAccessJSON);
        $result = $this->DeviceAccessControl->save($deviceAccess);
        return $result;
    }

    /**
     * @api {post} /Access/checkDeviceInformation Login to the system with a Device
     * @apiName checkDeviceInformation
     * @apiGroup AccessControl
     *
     * @apiParamExample {json} Request-Example:
     *
     * {"ReaxiumParameters": {
     *    "ReaxiumParameters": {
     *      "DeviceAccessControl": {
     *          "device_id": "1",
     *          "application_id": "2",
     *          "access_type_id": "1",
     *           }
     *          }
     *         }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {"ReaxiumResponse": {
     *              "code": "0",
     *              "message": "ACCESS GRANTED",
     *               "object": []
     *                }
     *              }
     *
     *
     * @apiErrorExample Error-Response Invalid Access:
     * {
     *  "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "Invalid Access",
     *      "object": []
     *       }
     *      }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     *
     *  {
     *      "ReaxiumResponse": {
     *          "code": 2,
     *          "message": "Invalid Parameters received, please checkout the api documentation",
     *          "object": []
     *          }
     *      }
     *
     * @apiErrorExample Error-Response Invalid JSON Object:
     *
     *  {
     *      "ReaxiumResponse": {
     *          "code": 3,
     *          "message": "Invalid Json Object",
     *          "object": []
     *          }
     *      }
     */
    public function checkDeviceInformation()
    {
        Log::info("Device access information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["DeviceAccessControl"])) {
                    $this->loadModel("DeviceAccessControl");
                    $access = $this->DeviceAccessControl->newEntity();
                    $access = $this->DeviceAccessControl->patchEntity($access, $jsonObject['ReaxiumParameters']);
                    if (isset($access->device_id) && isset($access->application_id) && isset($access->access_type_id)) {
                        $arrayOfConditions = array('ReaxiumDevice.device_id' => $access->device_id, 'Applications.application_id' => $access->application_id, 'AccessType.access_type_id' => $access->access_type_id);
                        $accessFound = $this->getDeviceAccessInfo($arrayOfConditions);
                        if (isset($accessFound)) {
                            $response['ReaxiumResponse']['object'] = $accessFound;
                            $response = parent::setSuccessAccess($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'Invalid Access';
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
     * obtain all de information related to an specific device Access
     *
     * @param $arrayOfConditions
     * @return \Cake\ORM\Table  --Device Access information
     */
    private function getDeviceAccessInfo($arrayOfConditions)
    {
        $access = TableRegistry::get("DeviceAccessControl");
        $access = $access->find()->where($arrayOfConditions)->contain(array("Status", "Applications", "ReaxiumDevice", "AccessType"));
        if ($access->count() > 0) {
            $access = $access->toArray();
        } else {
            $access = null;
        }

        return $access;
    }


    /**
     *
     * Store an intent of access to the platform
     *
     * @param $userId
     * @param $traffic_type
     * @param $access_id
     * @param $deviceId
     * @param $trafficInfo
     * @return mixed
     */
    private function registerTraffic($userId, $traffic_type, $access_id, $deviceId, $trafficInfo)
    {
        $trafficTable = TableRegistry::get("Traffic");
        $trafficRecord = $trafficTable->newEntity();
        $trafficRecord->traffic_type_id = $traffic_type;
        $trafficRecord->user_id = $userId;
        $trafficRecord->access_id = $access_id;
        $trafficRecord->device_id = $deviceId;
        $trafficRecord->traffic_info = $trafficInfo;
        $trafficTable->save($trafficRecord);
    }

    /**
     * @param $userId
     * @param $trafficTable
     * @return null
     */
    public function getUserLastTraffic($userId, $trafficTable)
    {
        $lastTraffic = $trafficTable->find('all', array('conditions' => array('user_id' => $userId)))->order('datetime DESC');
        if ($lastTraffic->count() > 0) {
            $lastTraffic = $lastTraffic->toArray();
            $lastTraffic = $lastTraffic[0];
        } else {
            $lastTraffic = NULL;
        }
        return $lastTraffic;
    }


    /**
     * @api {post} /Access/createUserAccess Create A Security  Access For A User
     * @apiName createUserAccess
     * @apiGroup AccessControl
     *
     * @apiParamExample {json} Request-Example:
     *   {
     *    "ReaxiumParameters": {
     *      "UserAccessControl": {
     *          "device_id": "1",
     *          "access_type_id": "1",
     *          "user_id":"1",
     *          "user_login_name":"reaxiumUser",
     *          "user_password":"reaxiumPassword"
     *           }
     *          }
     *         }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *      "ReaxiumResponse": {
     *          "code": 0,
     *          "message": "SAVED SUCCESSFUL",
     *          "object": {
     *          "device_id": 1,
     *          "user_id": 1,
     *          "access_type_id": 1,
     *          "user_login_name": "reaxiumUser",
     *          "user_password": "reaxiumPassword",
     *          "access_id": 1
     *          }
     *       }
     *  }
     *
     *
     * @apiErrorExample Error-Response: User Access already exists
     *  {
     *      "ReaxiumResponse": {
     *          "code": 101,
     *          "message": "User access already exist in the system",
     *          "object": []
     *          }
     *      }
     *
     */
    public function createUserAccess()
    {
        Log::info("Create a new User access service has been invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["UserAccessControl"])) {
                    $result = $this->createANewUserAccess($jsonObject['ReaxiumParameters']);
                    if ($result) {
                        $response = parent::setSuccessfulSave($response);
                        $response['ReaxiumResponse']['object'] = $result;
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                        $response['ReaxiumResponse']['message'] = 'There was a problem trying to save the user access, please try later';
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error Saving the Device " . $e->getMessage());
                $response = $this->setInternalServiceError($response);
                $response['ReaxiumResponse']['errorInfo'] = 'Perhaps your user access already exists in the system or u are trying to create an access with invalid data';
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     *
     * Register a new device access in the system
     *
     * @param $userAccessJSON
     * @return created device access
     */
    private function createANewUserAccess($userAccessJSON)
    {
        $this->loadModel("UserAccessControl");
        $userAccess = $this->UserAccessControl->newEntity();
        $userAccess = $this->UserAccessControl->patchEntity($userAccess, $userAccessJSON);
        $result = $this->UserAccessControl->save($userAccess);
        return $result;
    }

    /**
     * @api {post} /Access/checkUserAccessInformation Login to the system with a User
     * @apiName checkDeviceInformation
     * @apiGroup AccessControl
     *
     * @apiParamExample {json} Request-Example:
     *
     *    "ReaxiumParameters": {
     *      "UserAccessControl": {
     *          "device_id": "1",
     *          "access_type_id": "1",
     *          "user_login_name":"reaxiumUser",
     *          "user_password":"reaxiumPassword"
     *           }
     *          }
     *         }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {"ReaxiumResponse": {
     *              "code": "0",
     *              "message": "ACCESS GRANTED",
     *               "object": []
     *                }
     *              }
     *
     *
     * @apiErrorExample Error-Response Invalid Access:
     * {
     *  "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "Invalid Access",
     *      "object": []
     *       }
     *      }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     *
     *  {
     *      "ReaxiumResponse": {
     *          "code": 2,
     *          "message": "Invalid Parameters received, please checkout the api documentation",
     *          "object": []
     *          }
     *      }
     *
     * @apiErrorExample Error-Response Invalid JSON Object:
     *
     *  {
     *      "ReaxiumResponse": {
     *          "code": 3,
     *          "message": "Invalid Json Object",
     *          "object": []
     *          }
     *      }
     */
    public function checkUserAccessInformation()
    {
        Log::info("User access information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["UserAccessControl"])) {
                    $this->loadModel("UserAccessControl");
                    $access = $this->UserAccessControl->newEntity();
                    $access = $this->UserAccessControl->patchEntity($access, $jsonObject['ReaxiumParameters']);
                    if (isset($access->device_id) && isset($access->access_type_id)) {

                        $arrayOfConditions = array('ReaxiumDevice.device_id' => $access->device_id,
                            'AccessType.access_type_id' => $access->access_type_id,
                            'user_login_name' => $access->user_login_name,
                            'user_password' => $access->user_password);
                        $accessFound = $this->getUserAccessInfo($arrayOfConditions);
                        if (isset($accessFound)) {
                            Log::info($accessFound);
                            $trafficTable = TableRegistry::get("Traffic");
                            $userId = $accessFound[0]['user_id'];
                            $accessId = $accessFound[0]['access_id'];
                            $deviceId = $accessFound[0]['device_id'];
                            $lastTraffic = $this->getUserLastTraffic($userId, $trafficTable);
                            Log::info("lastTraffic: ".$lastTraffic);
                            if ($lastTraffic == NULL || ($lastTraffic['traffic_type_id'] == 2)) {
                                Log::info("El usuario: " . $userId . " No tiene traficos el dia de hoy");
                                $this->registerTraffic($userId, 1, $accessId, $deviceId,ReaxiumApiMessages::$SUCCESS_ACCESS);
                            } else {
                                $this->registerTraffic($userId, 2, $accessId, $deviceId,ReaxiumApiMessages::$SUCCESS_ACCESS);
                            }
                            $response['ReaxiumResponse']['object'] = $accessFound;
                            $response = parent::setSuccessAccess($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'Invalid Access';
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
     * obtain all de information related to an specific user Access
     *
     * @param $arrayOfConditions
     * @return \Cake\ORM\Table  --User Access information
     */
    private function getUserAccessInfo($arrayOfConditions)
    {
        $access = TableRegistry::get("UserAccessControl");
        $access = $access->find()->where($arrayOfConditions)->contain(array("Status", "Users", "ReaxiumDevice", "AccessType"));
        if ($access->count() > 0) {
            $access = $access->toArray();
        } else {
            $access = null;
        }
        return $access;
    }


}