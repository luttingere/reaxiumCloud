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


define("BIOMETRIC_FILE_PATH", "/reaxium_user_images/biometric_user_images/");
define("BIOMETRIC_FILE_FULL_PATH", "/var/www/html/reaxium_user_images/biometric_user_images/");
define("ADMIN_SCHOOL",5);
define("CALL_CENTER",6);
define("TYPE_USER_STUDENT",2);
define("TYPE_ACCESS_DOCUMENT_ID",4);
define("MIN_RANDOM",10000000);
define("MAX_RANDOM",99999999);
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
    private function createAUser($userJSON){

        $userId = null;
        $document_id = null;
        $result = null;

        try{

            $this->loadModel("Users");
            $users = $this->Users->newEntity();

            //si no tiene document id se genera uno automatico
            if(isset($userJSON['Users']['document_id']) && empty($userJSON['Users']['document_id'])){
                Log::info("No existe document Id se crea uno nuevo");
                $document_id = $this->findAndGenerateDocumentId();
                Log::info("Document Id generado: " . $document_id);
                $userJSON['Users']['document_id'] = $document_id;
            }
            else{
                $document_id =  $userJSON['Users']['document_id'];
            }

            $users = $this->Users->patchEntity($users, $userJSON['Users']);
            Log::info($users);
            $result = $this->Users->save($users);
            $userId = $result->user_id;
            Log::info('UserId: ' . $userId);
            $result['PhoneNumbers'] = $this->addPhoneToAUser($userJSON['PhoneNumbers'], $userId);
            $result['Address'] = $this->addAddressToAUser($userJSON['address'], $userId);

            if($result){
                //validando tipo de accceso
                if(!empty($userJSON['Users']['user_type_id']) && $userJSON['Users']['user_type_id'] == TYPE_USER_STUDENT ){

                    Log::info("Proceso para crear acceso de estudiante");

                    $userAccessTable = TableRegistry::get("UserAccessData");

                    // se crea el tipo de acceso
                    if(!isset($userJSON['Users']['user_id'])){

                        Log::info("Creando un acceso al estudiante con ID: ".$userId);
                        Log::info("Creando un acceso al estudiante con documento id: ".$document_id);

                        $userAccessDate = $userAccessTable->newEntity();
                        $userAccessDate->user_id = $userId;
                        $userAccessDate->access_type_id = TYPE_ACCESS_DOCUMENT_ID;
                        $userAccessDate->document_id = $document_id;
                        $userAccessDate->status_id = 1;
                        $userAccessTable->save($userAccessDate);
                    }
                    else if(isset($userJSON['Users']['user_id']) && !empty($userJSON['Users']['user_id'])){
                        //se edita el tipo de acceso
                        Log::info("Update acceso del usuario con id: ".$userId);
                        Log::info("Update acceso del usuario con document_id: ".$document_id);

                        $userAccessTable->updateAll(array('document_id'=>$document_id),array('user_id'=>$userId,'access_type_id'=>TYPE_ACCESS_DOCUMENT_ID));
                    }
                }
            }
        }catch (\Exception $e){
            Log::info("Error creando usuario");
            Log::info($e->getMessage());
        }

        return $result;
    }

    /**
     *
     * @return int|string
     */
    private function findAndGenerateDocumentId(){

        $document_id = "";
        $userTable = TableRegistry::get("Users");

        while(true){
            $document_id = rand(MIN_RANDOM,MAX_RANDOM);
            $userData = $userTable->findByDocumentId($document_id);
            if($userData->count() == 0){break;}
        }

        return $document_id;
    }


    /**
     * @param $userJSON
     * @return created
     */
    private function createUserStakeholder($userJSON)
    {
        $userRelationShipTable = TableRegistry::get("UsersRelationship");
        $userStakeHolderData = $this->createAUser($userJSON);
        $stakeholderId = null;
        if(isset($userJSON['Users']['stakeholder_id'])){
            $stakeholderId = $userJSON['Users']['stakeholder_id'];
            $userRelationShipTable->deleteAll(array('stakeholder_id'=>$stakeholderId));
        }
        $resultRelationship = array();
        if (!isset($stakeholderId)) {
            $stakeholderTable = TableRegistry::get("Stakeholders");
            $stakeholder = $stakeholderTable->newEntity();
            $stakeholder->user_id = $userStakeHolderData['user_id'];
            $stakeholderId = $stakeholderTable->save($stakeholder);
            $stakeholderId = $stakeholderId->stakeholder_id;
        }
        $userRelationShip = $userJSON['Relationship'];

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

                        $arrayOfConditions = isset($user->business_id) ?
                            array('user_id' => $user->user_id,'Users.business_id'=>$user->business_id) : array('user_id' => $user->user_id);

                    } else if (isset($user->document_id)) {

                        $arrayOfConditions = isset($user->business_id) ?
                            array('document_id' => $user->document_id,'Users.business_id'=> $user->business_id) : array('document_id' => $user->document_id);

                    } else {
                        $failure = true;
                        $response = parent::seInvalidParametersMessage($response);
                    }
                    if (!$failure) {
                        $userFound = $this->getUserInfo($arrayOfConditions);
                        if (isset($userFound)) {

                            $userFound[0]['Stakeholders'] = $this->getStakeHolders($userFound[0]['user_id']);
                            if ($userFound[0]['user_type_id'] == 3) {
                                $userFound[0]['stakeholder_id'] = $this->getStakeHolderId($userFound[0]['user_id']);
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
        $user = $usersTable->find()->where($arrayConditions)->contain(array("Status", "PhoneNumbers", "UserType", "Address","Business"));
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
                $user[0]['UserRelationship'] = $this->getUserRelationshipByUserID($user[0]['user_id']);
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

    private function getStakeHolderId($userId)
    {
        $stakeholderId  = null;
        $stakeholderTable = TableRegistry::get("Stakeholders");
        $stakeholder = $stakeholderTable->findByUserId($userId);
        if ($stakeholder->count() > 0){
            $stakeholder = $stakeholder->toArray();
            $stakeholderId = $stakeholder[0]['stakeholder_id'];
        }
        return $stakeholderId;
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
                    $sortedBy = !isset($jsonObject['ReaxiumParameters']["sortedBy"]) ? 'first_last_name' : $jsonObject['ReaxiumParameters']["sortedBy"];
                    $sortDir = !isset($jsonObject['ReaxiumParameters']["sortDir"]) ? 'desc' : $jsonObject['ReaxiumParameters']["sortDir"];
                    $filter = !isset($jsonObject['ReaxiumParameters']["filter"]) ? '' : $jsonObject['ReaxiumParameters']["filter"];
                    $limit = !isset($jsonObject['ReaxiumParameters']["limit"]) ? 10 : $jsonObject['ReaxiumParameters']["limit"];

                    $userTypeId = !isset($jsonObject['ReaxiumParameters']["user_type_id"]) ? null : $jsonObject['ReaxiumParameters']["user_type_id"];

                    if(isset($userTypeId) && $userTypeId == CALL_CENTER){
                        $andCondition = array('Users.status_id' => 1,array('NOT'=>array('Users.user_type_id'=>1)));
                    }
                    elseif(isset($userTypeId) && $userTypeId == ADMIN_SCHOOL){

                        $business_id = !isset($jsonObject['ReaxiumParameters']["business_id"]) ? null : $jsonObject['ReaxiumParameters']["business_id"];
                        $andCondition = isset($business_id) ? array('Users.status_id' => 1,'Users.business_id'=>$business_id) : array('Users.status_id' => 1);
                    }else{
                        $andCondition = array('Users.status_id' => 1);
                    }


                    $userTable = TableRegistry::get('Users');

                    if (trim($filter) != '') {
                        $whereCondition = array(array('OR' => array(
                            array('first_name LIKE' => '%' . $filter . '%'),
                            array('first_last_name LIKE' => '%' . $filter . '%'),
                            array('document_id LIKE' => '%' . $filter . '%')
                        )));
                        $userFound = $userTable->find()
                            ->where($whereCondition)
                            ->andWhere($andCondition)
                            ->contain(array("Status", "UserType"))->order(array($sortedBy . ' ' . $sortDir));
                    } else {
                        $userFound = $userTable->find()
                            ->where($andCondition)
                            ->contain(array("Status", "UserType"))->order(array($sortedBy . ' ' . $sortDir));
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


                    $userTypeId = !isset($jsonObject['ReaxiumParameters']["Users"]["user_type_id"]) ? null : $jsonObject['ReaxiumParameters']["Users"]["user_type_id"];

                    if(isset($userTypeId) && $userTypeId == CALL_CENTER){
                        $andCondition = array('Users.status_id' => 1,array('NOT'=>array('Users.user_type_id'=>1)));
                    }
                    elseif(isset($userTypeId) && $userTypeId == ADMIN_SCHOOL){

                        $business_id = !isset($jsonObject['ReaxiumParameters']['Users']["business_id"]) ? null : $jsonObject['ReaxiumParameters']['Users']["business_id"];
                        $andCondition = isset($business_id) ? array('Users.status_id' => 1,'Users.business_id'=>$business_id) : array('Users.status_id' => 1);
                    }else{
                        $andCondition = array('Users.status_id' => 1);
                    }


                    $userFound = $userTable->find()
                        ->where($whereCondition)
                        ->andWhere($andCondition)
                        ->contain(array('UserType','Business'))
                        ->order(array('first_name', 'first_last_name'));

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

        $this->loadModel("UserAccessData");
        $this->UserAccessData->updateAll(array('status_id' => '3'), array('user_id' => $userId));
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
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $arrayTypeUsers=[];

        if(parent::validReaxiumJsonHeader($jsonObject)){

            $user_type_id = !isset($jsonObject['ReaxiumParameters']['Users']['user_type_id']) ? null : $jsonObject['ReaxiumParameters']['Users']['user_type_id'];

            try{

                if(isset($user_type_id)){

                    $arrayAux = $this->getTypeUsersList();

                    foreach($arrayAux as $entry){

                        if($user_type_id == 5){

                            switch($entry['user_type_id']){
                                case 2:
                                    array_push($arrayTypeUsers,$entry);
                                    break;
                                case 3:
                                    array_push($arrayTypeUsers,$entry);
                                    break;
                                case 4:
                                    array_push($arrayTypeUsers,$entry);
                                    break;
                                case 5:
                                    array_push($arrayTypeUsers,$entry);
                                    break;
                            }
                        }
                        elseif($user_type_id == 6){

                            switch($entry['user_type_id']){
                                case 2:
                                    array_push($arrayTypeUsers,$entry);
                                    break;
                                case 3:
                                    array_push($arrayTypeUsers,$entry);
                                    break;
                                case 4:
                                    array_push($arrayTypeUsers,$entry);
                                    break;
                                case 5:
                                    array_push($arrayTypeUsers,$entry);
                                    break;
                                case 6:
                                    array_push($arrayTypeUsers,$entry);
                                    break;
                            }

                        }
                        elseif($user_type_id == 1){

                            array_push($arrayTypeUsers,$entry);
                        }

                    }
                }
                else{
                    $response = parent::seInvalidParametersMessage($response);
                }

            }
            catch(\Exception $e){
                Log::info($e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        }
        else{
            $response = parent::seInvalidParametersMessage($response);
        }

        $response = parent::setSuccessfulResponse($response);
        $response['ReaxiumResponse']['object'] = $arrayTypeUsers;
        $this->response->body(json_encode($response));
    }


    private function getTypeUsersList()
    {
        $typeUserTable = TableRegistry::get("UserType");
        $typeUserList = $typeUserTable->find()->order(array("user_type_name"));
        if ($typeUserList->count() > 0) {
            $typeUserList = $typeUserList->toArray();
        }
        return $typeUserList;
    }

    public function saveUserFromDevice()
    {
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $object = parent::getJsonReceived();
        $userName = !isset($object['userName']) ? null : $object['userName'];
        $userSecondName = !isset($object['userSecondName']) ? null : $object['userSecondName'];
        $userLastName = !isset($object['userLastName']) ? null : $object['userLastName'];
        $userSecondLastName = !isset($object['userSecondLastName']) ? null : $object['userSecondLastName'];
        $userPhoto = !isset($object['userPhoto']) ? null : $object['userPhoto'];
        $userEmail = !isset($object['userEmail']) ? null : $object['userEmail'];
        $userBirthDate = !isset($object['userBirthDate']) ? null : $object['userBirthDate'];
        $userDocumentId = !isset($object['userDocumentId']) ? null : $object['userDocumentId'];

        Log::info('Saving a user through a device, parameters recieved');
        Log::info('User name: ' . $userName);
        Log::info('User last name: ' . $userLastName);
        Log::info('User document id: ' . $userDocumentId);
        Log::info('User Photo :' . $userPhoto);

        if (isset($userName) && isset($userLastName) && isset($userDocumentId)) {
            try {
                $userTable = TableRegistry::get("Users");
                $userByDocumentID = $userTable->findByDocumentId($userDocumentId);
                if ($userByDocumentID->count() < 1) {
                    $newUser = $userTable->newEntity();
                    $newUser->document_id = $userDocumentId;
                    $newUser->first_name = $userName;
                    $newUser->first_last_name = $userLastName;
                    if (isset($userSecondName)) {
                        $newUser->second_name = $userSecondName;
                    }
                    if (isset($userSecondLastName)) {
                        $newUser->second_last_name = $userSecondLastName;
                    }
                    if (isset($userEmail)) {
                        $newUser->email = $userEmail;
                    }
                    if (isset($userBirthDate)) {
                        $newUser->birthdate = $userBirthDate;
                    }
                    if (isset($userPhoto)) {
                        $userImageName = $userDocumentId . 'profile_image.jpg';
                        $imageFullPath = "http://" . $_SERVER['SERVER_NAME'] . BIOMETRIC_FILE_PATH . $userImageName;
                        $newUser->user_photo = $imageFullPath;
                        file_put_contents(BIOMETRIC_FILE_FULL_PATH . $userImageName, base64_decode($userPhoto));
                    }
                    $userSaved = $userTable->save($newUser);
                    $response = parent::setSuccessfulSave($response);
                    $response['ReaxiumResponse']['object'] = array($userSaved);

                } else {
                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$USER_ALREADY_REGISTERED_CODE;
                    $response['ReaxiumResponse']['message'] = ReaxiumApiMessages::$USER_ALREADY_REGISTERED_MESSAGE;
                }
            } catch (\Exception $e) {
                Log::info('Error saving a user');
                Log::info($e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::seInvalidParametersMessage($response);
        }
        $this->response->body(json_encode($response));
    }


    public function editUserFromDevice()
    {
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $object = parent::getJsonReceived();
        $userName = !isset($object['userName']) ? null : $object['userName'];
        $userSecondName = !isset($object['userSecondName']) ? null : $object['userSecondName'];
        $userLastName = !isset($object['userLastName']) ? null : $object['userLastName'];
        $userSecondLastName = !isset($object['userSecondLastName']) ? null : $object['userSecondLastName'];
        $userPhoto = !isset($object['userPhoto']) ? null : $object['userPhoto'];
        $userEmail = !isset($object['userEmail']) ? null : $object['userEmail'];
        $userBirthDate = !isset($object['userBirthDate']) ? null : $object['userBirthDate'];
        $userDocumentId = !isset($object['userDocumentId']) ? null : $object['userDocumentId'];
        $userId = !isset($object['userId']) ? null : $object['userId'];

        Log::info('editing a user through a device, parameters recieved');
        Log::info('User ID :' . $userId);
        Log::info('User name: ' . $userName);
        Log::info('User last name: ' . $userLastName);
        Log::info('User document id: ' . $userDocumentId);
        Log::info('User Photo :' . $userPhoto);

        if (isset($userId) && isset($userName) && isset($userLastName) && isset($userDocumentId)) {
            try {
                $userTable = TableRegistry::get("Users");
                $User = $userTable->newEntity();
                $User->user_id = $userId;
                $User->document_id = $userDocumentId;
                $User->first_name = $userName;
                $User->first_last_name = $userLastName;
                if (isset($userSecondName)) {
                    $User->second_name = $userSecondName;
                }
                if (isset($userSecondLastName)) {
                    $User->second_last_name = $userSecondLastName;
                }
                if (isset($userEmail)) {
                    $User->email = $userEmail;
                }
                if (isset($userBirthDate)) {
                    $User->birthdate = $userBirthDate;
                }
                if (isset($userPhoto)) {
                    $userImageName = $userDocumentId . 'profile_image.jpg';
                    $imageFullPath = "http://" . $_SERVER['SERVER_NAME'] . BIOMETRIC_FILE_PATH . $userImageName;
                    $User->user_photo = $imageFullPath;
                    file_put_contents(BIOMETRIC_FILE_FULL_PATH . $userImageName, base64_decode($userPhoto));
                }
                $userSaved = $userTable->save($User);
                $response = parent::setSuccessfulSave($response);
                $response['ReaxiumResponse']['object'] = array($userSaved);

            } catch (\Exception $e) {
                Log::info('Error saving a user');
                Log::info($e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::seInvalidParametersMessage($response);
        }
        $this->response->body(json_encode($response));
    }


}
