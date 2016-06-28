<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 21/5/2016
 * Time: 08:45
 */

namespace App\Controller;

use Cake\Core\Exception\Exception;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;

//PATH PRODUCCION
//define('PATH_DIRECTORY', '../../reaxium_reports/');
//PATH DESARROLLO
define('PATH_DIRECTORY', '../../reports_school/');
define('DEFAULT_URL_PHOTO_USER', 'http://54.200.133.84/reaxium_user_images/profile-default.png');
define("TYPE_USER_STUDENT",2);
define("TYPE_USER_STAKEHOLDER",3);
define("TYPE_ACCESS_DOCUMENT_ID",4);
define("MIN_RANDOM",10000000);
define("MAX_RANDOM",99999999);
class BulkController extends ReaxiumAPIController{



    /**
     * @api {post} /Bulk/bulkUsersSystem Create A New User in the system
     * @apiName createUser
     * @apiGroup Users
     *
     * @apiParamExample {json} Request-Example:
     * {
     *  "ReaxiumParameters":{
     *  "BulkUsers":{
     *  "name_file":"test_school_users1.csv"
     *  }
     *  }
     *  }
     *
     * {
     *  "ReaxiumResponse": {
     *  "code": 0,
     *  "message": "SUCCESSFUL REQUEST",
     *  "object": {
     *  "register_saved": 2
     *  }
     *  }
     *  }
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
    public function bulkUsersSystem(){

        Log::info("Service for load massive users in system");

        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            try {

                $name_file = !isset($jsonObject['ReaxiumParameters']['BulkUsers']['name_file']) ? null
                    : $jsonObject['ReaxiumParameters']['BulkUsers']['name_file'];

                if (isset($name_file)) {

                    //Ubicacion del directorio
                    $path = PATH_DIRECTORY . DIRECTORY_SEPARATOR . $name_file;

                    if (file_exists($path)) {

                        //Leer archivo ccv
                        $csv = $this->readCSV($path, ';');
                        $usersTable = TableRegistry::get("Users");
                        $phoneTable = TableRegistry::get("PhoneNumbers");
                        $addressTable = TableRegistry::get("Address");
                        $userAccessTable = TableRegistry::get("UserAccessData");

                        $validate = true;
                        $messageError = array('code' => 0, 'message' => '');

                        if (count($csv) > 0) {

                            //recorre cada row del arreglo csv
                            for ($i = 1; $i < count($csv); $i++) {

                                $documentId = empty(trim($csv[$i][0])) ? null : trim($csv[$i][0]);
                                $firstName = empty(trim($csv[$i][1])) ? null : trim($csv[$i][1]);
                                $middleName = empty(trim($csv[$i][2])) ? null : trim($csv[$i][2]);
                                $lastName = empty(trim($csv[$i][3])) ? null : trim($csv[$i][3]);
                                $birthdate = empty(trim($csv[$i][4])) ? null : trim($csv[$i][4]);
                                $phoneHome = empty(trim($csv[$i][5])) ? null : trim($csv[$i][5]);
                                $phoneOffice = empty(trim($csv[$i][6])) ? null : trim($csv[$i][6]);
                                $phoneOther = empty(trim($csv[$i][7])) ? null : trim($csv[$i][7]);
                                $businessNumber = empty(trim($csv[$i][8])) ? null : trim($csv[$i][8]);
                                $typeUser = empty(trim($csv[$i][10])) ? null : trim($csv[$i][10]);
                                $userAddress = empty($csv[$i][11]) ? null : $csv[$i][11];
                                $emailUser = empty(trim($csv[$i][12])) ? null : trim($csv[$i][12]);
                                $documentIdSForParents = empty(trim($csv[$i][13])) ? null : trim($csv[$i][13]);


                                if (isset($documentId) && isset($firstName)
                                    && isset($lastName) && isset($birthdate) && isset($businessNumber)
                                    && isset($typeUser)
                                ) {

                                    $entityUser = $usersTable->newEntity();
                                    $entityAddress = $addressTable->newEntity();

                                    $arrayPhone = array();

                                    // se obtiene id del tipo de usuario
                                    $user_type = $this->findTypeUserId($typeUser);

                                    if (isset($user_type)) {
                                        $entityUser->user_type_id = $user_type[0]['user_type_id'];
                                    }
                                    else {

                                        $messageError['code'] = 2;
                                        $messageError['message'] = 'User type is invalid in row: ' . $i;
                                        break;
                                    }

                                    // se obtiene el id del negocio
                                    $business = $this->findSchoolId($businessNumber);

                                    if (isset($business)) {
                                        $entityUser->business_id = $business[0]['business_id'];
                                    }
                                    else {

                                        $messageError['code'] = 1;
                                        $messageError['message'] = 'business number is invalid in row: ' . $i;
                                        break;
                                    }

                                    // si el tipo de usuario es estudiante se obtiene el ID de csv de resto se genera automaticamente
                                    //para otro usuario
                                    if($entityUser->user_type_id == TYPE_USER_STUDENT){
                                        $entityUser->document_id = $documentId;
                                    }
                                    else if($entityUser->user_type_id == TYPE_USER_STAKEHOLDER){
                                        if(isset($documentIdSForParents)){
                                            $entityUser->document_id = $this->findAndGenerateDocumentId();
                                        }
                                        else{
                                            $messageError['code'] = 3;
                                            $messageError['message'] = 'field of relationship parents and students is empty: ' . $i;
                                            break;
                                        }

                                    }

                                    $entityUser->first_name = $firstName;
                                    $entityUser->second_name = $middleName;
                                    $entityUser->first_last_name = $lastName;
                                    $entityUser->birthdate = $birthdate;


                                    if (isset($phoneHome)) {
                                        $entityPhones = $phoneTable->newEntity();
                                        $entityPhones->phone_name = 'Home';
                                        $entityPhones->phone_number = $phoneHome;
                                        array_push($arrayPhone, $entityPhones);
                                    }

                                    if (isset($phoneOffice)) {
                                        $entityPhones = $phoneTable->newEntity();
                                        $entityPhones->phone_name = 'Office';
                                        $entityPhones->phone_number = $phoneOffice;
                                        array_push($arrayPhone, $entityPhones);
                                    }

                                    if (isset($phoneOther)) {
                                        $entityPhones = $phoneTable->newEntity();
                                        $entityPhones->phone_name = 'Other';
                                        $entityPhones->phone_number = $phoneOther;
                                        array_push($arrayPhone, $entityPhones);
                                    }

                                    $entityUser->status_id = 1;
                                    $entityUser->user_photo = DEFAULT_URL_PHOTO_USER;


                                    //address falta como obtener logitud latitud
                                    if (isset($userAddress)) {

                                        $entityAddress->address = $userAddress;
                                        $arrayGeoData = $this->getLatitudeAndLongitude($userAddress);

                                        if(isset($arrayGeoData)){
                                            $entityAddress->latitude = $arrayGeoData['latitude'];
                                            $entityAddress->longitude = $arrayGeoData['longitude'];
                                        }else{
                                            $entityAddress->latitude = '26.3645341';
                                            $entityAddress->longitude = '-80.1329333';
                                        }

                                    }

                                    $entityUser->email = $emailUser;

                                    if($entityUser->user_type_id == TYPE_USER_STUDENT){
                                        $validate = $this->createUser($usersTable, $entityUser, $phoneTable, $arrayPhone, $addressTable, $entityAddress,$userAccessTable);
                                    }
                                    else if($entityUser->user_type_id == TYPE_USER_STAKEHOLDER){
                                        $validate = $this->createStakeHolder($usersTable, $entityUser, $phoneTable, $arrayPhone, $addressTable, $entityAddress,$documentIdSForParents);
                                    }
                                }
                            }

                            if ($validate) {

                                $response = parent::setSuccessfulResponse($response);

                            } else {
                                Log::info($messageError['message']);
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                                $response['ReaxiumResponse']['message'] = $messageError['message'];

                            }

                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'File not found for processing';
                        }
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'File not found for processing';
                    }

                } else {
                    $response = parent::setInvalidJsonMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error getting the data of file .csv " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }

        } else {
            $response = parent::setInvalidJsonMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    /**
     * @api {post} /Bulk/bulkSchoolSystem Create A New User in the system
     * @apiName createUser
     * @apiGroup Users
     *
     * @apiParamExample {json} Request-Example:
     * {
     *  "ReaxiumParameters":{
     *  "BulkSchool":{
     *  "name_file":"test_school_users1.csv"
     *  }
     *  }
     *  }
     *
     * {
     *  "ReaxiumResponse": {
     *  "code": 0,
     *  "message": "SUCCESSFUL REQUEST",
     *  "object": {
     *  "register_saved": 2
     *  }
     *  }
     *  }
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
    public function bulkSchoolSystem()
    {

        Log::info("Service for load massive school in system");

        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            try {
                $name_file = !isset($jsonObject['ReaxiumParameters']['BulkSchool']['name_file']) ? null
                    : $jsonObject['ReaxiumParameters']['BulkSchool']['name_file'];

                //Ubicacion del directorio
                $path = PATH_DIRECTORY . DIRECTORY_SEPARATOR . $name_file;

                if (isset($name_file)) {

                    if (file_exists($path)) {
                        //Leer archivo ccv
                        $csv = $this->readCSV($path, ';');

                        $businessTable = TableRegistry::get('Business');
                        $addressTable = TableRegistry::get("Address");
                        $phoneNumbersTable = TableRegistry::get("PhoneNumbers");

                        $validate = true;
                        $contRegister = 0;

                        if (count($csv) > 0) {

                            //recorre cada row del arreglo csv
                            for ($i = 1; $i < count($csv); $i++) {

                                $businessId = empty(trim($csv[$i][0])) ? null : trim($csv[$i][0]);
                                $schoolType = empty(trim($csv[$i][1])) ? null : trim($csv[$i][1]);
                                $schoolName = empty(trim($csv[$i][2])) ? null : trim($csv[$i][2]);
                                $schoolAddress = empty(trim($csv[$i][3])) ? null : trim($csv[$i][3]);
                                $schoolZip = empty(trim($csv[$i][4])) ? null : trim($csv[$i][4]);
                                $schoolPhone = empty(trim($csv[$i][5])) ? null : trim($csv[$i][5]);


                                if (isset($businessId) &&
                                    isset($schoolType) && isset($schoolName) &&
                                    isset($schoolAddress) && isset($schoolZip) && isset($schoolPhone)) {

                                    $contRegister++;

                                    $entityPhone = $phoneNumbersTable->newEntity();
                                    $entityPhone->phone_name = 'Oficce';
                                    $entityPhone->phone_number = $schoolPhone;

                                    $addressReal = $schoolName .','. $schoolAddress .','. $schoolZip;

                                    $entityAddress = $addressTable->newEntity();
                                    $entityAddress->address = $addressReal;

                                    $arrayGeoData = $this->getLatitudeAndLongitude($addressReal);

                                    if(isset($arrayGeoData)){
                                        $entityAddress->latitude = $arrayGeoData['latitude'];
                                        $entityAddress->longitude = $arrayGeoData['longitude'];
                                    }else{
                                        $entityAddress->latitude = '25.77427';
                                        $entityAddress->longitude = '-80.19366';
                                    }


                                    $entityBusiness = $businessTable->newEntity();
                                    $entityBusiness->business_name = $schoolName;
                                    $entityBusiness->business_id_number = $businessId;
                                    $entityBusiness->type_business = $schoolType;
                                    $entityBusiness->status_id = 1;

                                    $validate = $this->createBusiness($businessTable,
                                        $entityBusiness,
                                        $phoneNumbersTable,
                                        $entityPhone,
                                        $addressTable,
                                        $entityAddress);
                                }
                            }

                            if($validate){
                                $response = parent::setSuccessfulResponse($response);
                                $response['ReaxiumResponse']['object'] = array('register_saved'=>$contRegister);
                            }else{
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                                $response['ReaxiumResponse']['message'] = 'Bulk Business no found,Please contact with the api administrator';
                            }


                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'File not found for processing';
                        }
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'File not found for processing';
                    }
                } else {
                    $response = parent::setInvalidJsonMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error getting the data of file .csv " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }

        } else {
            $response = parent::setInvalidJsonMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    /**
     * @api {post} /Bulk/bulkStopsSystem Create A New User in the system
     * @apiName createUser
     * @apiGroup Users
     *
     * @apiParamExample {json} Request-Example:
     * {
     *  "ReaxiumParameters":{
     *  "BulkStops":{
     *  "name_file":"test_school_users1.csv"
     *  }
     *  }
     *  }
     *
     * {
     *  "ReaxiumResponse": {
     *  "code": 0,
     *  "message": "SUCCESSFUL REQUEST",
     *  "object": {
     *  "register_saved": 2
     *  }
     *  }
     *  }
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
    public function bulkStopsSystem(){

        Log::info("Service for load massive stops in system");

        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();


        if(parent::validReaxiumJsonHeader($jsonObject)){

            try{

                $name_file = !isset($jsonObject['ReaxiumParameters']['BulkStops']['name_file']) ? null
                    : $jsonObject['ReaxiumParameters']['BulkStops']['name_file'];

                //Ubicacion del directorio
                $path = PATH_DIRECTORY . DIRECTORY_SEPARATOR . $name_file;

                if(isset($name_file)){

                    if (file_exists($path)) {

                        //Leer archivo ccv
                        $csv = $this->readCSV($path, ';');
                        $stopsTable = TableRegistry::get('Stops');

                        $validate = true;
                        $contRegister = 0;

                        if (count($csv) > 0) {

                            //recorre cada row del arreglo csv
                            for ($i = 1; $i < count($csv); $i++) {

                                $stopNumber = empty(trim($csv[$i][0])) ? null : trim($csv[$i][0]);
                                $stopName = empty(trim($csv[$i][1])) ? null : trim($csv[$i][1]);
                                $stopAddress = empty(trim($csv[$i][2])) ? null : trim($csv[$i][2]);

                                if(isset($stopNumber) && isset($stopName) && isset($stopAddress)){

                                    $contRegister++;

                                    $entityStops = $stopsTable->newEntity();
                                    $entityStops->stop_number = $stopNumber;
                                    $entityStops->stop_name = $stopName;
                                    $entityStops->stop_address = $stopAddress;
                                    $entityStops->status_id = 1;

                                    $arrayGoe = $this->getLatitudeAndLongitude($stopAddress);

                                    if(isset($arrayGoe)){
                                        $entityStops->stop_latitude = $arrayGoe['latitude'];
                                        $entityStops->stop_longitude = $arrayGoe['longitude'];
                                    }else{
                                        $entityStops->stop_latitude = '25.77427';
                                        $entityStops->stop_longitude = '-80.19366';
                                    }

                                    $validate = $this->createStops($stopsTable,$entityStops);
                                }
                            }

                            if($validate){
                                $response = parent::setSuccessfulResponse($response);
                                $response['ReaxiumResponse']['object'] = array('register_saved'=>$contRegister);
                            }else{
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                                $response['ReaxiumResponse']['message'] = 'Bulk Stops no found,Please contact with the api administrator';
                            }
                        }
                        else{
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'File not found for processing';
                        }

                    }else{
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'File not found for processing';
                    }

                }else{
                    $response = parent::setInvalidJsonMessage($response);
                }

            }catch(\Exception $e){
                Log::info("Error getting the data of file .csv " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        }
        else{
            $response = parent::setInvalidJsonMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    /**
     * Create user Method transactional
     * @param $usersTable
     * @param $entityUser
     * @param $phoneTable
     * @param $arrayPhone
     * @param $addressTable
     * @param $entityAddress
     * @return bool
     */
    private function createUser($usersTable, $entityUser, $phoneTable, $arrayPhone, $addressTable, $entityAddress,$userAccessTable)
    {

        $validate = true;

        try {

            $conn = $usersTable->connection();
            $phoneNumbersRelationshipTable = TableRegistry::get("PhoneNumbersRelationship");
            $addressRelationshipTable = TableRegistry::get("AddressRelationship");


            //bloque transaccional
            $conn->transactional(function () use (
                $usersTable,
                $entityUser,
                $phoneTable,
                $arrayPhone,
                $addressTable,
                $entityAddress,
                $phoneNumbersRelationshipTable,
                $addressRelationshipTable,
                $userAccessTable) {

                //$conn->execute('UPDATE phone_numbers SET phone_name = ? WHERE phone_number_id = ?', ["Home", 1]);
                //$conn->execute('UPDATE users SET second_name = ? WHERE user_id = ?', ["test14", 80]);
                //$entityUser->user_type_id

                //save table user
                $resultUserSave = $usersTable->save($entityUser);

                //save phones
                foreach ($arrayPhone as $entityPhone) {

                    $resultPhoneSave = $phoneTable->save($entityPhone);
                    $entityRelationUserPhone = $phoneNumbersRelationshipTable->newEntity();
                    $entityRelationUserPhone->phone_number_id = $resultPhoneSave['phone_number_id'];
                    $entityRelationUserPhone->user_id = $resultUserSave['user_id'];
                    $phoneNumbersRelationshipTable->save($entityRelationUserPhone);
                }

                //save address

                $resultAddressSave = $addressTable->save($entityAddress);
                $entityRelationUserAddress = $addressRelationshipTable->newEntity();
                $entityRelationUserAddress->address_id = $resultAddressSave['address_id'];
                $entityRelationUserAddress->user_id = $resultUserSave['user_id'];
                $addressRelationshipTable->save($entityRelationUserAddress);


                //validando tipo de accceso
                if(isset($resultUserSave['user_type_id']) && $resultUserSave['user_type_id'] == TYPE_USER_STUDENT ){

                    Log::info("Proceso para crear acceso de estudiante");

                    // se crea el tipo de acceso

                        Log::info("Creando un acceso al estudiante con ID: ".$resultUserSave['user_id']);
                        Log::info("Creando un acceso al estudiante con documento id: ".$resultUserSave['document_id']);

                        $userAccessDate = $userAccessTable->newEntity();
                        $userAccessDate->user_id = $resultUserSave['user_id'];
                        $userAccessDate->access_type_id = TYPE_ACCESS_DOCUMENT_ID;
                        $userAccessDate->document_id = $resultUserSave['document_id'];
                        $userAccessDate->status_id = 1;
                        $userAccessTable->save($userAccessDate);
                }

            });
        } catch (\Exception $e) {
            Log::info("Error creando el usuario: " . $e->getMessage());
            $validate = false;
        }

        if($validate){
            Log::info("Creacion del Usuario Student con DocumentId: ".$entityUser->document_id);
        }

        return $validate;
    }

    /**
     * Metodo para crear relacion de usuario Stakeholder
     * @param $usersTable
     * @param $entityUser
     * @param $userData
     * @return bool
     */
    private function createStakeHolder($usersTable,$entityUser, $phoneTable, $arrayPhone, $addressTable, $entityAddress,$documentStudents){

        $validate = true;

        try{
            $conn = $usersTable->connection();

            $phoneNumbersRelationshipTable = TableRegistry::get("PhoneNumbersRelationship");
            $addressRelationshipTable = TableRegistry::get("AddressRelationship");
            $userRelationShipTable = TableRegistry::get("UsersRelationship");
            $stakeholderTable = TableRegistry::get("Stakeholders");

            // comprobando si existe los id de los studiantes

            $userRelationShip = explode(",",$documentStudents);
            $userIdRelationParent = [];

            foreach($userRelationShip as $documentId){
                $userId = $this->findByDocumentIdUser($documentId);
                if(isset($userId)){
                    array_push($userIdRelationParent,$userId);
                }else{
                    throw new Exception("Estudiante no registrado para completar la relacion: "."document ID: ".$documentId);
                }
            }

            if(count($userIdRelationParent) > 0){

                $conn->transactional(function () use ($usersTable,
                    $entityUser,
                    $phoneTable,
                    $arrayPhone,
                    $addressTable,
                    $entityAddress,
                    $userRelationShipTable,
                    $stakeholderTable,
                    $documentStudents,
                    $phoneNumbersRelationshipTable,
                    $addressRelationshipTable,
                    $userIdRelationParent){



                    //save table user
                    $resultUserSave = $usersTable->save($entityUser);

                    //save phones
                    foreach ($arrayPhone as $entityPhone) {

                        $resultPhoneSave = $phoneTable->save($entityPhone);
                        $entityRelationUserPhone = $phoneNumbersRelationshipTable->newEntity();
                        $entityRelationUserPhone->phone_number_id = $resultPhoneSave['phone_number_id'];
                        $entityRelationUserPhone->user_id = $resultUserSave['user_id'];
                        $phoneNumbersRelationshipTable->save($entityRelationUserPhone);
                    }

                    //save address

                    $resultAddressSave = $addressTable->save($entityAddress);
                    $entityRelationUserAddress = $addressRelationshipTable->newEntity();
                    $entityRelationUserAddress->address_id = $resultAddressSave['address_id'];
                    $entityRelationUserAddress->user_id = $resultUserSave['user_id'];
                    $addressRelationshipTable->save($entityRelationUserAddress);


                    // save date stakeholder relation
                    $stakeholder = $stakeholderTable->newEntity();
                    $stakeholder->user_id = $resultUserSave['user_id'];
                    $stakeholderId = $stakeholderTable->save($stakeholder);
                    $stakeholderId = $stakeholderId->stakeholder_id;

                    //buscar los id de los estudiantes relacionados al padre por document ID

                    foreach ($userIdRelationParent as $userId) {
                        $relationshipObject = $userRelationShipTable->newEntity();
                        $relationshipObject->stakeholder_id = $stakeholderId;
                        $relationshipObject->user_id = $userId;
                        $userRelationShipTable->save($relationshipObject);

                    }

                });
            }


        }catch(\Exception $e){
            Log::info("Error creando el usuario stakeholder: " . $e->getMessage());
            $validate = false;
        }

        if($validate){
            Log::info("Creacion del Usuario StakeHolder con DocumentId: ".$entityUser->document_id);
        }

        return $validate;
    }


    /**
     * Create Stops Method transactional
     * @param $stopsTable
     * @param $entityStop
     * @return bool
     */
    private function createStops($stopsTable,$entityStop){

        $validate = true;

        try{
            $conn = $stopsTable->connection();

            $conn->transactional(function() use($stopsTable,$entityStop){
                $stopsTable->save($entityStop);
            });
        }
        catch (\Exception $e){
            Log::info("Error creando Stops " . $e->getMessage());
            $validate = false;
        }

        return $validate;
    }

    /**
     * Create Business Method transactional
     * @param $businessTable
     * @param $entityBusiness
     * @param $phoneNumbersTable
     * @param $entityPhone
     * @param $addressTable
     * @param $entityAddress
     * @return bool
     */
    private function createBusiness($businessTable, $entityBusiness, $phoneNumbersTable, $entityPhone, $addressTable, $entityAddress){

        $validate = true;

        try {
            $conn = $businessTable->connection();

            //bloque transacional
            $conn->transactional(function () use (
                $businessTable,
                $entityBusiness,
                $phoneNumbersTable,
                $entityPhone,
                $addressTable,
                $entityAddress
            ) {

                //save phone
                $resultPhone = $phoneNumbersTable->save($entityPhone);

                //save address
                $resultAddress = $addressTable->save($entityAddress);

                //save business
                $entityBusiness->address_id = $resultAddress['address_id'];
                $entityBusiness->phone_number_id = $resultPhone['phone_number_id'];
                $businessTable->save($entityBusiness);

            });
        } catch (\Exception $e) {
            Log::info("Error creando el usuario: " . $e->getMessage());
            $validate = false;
        }

        return $validate;
    }


    /**
     * Read csv File
     * @param $csvFile
     * @return array
     */
    private function readCSV($csvFile, $delimiter)
    {

        $file_handle = fopen($csvFile, 'r');

        while (!feof($file_handle)) {
            $line_of_text[] = fgetcsv($file_handle, 1024, $delimiter);
        }

        fclose($file_handle);

        return $line_of_text;
    }

    /**
     * @param $nameUser
     * @return int
     */
    private function findTypeUserId($nameUser)
    {

        $userTypeTable = TableRegistry::get("UserType");
        $userTypeFound = $userTypeTable->findByUserTypeName(strtolower($nameUser));

        if ($userTypeFound->count() > 0) {

            $userTypeFound = $userTypeFound->toArray();
            //$id_user_type = $userTypeFound[0]['user_type_id'];
        } else {
            $userTypeFound = null;
        }

        return $userTypeFound;
    }

    /**
     * @param $idNumberSchool
     * @return int
     */
    private function findSchoolId($idNumberSchool)
    {

        $businessTable = TableRegistry::get("Business");
        $businessFound = $businessTable->findByBusinessIdNumber($idNumberSchool);

        if ($businessFound->count() > 0) {

            $businessFound = $businessFound->toArray();
        } else {
            $businessFound = null;
        }

        return $businessFound;

    }



    /**
     * Method for get latitude and longitude
     * @param $address
     * @return array|null
     */
    private function getLatitudeAndLongitude($address)
    {

        $latitudeAndLongitude = null;

        try{
            Log::info($address);
            // We get the JSON results from this request
            $geo = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&sensor=false');
            // We convert the JSON to an array
            $geo = json_decode($geo, true);
            // If everything is cool
            if ($geo['status'] = 'OK') {
                // We set our values
                if(isset($geo['results'][0])){
                    $latitude = $geo['results'][0]['geometry']['location']['lat'];
                    $longitude = $geo['results'][0]['geometry']['location']['lng'];
                    $latitudeAndLongitude = array('latitude' => $latitude, 'longitude' => $longitude);
                }else{
                    Log::info("No se pudo obtener longitud y latitude");
                    $latitudeAndLongitude = null;
                }
            }
        }
        catch (\Exception $e){
          Log::info("Error obteniendo latitud y longitud: ".$e->getMessage());
            $latitudeAndLongitude = null;
        }

        return $latitudeAndLongitude;
    }


    /**
     * AutoGenera un document id
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
     * Buscar user id del usuario por document id
     * @param $documentId
     * @return null
     */
    private function findByDocumentIdUser($documentId){

        $userId = null;
        $userTable = TableRegistry::get("Users");
        $userData = $userTable->findByDocumentId($documentId);

        if($userData->count() > 0){
            $userData = $userData->toArray();
            $userId = $userData[0]['user_id'];
        }

        return $userId;
    }

}