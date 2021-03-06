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
     * {
     * "ReaxiumParameters": {
     * "Users": {
     * "document_id": "19055085",
     * "first_name": "Jhon",
     * "second_name": "Andrew",
     * "first_last_name": "Doe",
     * "second_last_name": "Smith",
     * "user_type_id": 2,
     * "user_photo": "/userimages/user_name_profile_picture.jpg"
     * },
     * "PhoneNumbers": [{
     * "phone_number_id": 1,
     * "phone_name": "Home",
     * "phone_number": "0212-3734831"
     * }],
     * "address": [{
     * "address": "Miranda, San antonio de los altos, urbanizacion OPS torre 4, 1204",
     * "latitude": "10.37706",
     * "longitude": "-66.95635"
     * }]
     * }
     * }
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
     * @api {post} /Users/createStakeholderUser Create A New Stakeholder User in the system
     * @apiName createStakeholderUser
     * @apiGroup Users
     *
     * @apiParamExample {json} Request-Example:
     * {
     * "ReaxiumParameters": {
     * "Users": {
     * "document_id": "19055085",
     * "first_name": "Jhon",
     * "second_name": "Andrew",
     * "first_last_name": "Doe",
     * "second_last_name": "Smith",
     * "user_type_id": 2,
     * "user_photo": "/userimages/user_name_profile_picture.jpg"
     * },
     * "PhoneNumbers": [{
     * "phone_number_id": 1,
     * "phone_name": "Home",
     * "phone_number": "0212-3734831"
     * }],
     * "address": [{
     * "address": "Miranda, San antonio de los altos, urbanizacion OPS torre 4, 1204",
     * "latitude": "10.37706",
     * "longitude": "-66.95635"
     * }],
     * "Relationship":[{
     * "user_id":2
     * },{
     * "user_id":3
     * }]
     * }
     * }
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
     *          "message": "User already exist in the system",
     *          "object": []
     *          }
     *      }
     *
     */
    public function createStakeholderUser()
    {
        Log::info("Create a new Stakeholder User service has been invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Users"])) {
                    $result = $this->createUserStakeholder($jsonObject['ReaxiumParameters']);
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
        $userId = null;
        $this->loadModel("Users");
        $users = $this->Users->newEntity();
        $users = $this->Users->patchEntity($users, $userJSON['Users']);
        Log::info($users);
        $result = $this->Users->save($users);
        $userId = $result->user_id;
        Log::info('UserId: ' . $userId);
        $result['PhoneNumbers'] = $this->addPhoneToAUser($userJSON['PhoneNumbers'], $userId);
        $result['Address'] = $this->addAddressToAUser($userJSON['address'], $userId);
        return $result;
    }

    /**
     * @param $userJSON
     * @return created
     */
    private function createUserStakeholder($userJSON)
    {
        $userStakeHolderData = $this->createAUser($userJSON);
        $stakeholderId = $userStakeHolderData['stakeholder_id'];
        $resultRelationship = array();
        if (!isset($stakeholderId)) {
            $stakeholderTable = TableRegistry::get("Stakeholders");
            $stakeholder = $stakeholderTable->newEntity();
            $stakeholder->user_id = $userStakeHolderData['user_id'];
            $stakeholderId = $stakeholderTable->save($stakeholder);
            $stakeholderId = $stakeholderId->stakeholder_id;
        }
        $userRelationShip = $userJSON['Relationship'];
        $userRelationShipTable = TableRegistry::get("UsersRelationship");
        foreach ($userRelationShip as $relationship) {
            $relationshipObject = $userRelationShipTable->newEntity();
            $relationshipObject->stakeholder_id = $stakeholderId;
            $relationshipObject->user_id = $relationship['user_id'];
            $relationshipSaved = $userRelationShipTable->save($relationshipObject);
            array_push($resultRelationship, $relationshipSaved);
        }
        $userStakeHolderData['Relationship'] = $resultRelationship;
        return $userStakeHolderData;
    }

    /**
     * add phone numbers to a user
     */
    private function addPhoneToAUser($phoneJson, $userID)
    {
        $phoneNumbersTable = TableRegistry::get("PhoneNumbers");
        $phoneNumbersRelationshipTable = TableRegistry::get("PhoneNumbersRelationship");
        $result = array();
        $phoneIdChecker = false;
        foreach ($phoneJson as $phone) {
            $phoneNumber = $phoneNumbersTable->newEntity();
            $phoneNumber = $phoneNumbersTable->patchEntity($phoneNumber, $phone);
            if (isset($phoneNumber->phone_number_id)) {
                $phoneIdChecker = true;
            }
            $resultOfSave = $phoneNumbersTable->save($phoneNumber);
            array_push($result, $resultOfSave);
            Log::info('PhoneId checker: ' . $phoneIdChecker);
            if (!$phoneIdChecker) {
                $relationShip = $phoneNumbersRelationshipTable->newEntity();
                $relationShip->phone_number_id = $resultOfSave->phone_number_id;
                $relationShip->user_id = $userID;
                $phoneNumbersRelationshipTable->save($relationShip);
            }
        }
        return $result;
    }

    /**
     * add address info to a user
     */
    private function addAddressToAUser($addressJson, $userID)
    {
        $addressTable = TableRegistry::get("Address");
        $addressRelationshipTable = TableRegistry::get("AddressRelationship");
        $result = array();
        $addressIdChecker = false;
        foreach ($addressJson as $address) {
            $addressObject = $addressTable->newEntity();
            $addressObject = $addressTable->patchEntity($addressObject, $address);
            if (isset($addressObject->address_id)) {
                $addressIdChecker = true;
            };
            $resultOfSave = $addressTable->save($addressObject);
            array_push($result, $resultOfSave);
            Log::info('AddressId Checker: ' . $addressIdChecker);
            if (!$addressIdChecker) {
                $relationShip = $addressRelationshipTable->newEntity();
                $relationShip->address_id = $resultOfSave->address_id;
                $relationShip->user_id = $userID;
                $addressRelationshipTable->save($relationShip);
            }
        }
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
     * {
     * "ReaxiumResponse": {
     * "code": 0,
     * "message": "SUCCESSFUL REQUEST",
     * "object": [
     * {
     * "user_id": 4,
     * "document_id": "6810108",
     * "first_name": "Diana Carolina",
     * "second_name": "Carolina",
     * "first_last_name": "Mogollon",
     * "second_last_name": "Marquez",
     * "status_id": 1,
     * "user_type_id": 2,
     * "user_photo": null,
     * "stakeholder": [
     * {
     * "stakeholder_id": 4,
     * "status_id": 1,
     * "android_id": null,
     * "ios_id": null,
     * "user_id": 22,
     * "_joinData": {
     * "stakeholder_id": 4,
     * "user_id": 4
     * }
     * }
     * ],
     * "address": [],
     * "user_type": {
     * "user_type_id": 2,
     * "user_type_name": "student"
     * },
     * "phone_numbers": [],
     * "status": {
     * "status_id": 1,
     * "status_name": "ACTIVE"
     * }
     * }
     * ]
     * }
     * }
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

                            $userFound[0]['Stakeholders'] = $this->getStakeHolders($userFound[0]['user_id']);
                            if ($userFound[0]['user_type_id'] == 3) {
                                $userFound[0]['UserRelationship'] = $this->getUserRelationshipByUserID($userFound[0]['user_id']);
                            }

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
        $user = $usersTable->find()->where($arrayConditions)->contain(array("Status", "PhoneNumbers", "UserType", "Address"));
        if ($user->count() > 0) {
            $user = $user->toArray();
        } else {
            $user = null;
        }
        return $user;
    }

    /**
     * get few information of a user
     * @param $userId
     * @return $this|array|null
     */
    public function getUser($userId)
    {
        $usersTable = TableRegistry::get("Users");
        $user = $usersTable->find()->where(array('user_id' => $userId))->contain(array("Status", "PhoneNumbers", "UserType", "Address"));
        if ($user->count() > 0) {
            $user = $user->toArray();
            if ($user[0]['user_type_id'] == 3) {
                $user[0]['UserRelationship'] = $this->getUserRelationshipByUserID($userFound[0]['user_id']);
            }
            $user[0]['stakeholders'] = $this->getStakeHolders($userId);
        } else {
            $user = null;
        }
        return $user;
    }

    private function getStakeHolders($userId)
    {
        $relationshipTable = TableRegistry::get("UsersRelationship");
        $stakeholderTable = TableRegistry::get("Stakeholders");
        $stakeholderInfoArray = array();
        $steakholderFound = $relationshipTable->find()->where(array('user_id' => $userId));
        if ($steakholderFound->count() > 0) {
            $steakholderFound = $steakholderFound->toArray();
            $stakeholder = null;
            foreach ($steakholderFound as $stakeholder) {
                $stakeholder = $stakeholder['stakeholder_id'];
                $stakeholder = $stakeholderTable->findByStakeholderId($stakeholder);
                if ($stakeholder->count() > 0) {
                    $stakeholder = $stakeholder->toArray();
                    $arrayOfConditions = array('user_id' => $stakeholder[0]['user_id']);
                    array_push($stakeholderInfoArray, $this->getUserInfo($arrayOfConditions));
                }
            }
        }
        return $stakeholderInfoArray;
    }

    /**
     *
     * obtain the business bind to a stakeholderId
     *
     * @return \Cake\ORM\Table  --Stakeholder information
     */
    private function getUserRelationhip($stakeHolderId)
    {
        $relationshipTable = TableRegistry::get("UsersRelationship");
        $userfound = $relationshipTable->find()->where(array('stakeholder_id' => $stakeHolderId))->contain("Users");
        if ($userfound->count() > 0) {
            $userfound = $userfound->toArray();
            $usersArray = array();
            foreach ($userfound as $user) {
                array_push($usersArray, $user['user']);
            }
            $userfound = $usersArray;
        } else {
            $userfound = null;
        }
        return $userfound;
    }

    /**
     *
     * obtain the business bind to a stakeholder looking first by his user_id
     *
     * @return \Cake\ORM\Table  --Stakeholder information
     */
    private function getUserRelationshipByUserID($userId)
    {
        $stakeholderTable = TableRegistry::get("Stakeholders");
        $stakleHolder = $stakeholderTable->findByUserId($userId);
        $stakeHolderId = null;
        if ($stakleHolder->count() > 0) {
            $stakleHolder = $stakleHolder->toArray();
            $stakeHolderId = $stakleHolder[0]['stakeholder_id'];
        }
        $userfound = $this->getUserRelationhip($stakeHolderId);
        return $userfound;
    }


    /**
     * @api {post} /Users/allUsersInfo all the information of system users
     * @apiName allUsersInfo
     * @apiGroup Users
     *
     *
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
     * @apiErrorExample Error-Response Not Found:
     *          {"ReaxiumResponse": {
     *              "code": 404,
     *              "message": "No users found",
     *              "object": []
     *                  }
     *                }
     */
    public function allUsersInfo()
    {
        Log::info("All User information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        try {
            $userTable = TableRegistry::get('Users');
            $userFound = $userTable->find()->contain(array("Status", "UserType"))->order(array('first_name', 'first_last_name'));
            if ($userFound->count() > 0) {
                $userFound = $userFound->toArray();
                $response['ReaxiumResponse']['object'] = $userFound;
                $response = parent::setSuccessfulResponse($response);
            } else {
                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                $response['ReaxiumResponse']['message'] = 'No Users found';
            }
        } catch (\Exception $e) {
            Log::info("Error getting the user " . $e->getMessage());
            $response = parent::setInternalServiceError($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     * @api {post} /Users/allUsersInfoWithPagination all the information of system users showed by page
     * @apiName allUsersInfoWithPagination
     * @apiGroup Users
     *
     *       {
     *        "ReaxiumParameters": {
     *          "Users": {
     *              "page": "1",
     *              "limit": "10"
     *              "sortDir": "desc",
     *              "sortedBy": "first_name"
     *              }
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
     * @apiErrorExample Error-Response Not Found:
     *          {"ReaxiumResponse": {
     *              "code": 404,
     *              "message": "No users found",
     *              "object": []
     *                  }
     *                }
     */
    public function allUsersInfoWithPagination()
    {
        Log::info("All User information eith pagination Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            Log::info('Object received: ' . json_encode($jsonObject));
            try {
                if (isset($jsonObject['ReaxiumParameters']["page"])) {

                    $page = $jsonObject['ReaxiumParameters']["page"];
                    $sortedBy = !isset($jsonObject['ReaxiumParameters']["sortedBy"])? 'first_last_name':$jsonObject['ReaxiumParameters']["sortedBy"];
                    $sortDir = !isset($jsonObject['ReaxiumParameters']["sortDir"])? 'desc':$jsonObject['ReaxiumParameters']["sortDir"];
                    $filter = !isset($jsonObject['ReaxiumParameters']["filter"])? '':$jsonObject['ReaxiumParameters']["filter"];
                    $limit = !isset($jsonObject['ReaxiumParameters']["limit"])? 10:$jsonObject['ReaxiumParameters']["limit"];

                    $userTable = TableRegistry::get('Users');

                    if(trim($filter) != '' ){
                        $whereCondition = array(array('OR' => array(
                            array('first_name LIKE' => '%' . $filter . '%'),
                            array('first_last_name LIKE' => '%' . $filter . '%'),
                            array('document_id LIKE' => '%' . $filter . '%')
                        )));
                        $userFound = $userTable->find()->where($whereCondition)->contain(array("Status", "UserType"))->order(array($sortedBy.' '.$sortDir));
                    }else{
                        $userFound = $userTable->find()->contain(array("Status", "UserType"))->order(array($sortedBy.' '.$sortDir));
                    }

                    $count = $userFound->count();
                    $this->paginate = array('limit' => $limit, 'page' => $page);
                    $userFound = $this->paginate($userFound);

                    if ($userFound->count() > 0) {
                        $maxPages = floor((($count - 1) / $limit) + 1);
                        $userFound = $userFound->toArray();
                        $response['ReaxiumResponse']['totalRecords'] = $count;
                        $response['ReaxiumResponse']['totalPages'] = $maxPages;
                        $response['ReaxiumResponse']['object'] = $userFound;
                        $response = parent::setSuccessfulResponse($response);
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'No Users found';
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
     * @api {post} /Users/allUsersWithFilter search a user by a filter
     * @apiName allUsersWithFilter
     * @apiGroup Users
     * @apiParamExample {json} Request-Example:
     *
     *      {"ReaxiumParameters": {
     *          "Users": {
     *              "filter": "19044081"
     *               }
     *            }
     *         }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *      "ReaxiumResponse": {
     *          "code": 0,
     *          "message": "SUCCESSFUL REQUEST",
     *          "object": [{
     *              "user_id":"1",
     *              "document_id": "19044081",
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
     * @apiErrorExample Error-Response Not Found:
     *          {"ReaxiumResponse": {
     *              "code": 404,
     *              "message": "No users found",
     *              "object": []
     *                  }
     *                }
     */
    public function allUsersWithFilter()
    {
        Log::info("All User information with filter Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Users"]["filter"])) {
                    $userTable = TableRegistry::get('Users');
                    $filter = $jsonObject['ReaxiumParameters']["Users"]["filter"];
                    $whereCondition = array(array('OR' => array(
                        array('first_name LIKE' => '%' . $filter . '%'),
                        array('first_last_name LIKE' => '%' . $filter . '%'),
                        array('document_id LIKE' => '%' . $filter . '%')
                    )));
                    $userFound = $userTable->find()->where($whereCondition)->order(array('first_name', 'first_last_name'));
                    if ($userFound->count() > 0) {
                        $userFound = $userFound->toArray();
                        $response['ReaxiumResponse']['object'] = $userFound;
                        $response = parent::setSuccessfulResponse($response);
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'No Users found';
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error getting the user " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
            Log::info("Responde Object: " . json_encode($response));
            $this->response->body(json_encode($response));
        }
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

    /**
     * Service for get type user
     */
    public function usersTypeList(){
        Log::info("Looking for the users type list ");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $response['ReaxiumResponse']['object'] = $this->getTypeUsersList();
        $this->response->body(json_encode($response));
    }


    private function getTypeUsersList(){
        $typeUserTable= TableRegistry::get("UserType");
        $typeUserList = $typeUserTable->find()->order(array("user_type_name"));
        if ($typeUserList->count() > 0) {
            $typeUserList = $typeUserList->toArray();
        }
        return $typeUserList;
    }


}
