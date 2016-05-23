<?php
/**
 * Created by PhpStorm.
 * User: SinAsignari54GB1TB
 * Date: 22/03/2016
 * Time: 03:35 AM
 */

namespace App\Controller;

use App\Util\ReaxiumApiMessages;
use App\Util\ReaxiumUtil;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;


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
                            $userController = new UsersController();
                            $user = $userController->getUser($accessFound[0]['user_id']);
                            if ($user[0]['user_type_id'] != 1) {
                                $trafficTable = TableRegistry::get("Traffic");
                                $userId = $accessFound[0]['user_id'];
                                $accessId = $accessFound[0]['access_id'];
                                $deviceId = $accessFound[0]['device_id'];
                                $lastTraffic = $this->getUserLastTraffic($userId, $trafficTable);
                                Log::info("lastTraffic: " . $lastTraffic);
                                if ($lastTraffic == NULL || ($lastTraffic['traffic_type_id'] == 2)) {
                                    Log::info("El usuario: " . $userId . " No tiene traficos el dia de hoy");
                                    $this->registerTraffic($userId, 1, $accessId, $deviceId, ReaxiumApiMessages::$SUCCESS_ACCESS);
                                } else {
                                    $this->registerTraffic($userId, 2, $accessId, $deviceId, ReaxiumApiMessages::$SUCCESS_ACCESS);
                                }
                            }
                            $accessFound[0]['UserData'] = $user;
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
        $access = $access->find()->where($arrayOfConditions)->contain(array("Status", "ReaxiumDevice", "AccessType"));
        if ($access->count() > 0) {
            $access = $access->toArray();
        } else {
            $access = null;
        }
        return $access;
    }


    public function registerAUserAccess($userId, $deviceId, $accessTypeId, $trafficType, $trafficInfo)
    {
        $result = null;
        $accessId = $this->getAccessIdOfAnAccess($userId, $deviceId, $accessTypeId);
        if (isset($accessId)) {
            $trafficController = new TrafficController();
            $trafficSaved = $trafficController->registerATraffic($userId, $trafficType, $accessId, $deviceId, $trafficInfo);
            if ($trafficSaved) {
                $result = $trafficSaved;
            }
        }else{
            Log::info("The user id: ".$userId." dont have a valid access, access type id received: ".$accessTypeId);
        }
        return $result;
    }


    private function getAccessIdOfAnAccess($userId, $deviceId, $accessTypeId)
    {
        $result = null;
        $userDataAccessTable = TableRegistry::get("UserAccessData");
        $userDataAccessObject = $userDataAccessTable->find()->where(array('user_id' => $userId, 'access_type_id' => $accessTypeId));
        if ($userDataAccessObject->count() > 0) {
            $userDataAccessObject = $userDataAccessObject->toArray();
            $userAccessDataId = $userDataAccessObject[0]['user_access_data_id'];
            $userAccessControlTable = TableRegistry::get("UserAccessControl");
            $userAccessControlObject = $userAccessControlTable->find()->where(array('user_access_data_id' => $userAccessDataId, 'device_id' => $deviceId));
            if ($userAccessControlObject->count() > 0) {
                $userAccessControlObject = $userAccessControlObject->toArray();
                $accessId = $userAccessControlObject[0]['access_id'];
                $result = $accessId;
            }
        }
        return $result;
    }


    /**
     * @api {post} /Access/checkUserAccess Login to the system with a Device
     * @apiName checkUserAccess
     * @apiGroup AccessControl
     *
     * @apiParamExample {json} Request-Example:
     *
     *        {
     *          "ReaxiumParameters": {
     *           "UserAccessData": {
     *           "device_id": "1",
     *           "access_type_id": "3",
     *           "user_rfid_code": "45623"
     *           }
     *          }
     *        }
     *
     * @apiParamExample {json} Request-Example:
     *
     *      {
     *          "ReaxiumParameters": {
     *          "UserAccessData": {
     *          "device_id": "1",
     *          "access_type_id": "2",
     *          "user_bio_code":"4792v"
     *          }
     *      }
     *      }
     *
     * @apiParamExample {json} Request-Example:
     *
     *      {
     *          "ReaxiumParameters": {
     *          "UserAccessData": {
     *          "device_id": "1",
     *          "access_type_id": "1",
     *          "user_login_name":"reaxiumUser",
     *          "user_password":"reaxiumPassword"
     *          }
     *      }
     *    }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {"ReaxiumResponse": {
     *              "code": "0",
     *              "message": "SUCCESSFUL REQUEST",
     *               "object": []
     *                }
     *              }
     *
     *
     * @apiErrorExample Error-Response Invalid Access:
     *  {
     *      "ReaxiumResponse": {
     *      "code": 2,
     *      "message": "AccessType invalid",
     *      "object": []
     *      }
     *  }
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
    //TODO modificado encriptado
    public function checkUserAccess()
    {
        Log::info("User access information Service invoked");
        parent::setResultAsAJson();
        $response = parent:: getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $failure = false;
        $result = array();

        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {

                if (isset($jsonObject['ReaxiumParameters']["UserAccessData"])) {

                    $this->loadModel('UserAccessData');

                    if (isset($jsonObject['ReaxiumParameters']['UserAccessData']['device_id']) &&
                        isset($jsonObject['ReaxiumParameters']['UserAccessData']['access_type_id'])
                    ) {

                        $deviceId = $jsonObject['ReaxiumParameters']['UserAccessData']['device_id'];
                        $access_type_id = $jsonObject['ReaxiumParameters']['UserAccessData']['access_type_id'];
                        $arrayOfConditions = null;

                        switch (intval($access_type_id)) {
                            case 1:
                                //login user and password
                                if (isset($jsonObject['ReaxiumParameters']['UserAccessData']['user_login_name']) &&
                                    isset($jsonObject['ReaxiumParameters']['UserAccessData']['user_password'])
                                ) {

                                    $login_user = $jsonObject['ReaxiumParameters']['UserAccessData']['user_login_name'];
                                    $pass_user = $jsonObject['ReaxiumParameters']['UserAccessData']['user_password'];

                                    //$passEncrypt = Security::hash($pass_user,'sha1',true);

                                    $arrayOfConditions = array('UserAccessData.access_type_id' => $access_type_id,
                                        'user_login_name' => $login_user,
                                        'user_password' => $pass_user);
                                } else {
                                    $failure = true;
                                }
                                break;
                            case 2:
                                // biometric
                                if (isset($jsonObject['ReaxiumParameters']['UserAccessData']['user_bio_code'])) {
                                    $code_biometric = $jsonObject['ReaxiumParameters']['UserAccessData']['user_bio_code'];
                                    $arrayOfConditions = array('UserAccessData.access_type_id' => $access_type_id,
                                        'biometric_code' => $code_biometric);
                                } else {
                                    $failure = true;
                                }
                                break;
                            case 3:
                                //rfid
                                if (isset($jsonObject['ReaxiumParameters']['UserAccessData']['user_rfid_code'])) {
                                    $code_rfid = $jsonObject['ReaxiumParameters']['UserAccessData']['user_rfid_code'];
                                    $arrayOfConditions = array('UserAccessData.access_type_id' => $access_type_id,
                                        'rfid_code' => $code_rfid);
                                } else {
                                    $failure = true;
                                }

                                break;
                            default:
                                $failure = true;
                                break;
                        }

                        if (!$failure) {

                            $userExists = $this->getUserDataAccessInfo($arrayOfConditions);

                            if (isset($userExists)) {

                                Log::debug($userExists[0]);


                                if ($userExists[0]['status_id'] == ReaxiumApiMessages::$CODE_VALIDATE_STATUS) {

                                    array_push($result, $userExists[0]);

                                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                                    $response['ReaxiumResponse']['message'] = ReaxiumApiMessages::$SUCCESS_MESSAGE;
                                    $response['ReaxiumResponse']['object'] = $result;
                                } else {
                                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_USER_STATUS_CODE;
                                    $response['ReaxiumResponse']['message'] = ReaxiumApiMessages::$INVALID_USER_STATUS_MESSAGE;
                                }

                            } else {
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_USER_ACCESS_CODE;
                                $response['ReaxiumResponse']['message'] = ReaxiumApiMessages::$INVALID_USER_ACCESS_MESSAGE;
                            }

                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_PARAMETERS_CODE;
                            $response['ReaxiumResponse']['message'] = 'AccessType invalid';
                        }

                    }

                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error: " . $e->getMessage());
                $response = $this->setInternalServiceError($response);

            }
        } else {
            Log::info("Error - Json Invalido");
            $response = parent::setInvalidJsonMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    public function checkParentsAccess()
    {
        Log::info("checkParentsAccess service invoked");
        parent::setResultAsAJson();
        $response = parent:: getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {

                if (isset($jsonObject['ReaxiumParameters']["UserAccessData"])) {

                    $arrayOfValuesToValidate = array('user_login_name', 'user_password', 'device_token', 'device_platform');
                    $validation = ReaxiumUtil::validateParameters($arrayOfValuesToValidate, $jsonObject['ReaxiumParameters']["UserAccessData"]);
                    if ($validation['code'] == 0) {

                        $userName = $jsonObject['ReaxiumParameters']['UserAccessData']['user_login_name'];
                        $userPassword = $jsonObject['ReaxiumParameters']['UserAccessData']['user_password'];
                        $deviceToken = $jsonObject['ReaxiumParameters']['UserAccessData']['device_token'];
                        $devicePlatform = $jsonObject['ReaxiumParameters']['UserAccessData']['device_platform'];

                        $result = $this->parentsAccessProcess($userName, $userPassword, $devicePlatform, $deviceToken);

                        if ($result['loginValidation']) {

                            $response = parent::setSuccessfulResponse($response);
                            $response['ReaxiumResponse']['object'] = array($result['parentInfo']);

                        } else {

                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_USER_ACCESS_CODE;
                            $response['ReaxiumResponse']['message'] = ReaxiumApiMessages::$INVALID_USER_ACCESS_MESSAGE;

                        }

                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_PARAMETERS_CODE;
                        $response['ReaxiumResponse']['message'] = $validation['message'];
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error: " . $e->getMessage());
                $response = $this->setInternalServiceError($response);
            }
        } else {
            Log::info("Error - Json Invalido");
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    private function parentsAccessProcess($userName, $password, $devicePlatform, $deviceToken)
    {
        $response = array('loginValidation' => false, 'parentInfo' => array('parent' => array(), 'parentRelationship' => array()));

        try {
            $accessTable = TableRegistry::get("UserAccessData");
            $accessId = $accessTable->find('all', array('fields' => array('user_id'),
                'conditions' => array(
                    'access_type_id' => '1',
                    'user_login_name' => $userName,
                    'user_password' => $password,
                    'status_id' => '1')
            ));
            if ($accessId->count() > 0) {
                $response['loginValidation'] = true;
                $accessId = $accessId->toArray();
                $stakeholderTable = TableRegistry::get("Stakeholders");

                //save the device id of the parent, for send him push notifications in the future
                switch ($devicePlatform) {
                    case 'ANDROID':
                        $stakeholderTable->updateAll(array('android_id' => $deviceToken), array('user_id' => $accessId[0]['user_id']));
                        break;
                    case 'IOS':
                        $stakeholderTable->updateAll(array('ios_id' => $deviceToken), array('user_id' => $accessId[0]['user_id']));
                        break;
                    default:
                        Log::info("The device platform recieved is not valid: " . $devicePlatform);
                        break;
                }

                //find the stakeholder user relationship
                $stakeHolder = $stakeholderTable->findByUserId($accessId[0]['user_id'])->contain(array('Users' => array('UserType')));
                if ($stakeHolder->count() > 0) {
                    $stakeHolder = $stakeHolder->toArray();
                    $response['parentInfo']['parent'] = $stakeHolder[0]['user'];
                    $userRelationShipTable = TableRegistry::get("UsersRelationship");
                    $userRelationship = $userRelationShipTable->find('all', array(
                        'fields' => array('Users.document_id',
                            'Users.user_id',
                            'Users.first_name',
                            'Users.second_name',
                            'Users.first_last_name',
                            'Users.user_photo',
                            'Users.email',
                            'Users.birthdate',
                            'Business.business_name'),
                        'conditions' => array('stakeholder_id' => $stakeHolder[0]['stakeholder_id'], 'Users.status_id' => '1'),
                        'contain' => array('Users' => array('Business'))
                    ));
                    Log::info($userRelationship);
                    if ($userRelationship->count() > 0) {
                        $userRelationship = $userRelationship->toArray();
                        foreach($userRelationship as $userRel){
                            array_push($response['parentInfo']['parentRelationship'],$userRel['Users']);
                        }
                    }
                }
            } else {
                Log::info("Invalid user login for parent username: " . $userName . ", with parentPassword: " . $password);
            }
        } catch (\Exception $e) {
            Log::info("Error, with the parents login, " . $e->getMessage());
        }
        return $response;
    }


    /**
     * @param $arrayOfConditions
     * @return $this|array|\Cake\ORM\Table|null
     */
    private function getUserDataAccessInfo($arrayOfConditions)
    {
        $access = TableRegistry::get("UserAccessData");
        $access = $access->find()->where($arrayOfConditions)->contain(array('Users' => array('UserType', 'Status', 'Stakeholders' => array('Users'))));
        if ($access->count() > 0) {
            $access = $access->toArray();
        } else {
            $access = null;
        }
        return $access;
    }


    //TODO nuevo servicio pendiente documentacion
    public function createAccessNewUser()
    {

        Log::info("Create Access Service invoked");

        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent:: validReaxiumJsonHeader($jsonObject)) {

            $user_id = !isset($jsonObject['ReaxiumParameters']["UserAccessData"]["user_id"]) ? null : $jsonObject['ReaxiumParameters']["UserAccessData"]["user_id"];
            $access_type_id = !isset($jsonObject['ReaxiumParameters']["UserAccessData"]["access_type_id"]) ? null : $jsonObject['ReaxiumParameters']["UserAccessData"]["access_type_id"];
            $user_login = !isset($jsonObject['ReaxiumParameters']["UserAccessData"]["user_login"]) ? null : $jsonObject['ReaxiumParameters']["UserAccessData"]["user_login"];
            $user_password = !isset($jsonObject['ReaxiumParameters']["UserAccessData"]["user_password"]) ? null : $jsonObject['ReaxiumParameters']["UserAccessData"]["user_password"];

            if (isset($user_id) && isset($access_type_id) && isset($user_login) && isset($user_password)) {

                try {
                    $userDataAccessTable = TableRegistry::get("UserAccessData");
                    $userAccessInfo = $userDataAccessTable->findByUserIdAndAccessTypeId($user_id, $access_type_id);

                    //$passWordEncrypt = Security::hash($user_password,'sha1',true);

                    if ($userAccessInfo->count() > 0) {
                        $userAccessInfo = $userAccessInfo->toArray();

                        $userDataAccessTable->updateAll(array("user_login_name" => $user_login, "user_password" => $user_password), array('user_access_data_id' => $userAccessInfo[0]['user_access_data_id']));

                        Log::info("LoginUser and Password actualizado para usuario: " + $user_id);
                        Log::info(json_encode($userAccessInfo));

                        $response = parent::setSuccessfulResponse($response);
                    } else {

                        if($this->checkExistUserLoginName($user_login,$userDataAccessTable)){

                            $userAccessData = $userDataAccessTable->newEntity();
                            $userAccessData->user_id = $user_id;
                            $userAccessData->access_type_id = $access_type_id;
                            $userAccessData->user_login_name = $user_login;
                            $userAccessData->user_password = $user_password;
                            $userAccessData = $userDataAccessTable->save($userAccessData);

                            Log::info("Accesso creado para el usuario: " + $user_id);
                            Log::info(json_encode($userAccessData));

                            $response = parent::setSuccessfulResponse($response);

                        }else{
                            $response['ReaxiumResponse']['code'] = '2';
                            $response['ReaxiumResponse']['message'] = "User name login is invalid please choose another ";
                        }
                    }

                } catch (\Exception $e) {

                    Log::info('Error loading the access information for the user: ' . $user_id);
                    Log::info($e->getMessage());

                    if ($e->getCode() == 23000) {
                        $response['ReaxiumResponse']['code'] = '1';
                        $response['ReaxiumResponse']['message'] = "Login and Password Invalid try again";
                        $response['ReaxiumResponse']['object'] = [];
                    } else {
                        $response = parent::setInternalServiceError($response);
                    }

                }
            } else {
                Log::info("Se quedo aqui");
                $response = parent::seInvalidParametersMessage($response);
            }
        } else {
            $response = parent::seInvalidParametersMessage($response);
        }

        $this->response->body(json_encode($response));
    }

    /***
     * Check userLogin exist
     * @param $userLogin
     * @param $userDataAccessTable
     * @return bool
     */
    private function checkExistUserLoginName($userLogin,$userDataAccessTable){

        $validate = true;

        $userDataAccessFound = $userDataAccessTable->findByUserLoginName($userLogin);

        if($userDataAccessFound->count()>0){
            $validate = false;
        }

        return $validate;
    }


    /**
     *
     */
    public function checkAccessControlByUser()
    {

        Log::info("cheack Access Service invoked");

        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $arrayFinal = [];

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            $user_id = !isset($jsonObject["ReaxiumParameters"]["ReaxiumDevice"]["user_id"]) ? null : $jsonObject["ReaxiumParameters"]["ReaxiumDevice"]["user_id"];
            $device_id = !isset($jsonObject["ReaxiumParameters"]["ReaxiumDevice"]["device_id"]) ? null : $jsonObject["ReaxiumParameters"]["ReaxiumDevice"]["device_id"];


            if (isset($user_id) && isset($device_id)) {
                try {
                    $userDataAccess = TableRegistry::get("UserAccessData");
                    $userDataAccess = $userDataAccess->findByUserId($user_id);


                    if ($userDataAccess->count() > 0) {

                        $userAccessControlTable = TableRegistry::get("UserAccessControl");

                        $userDataAccessTable = $userDataAccess->toArray();

                        foreach ($userDataAccessTable as $accessData) {

                            if ($this->validateAccessControl($device_id, $accessData['user_access_data_id'], $userAccessControlTable)) {
                                array_push($arrayFinal, $accessData);
                            }
                        }

                        Log::info(json_encode($arrayFinal));

                        if (count($arrayFinal) > 0) {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                            $response['ReaxiumResponse']['message'] = ReaxiumApiMessages::$SUCCESS_MESSAGE;
                            $response['ReaxiumResponse']['object'] = $arrayFinal;

                        } else {
                            $response['ReaxiumResponse']['code'] = '2';
                            $response['ReaxiumResponse']['message'] = "All access is already configured for this user";
                            $response['ReaxiumResponse']['object'] = [];

                        }
                    } else {
                        $response['ReaxiumResponse']['code'] = "1";
                        $response['ReaxiumResponse']['message'] = "Not get data by user";
                        $response['ReaxiumResponse']['object'] = [];

                    }

                } catch (\Exception $e) {
                    Log::info('Error loading the access information for the user: ' . $user_id);
                    Log::info($e->getMessage());
                    $response = parent::setInternalServiceError($response);
                }
            }
        } else {
            $response = parent::seInvalidParametersMessage($response);
        }
        $this->response->body(json_encode($response));
    }


    private function validateAccessControl($device_id, $user_access_data_id, $userAccessControlTable)
    {

        Log::info("Device: " . $device_id . " Access: " . $user_access_data_id);
        $validate = true;

        $userAccessControl = $userAccessControlTable->findByDeviceIdAndUserAccessDataId($device_id, $user_access_data_id);

        Log::info($userAccessControl->count());

        if ($userAccessControl->count() > 0) {
            $validate = false;
        }

        return $validate;
    }


    public function addDeviceAccessData()
    {

        Log::info("Insert access relationship device Service invoked");

        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $validate = true;

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            $arrayObj = !isset($jsonObject["ReaxiumParameters"]["ReaxiumDevice"]["object"]) ? null : $jsonObject["ReaxiumParameters"]["ReaxiumDevice"]["object"];

            if (isset($arrayObj)) {

                if (count($arrayObj) > 0) {

                    try {
                        Log::info($arrayObj);
                        $userAccessControlTable = TableRegistry::get("UserAccessControl");
                        $userAccessData = $userAccessControlTable->newEntities($arrayObj);

                        foreach ($userAccessData as $entity) {

                            if (!$userAccessControlTable->save($entity)) {
                                $validate = false;
                                break;
                            }
                        }

                        if ($validate) {
                            $response = parent::setSuccessfulResponse($response);
                        } else {
                            Log::info('Error insertando elemento en tabla users_access_control');
                            $response = parent::setInternalServiceError($response);
                        }

                    } catch (\Exception $e) {
                        Log::info('Error loading the access information for the user: ');
                        Log::info($e->getMessage());
                        $response = parent::setInternalServiceError($response);
                    }

                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } else {
                $response = parent::seInvalidParametersMessage($response);
            }

        } else {
            $response = parent::seInvalidParametersMessage($response);
        }
        $this->response->body(json_encode($response));
    }

}