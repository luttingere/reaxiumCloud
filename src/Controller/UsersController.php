<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 22/03/2016
 * Time: 01:43 AM
 */

namespace App\Controller;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;

class UsersController extends ReaxiumAPIController
{

    /**
     * @api {post} /Users/createUser Create A New User in the system
     * @apiName createUser
     * @apiGroup Users
     *
     * @apiParamExample {json} Request-Example:
     *   {"ReaxiumParameters": {
     *      "Users": {
     *      "document_id": "19055085",
     *      "first_name": "Jhon",
     *      "second_name": "Andrew",
     *      "first_last_name": "Doe",
     *      "second_last_name": "Smith"
     *       }
     *     }
     *  }
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *      "ReaxiumResponse": {
     *          "code": 0,
     *          "message": "SAVED SUCCESSFUL",
     *          "object": [{
     *              "user_id":"1",
     *              "document_id": "19055085",
     *              "first_name": "Jhon",
     *              "second_name": "Andrew",
     *              "first_last_name": "Doe",
     *              "second_last_name":"Smith"
     *              "status_id":"1"
     *              "status":{
     *                  "status_id":"1",
     *                  "status_name":"Active"
     *                  }
     *              }]
     *          }
     *      }
     *
     * @apiErrorExample Error-Response: User already exist
     *  {
     *      "ReaxiumResponse": {
     *          "code": 101,
     *          "message": "User id number already exist in the system",
     *          "object": []
     *          }
     *      }
     *
     */
    public function createUser()
    {
        Log::info("Create a new User service has been invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Users"])) {
                    $result = $this->createAUser($jsonObject['ReaxiumParameters']);
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
                Log::info("Error Saving the User " . $e->getMessage());
                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                $response['ReaxiumResponse']['message'] = 'User id number already exist in the system';
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     *
     * Register a new user in the system
     *
     * @param $userJSON
     * @return created user
     */
    private function createAUser($userJSON)
    {
        $this->loadModel("Users");
        $users = $this->Users->newEntity();
        $users = $this->Users->patchEntity($users, $userJSON);
        $result = $this->Users->save($users);
        return $result;
    }

    /**
     * @api {post} /Users/userInfo get A User Information by ID
     * @apiName userInfo
     * @apiGroup Users
     *
     * @apiParamExample {json} Request-Example:
     *
     *      {"ReaxiumParameters": {
     *          "Users": {
     *              "user_id": "1"
     *               }
     *            }
     *         }
     *
     * @apiParamExample {json} Request-Example:
     *
     *      {"ReaxiumParameters": {
     *          "Users": {
     *              "document_id": "19055086"
     *               }
     *            }
     *         }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *      "ReaxiumResponse": {
     *          "code": 0,
     *          "message": "SUCCESSFUL REQUEST",
     *          "object": [{
     *              "user_id":"1",
     *              "document_id": "19055085",
     *              "first_name": "Jhon",
     *              "second_name": "Andrew",
     *              "first_last_name": "Doe",
     *              "second_last_name":"Smith"
     *              "status_id":"1"
     *              "status":{
     *                  "status_id":"1",
     *                  "status_name":"Active"
     *                  }
     *              }]
     *          }
     *      }
     *
     *
     * @apiErrorExample Error-Response User Not Found:
     *          {"ReaxiumResponse": {
     *              "code": 404,
     *              "message": "User Not found",
     *              "object": []
     *                  }
     *                }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     *      {"ReaxiumResponse": {
     *          "code": 2,
     *          "message": "Invalid Parameters received, please checkout the api documentation",
     *          "object": []
     *          }
     *      }
     *
     *
     * @apiErrorExample Error-Response Invalid Json Object:
     *      {"ReaxiumResponse": {
     *          "code": 2,
     *          "message": "Invalid Parameters received, please checkout the api documentation",
     *          "object": []
     *          }
     *      }
     */
    public function userInfo()
    {
        Log::info("User information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Users"])) {
                    $this->loadModel("Users");
                    $user = $this->Users->newEntity();
                    $user = $this->Users->patchEntity($user, $jsonObject['ReaxiumParameters']);
                    $arrayOfConditions = null;
                    $failure = false;
                    if (isset($user->user_id)) {
                        $arrayOfConditions = array('user_id' => $user->user_id);
                    } else if (isset($user->document_id)) {
                        $arrayOfConditions = array('document_id' => $user->document_id);
                    } else {
                        $failure = true;
                        $response = parent::seInvalidParametersMessage($response);
                    }
                    if (!$failure) {
                        $userFound = $this->getUserInfo($arrayOfConditions);
                        if (isset($userFound)) {
                            $response['ReaxiumResponse']['object'] = $userFound;
                            $response = parent::setSuccessfulResponse($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'User Not found';
                        }
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error getting the user " . $e->getMessage());
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
     * obtain all de information related to an specific User id
     *
     * @param $arrayConditions
     * @return \Cake\ORM\Table  --User information
     */
    private function getUserInfo($arrayConditions)
    {
        $usersTable = TableRegistry::get("Users");
        $user = $usersTable->find()->where($arrayConditions)->contain("Status");
        if ($user->count() > 0) {
            $user = $user->toArray();
        } else {
            $user = null;
        }
        return $user;
    }


    /**
     * @api {post} /Users/deleteUser Delete A User From the System
     * @apiName deleteUser
     * @apiGroup Users
     *
     * @apiParamExample {json} Request-Example:
     *
     * {"ReaxiumParameters": {
     *      "Users": {
     *          "user_id": "1"
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
     * @apiErrorExample Error-Response User Not Found:
     *      {"ReaxiumResponse": {
     *          "code": 404,
     *          "message": "User Not found",
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
    public function deleteUser()
    {
        Log::info("deleting  User service is running");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $deviceId = null;
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Users"])) {
                    $this->loadModel("Users");
                    $user = $this->Users->newEntity();
                    $user = $this->Users->patchEntity($user, $jsonObject['ReaxiumParameters']);
                    if (isset($user->user_id)) {
                        $arrayOfConditions = array('user_id' => $user->user_id);
                        $userFound = $this->getUserInfo($arrayOfConditions);
                        if (isset($userFound)) {
                            $this->deleteAUser($user->user_id);
                            $response = parent::setSuccessfulDelete($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'User Not found';
                        }
                    } else {
                        $response = parent::seInvalidParametersMessage($response);
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error deleting the user: " . $user->device_id . " error:" . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     * Delete a user from de system
     * @param $userId
     */
    private function deleteAUser($userId)
    {
        $this->loadModel("Users");
        $this->Users->updateAll(array('status_id' => '3'), array('user_id' => $userId));
    }


    /**
     * @api {post} /Users/changeUserStatus Change The Status Of A User
     * @apiName changeUserStatus
     * @apiGroup Users
     *
     * @apiParamExample {json} Request-Example:
     *
     *   {
     *    "ReaxiumParameters": {
     *      "Users": {
     *          "user_id": "1"
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
     * @apiErrorExample Error-Response User Not Found:
     *      {"ReaxiumResponse": {
     *          "code": 404,
     *          "message": "User Not found",
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
    public function changeUserStatus()
    {
        Log::info("updating the status of a user service is running");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $deviceId = null;
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Users"])) {
                    $this->loadModel("Users");
                    $user = $this->Users->newEntity();
                    $user = $this->Users->patchEntity($user, $jsonObject['ReaxiumParameters']);
                    if (isset($user->user_id) && isset($user->status_id)) {
                        $arrayOfConditionsForUser = array('user_id' => $user->user_id);
                        $userFound = $this->getUserInfo($arrayOfConditionsForUser);
                        if (isset($userFound)) {
                            $this->updateUser(array('status_id' => $user->status_id), array('user_id' => $user->user_id));
                            $response = parent::setSuccessfulUpdated($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'User Not found';
                        }
                    } else {
                        $response = parent::seInvalidParametersMessage($response);
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error updating the status of the user id: " . $user->user_id . " error:" . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    /**
     * Update the attributes of a User
     * @param $arrayFields
     * @param $arrayConditions
     */
    private function updateUser($arrayFields, $arrayConditions)
    {
        $this->loadModel("Users");
        $this->Users->updateAll($arrayFields, $arrayConditions);
    }




}