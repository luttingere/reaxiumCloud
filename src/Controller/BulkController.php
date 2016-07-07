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
define("TYPE_USER_ADMIN",1);
define("TYPE_USER_STUDENT",2);
define("TYPE_USER_STAKEHOLDER",3);
define("TYPE_USER_DRIVER",4);
define("TYPE_USER_ADMIN_SCHOOL",5);
define("TYPE_USER_ADMIN_CALL_CENTER",6);
define("TYPE_ACCESS_DOCUMENT_ID",4);
define("MIN_RANDOM",10000000);
define("MAX_RANDOM",99999999);
define ("MAX_COLUMN_CSV_USERS",12);
define("MAX_COLUMN_CSV_STOPS",3);
define("MAX_COLUMN_CSV_SCHOOL",6);
define("REPORT_USERS",1);
define("REPORT_SCHOOL",2);
define("REPORT_STOPS",3);
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
                        $csv = $this->readCSV($path,$name_file,REPORT_USERS);

                        Log::info(json_encode($csv));

                        $usersTable = TableRegistry::get("Users");
                        $phoneTable = TableRegistry::get("PhoneNumbers");
                        $addressTable = TableRegistry::get("Address");
                        $userAccessTable = TableRegistry::get("UserAccessData");

                        $validate = false;
                        $messageError = array('code' => 0, 'message' => '');

                        if (count($csv) > 0) {

                            //recorre cada row del arreglo csv
                            for ($i = 0; $i < count($csv); $i++) {

                                $lineaFile = $i+1;
                                $documentId = empty(trim($csv[$i][0])) ? null : trim($csv[$i][0]);
                                $firstName = empty(trim($csv[$i][1])) ? null : trim($csv[$i][1]);
                                $middleName = empty(trim($csv[$i][2])) ? null : trim($csv[$i][2]);
                                $lastName = empty(trim($csv[$i][3])) ? null : trim($csv[$i][3]);
                                $birthdate = empty(trim($csv[$i][4])) ? null : trim($csv[$i][4]);
                                $phoneHome = empty(trim($csv[$i][5])) ? null : trim($csv[$i][5]);
                                $phoneOffice = empty(trim($csv[$i][6])) ? null : trim($csv[$i][6]);
                                $phoneOther = empty(trim($csv[$i][7])) ? null : trim($csv[$i][7]);
                                $businessNumber = empty(trim($csv[$i][8])) ? null : trim($csv[$i][8]);
                                $typeUser = empty(trim($csv[$i][9])) ? null : trim($csv[$i][9]);
                                $emailUser = empty(trim($csv[$i][10])) ? null : trim($csv[$i][10]);
                                $documentIdSForParents = empty(trim($csv[$i][11])) ? null : trim($csv[$i][11]);


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
                                        $messageError['message'] = "User type ".$typeUser." is invalid in line: ".$lineaFile;
                                        $validate=false;
                                        break;
                                    }

                                    // se obtiene el id del negocio
                                    $business = $this->findSchoolId($businessNumber);

                                    if (isset($business)) {
                                        $entityUser->business_id = $business[0]['business_id'];
                                    }
                                    else {
                                        $messageError['code'] = 1;
                                        $messageError['message'] = "Business number ".$businessNumber." is invalid in line: ".$lineaFile;
                                        $validate=false;
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
                                            $messageError['message'] = 'field of relationship parents and students is empty: ' . $lineaFile;
                                            $validate=false;
                                            break;
                                        }
                                    }
                                    else if($entityUser->user_type_id == TYPE_USER_DRIVER){
                                        $entityUser->document_id = $this->findAndGenerateDocumentId();
                                    }
                                    else if($entityUser->user_type_id == TYPE_USER_ADMIN){
                                        $entityUser->document_id = $this->findAndGenerateDocumentId();
                                    }
                                    else if($entityUser->user_type_id == TYPE_USER_ADMIN_SCHOOL){
                                        $entityUser->document_id = $this->findAndGenerateDocumentId();
                                    }
                                    else if($entityUser->user_type_id == TYPE_USER_ADMIN_CALL_CENTER){
                                        $entityUser->document_id = $this->findAndGenerateDocumentId();
                                    }else{
                                        $messageError['code'] = 4;
                                        $messageError['message'] = "User type: ".$typeUser." is invalid in line: ".$lineaFile;
                                        $validate=false;
                                        break;
                                    }

                                    $entityUser->first_name = $firstName;
                                    $entityUser->second_name = $middleName;
                                    $entityUser->first_last_name = $lastName;

                                    //validado birthdate
                                    $validateBirthdate = $this->validateDate($birthdate);

                                      if($validateBirthdate){

                                          $date = explode("/",$birthdate);
                                          $m=$date[0];
                                          $d=$date[1];
                                          $y=$date[2];

                                          $dateFinal = $d.'/'.$m.'/'.$y;
                                          $entityUser->birthdate = $dateFinal;

                                      }else{

                                          $messageError['code'] = 5;
                                          $messageError['message'] = "Birthdate user ".$birthdate." has an invalid format suggested is the mm/dd/yyyy in line:".$lineaFile;
                                          $validate=false;
                                          break;
                                      }

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


                                    //La direccion del usuario es fija ya que por el momento no se esta tomando en cuenta
                                    //pero esta implementada.
                                    $entityAddress->address = '6000 Glades Rd, Boca Raton, FL 33431, United States';
                                    $entityAddress->latitude = '26.3645341';
                                    $entityAddress->longitude = '-80.1329333';

                                    $entityUser->email = $emailUser;

                                    if($entityUser->user_type_id == TYPE_USER_STUDENT){

                                        $existDocumentId = $this->findByDocumentIdUser($entityUser->document_id);

                                        if(isset($existDocumentId)){
                                            Log::info("El Document ID: ".$entityUser->document_id." existe no sera registrado el usuario en sistema linea: ".$lineaFile);
                                            $validate = true;
                                        }else{
                                            $validate = $this->createUser($usersTable, $entityUser, $phoneTable, $arrayPhone, $addressTable, $entityAddress,$userAccessTable);

                                        }
                                    }
                                    else if($entityUser->user_type_id == TYPE_USER_STAKEHOLDER){
                                        $validate = $this->createStakeHolder($usersTable, $entityUser, $phoneTable, $arrayPhone, $addressTable, $entityAddress,$documentIdSForParents);
                                    }
                                    else{
                                        //otro tipo de usuario
                                        $validate = $this->createUser($usersTable, $entityUser, $phoneTable, $arrayPhone, $addressTable, $entityAddress,$userAccessTable);
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

                if($e->getCode() == 90){
                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                    $response['ReaxiumResponse']['message'] = $e->getMessage();
                }
                else if($e->getCode() == 91){
                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                    $response['ReaxiumResponse']['message'] = $e->getMessage();
                }
                else if($e->getCode() == 93){
                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                    $response['ReaxiumResponse']['message'] = $e->getMessage();
                }
                else if($e->getCode() == 94){
                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                    $response['ReaxiumResponse']['message'] = $e->getMessage();
                }
                else{
                    $response = parent::setInternalServiceError($response);
                }

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
                        //Leer archivo csv
                        $csv = $this->readCSV($path,$name_file,REPORT_SCHOOL);

                        $businessTable = TableRegistry::get('Business');
                        $addressTable = TableRegistry::get("Address");
                        $phoneNumbersTable = TableRegistry::get("PhoneNumbers");

                        $validate = false;
                        $contRegister = 0;

                        if (count($csv) > 0) {

                            //recorre cada row del arreglo csv
                            for ($i = 0; $i < count($csv); $i++) {

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
                                        Log::info("No se pudo conseguir la latitud y logitud del business: ".$addressReal ."linea: ".$i);
                                        $validate = false;
                                        break;
                                    }


                                    $entityBusiness = $businessTable->newEntity();
                                    $entityBusiness->business_name = $schoolName;
                                    $entityBusiness->business_id_number = $businessId;
                                    $entityBusiness->type_business = $schoolType;
                                    $entityBusiness->status_id = 1;

                                    //valido si el school ID existe
                                    $existSchoolNumber = $this->findSchoolId($entityBusiness->business_id_number);

                                    if(isset($existSchoolNumber)){
                                        Log::info("El Business Id Number: ".$entityBusiness->business_id_number." existe no sera registrado el business en el sistema linea: ".$i);
                                        $validate = true;
                                    }
                                    else{
                                        $validate = $this->createBusiness($businessTable,
                                            $entityBusiness,
                                            $phoneNumbersTable,
                                            $entityPhone,
                                            $addressTable,
                                            $entityAddress);
                                    }

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

                if($e->getCode() == 90){
                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                    $response['ReaxiumResponse']['message'] = $e->getMessage();
                }
                else if($e->getCode() == 91){
                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                    $response['ReaxiumResponse']['message'] = $e->getMessage();
                }
                else{
                    $response = parent::setInternalServiceError($response);
                }
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
                        $csv = $this->readCSV($path,$name_file,REPORT_STOPS);
                        $stopsTable = TableRegistry::get('Stops');

                        $validate = true;
                        $contRegister = 0;

                        if (count($csv) > 0) {

                            //recorre cada row del arreglo csv
                            for ($i = 0; $i < count($csv); $i++) {

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

                                        $existStopNumber = $this->findByStopsNumber($entityStops->stop_number);

                                        if(isset($existStopNumber)){
                                            Log::info("El Stop Number: ".$entityStops->stop_number." existe no sera registrado stop en el sistema liena: ".$i);
                                            $validate = true;

                                        }else{
                                            $validate = $this->createStops($stopsTable,$entityStops);
                                        }

                                    }
                                    else{
                                        Log::info("No se pudo conseguir la latitud y logitud del stop address: ".$stopAddress);
                                        $validate = false;
                                        break;
                                    }

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
                if($e->getCode() == 90){
                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                    $response['ReaxiumResponse']['message'] = $e->getMessage();
                }
                else if($e->getCode() == 91){
                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                    $response['ReaxiumResponse']['message'] = $e->getMessage();
                }
                else{
                    $response = parent::setInternalServiceError($response);
                }
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
     * @param $phoneTable
     * @param $arrayPhone
     * @param $addressTable
     * @param $entityAddress
     * @param $documentStudents
     * @return bool
     * @throws \Exception
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

            $userRelationShip = array_filter(explode("|",$documentStudents));
            Log::info("Documents Id Student size: ".count($userRelationShip));
            $userIdRelationParent = [];

            if(strlen($documentStudents) == strlen($userRelationShip[0])){
                Log::info("Separador de Document Id Student para relacionar con stakeHolder es incorrecto: ".$userRelationShip[0]);
                throw new Exception("Student ID Document separator to relate to stakeholder is incorrect",93);
            }

            foreach($userRelationShip as $documentId){
                $userId = $this->findByDocumentIdUser($documentId);
                if(isset($userId)){
                    array_push($userIdRelationParent,$userId);
                }else{
                    Log::info("Estudiante con el DocumentId: " .$documentId. " no esta registrado para completar el proceso");
                    throw new Exception("Student with the document: ".$documentId." is not registered to complete the process",94);
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

            if($e->getCode()== 93 || $e->getCode()==94){
                throw $e;
            }
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
     * @param $name_file
     * @param $typeDocument
     * @return array
     * @throws \Exception
     */
    private function readCSV($csvFile,$name_file,$typeDocument){

        try{

            $delimiter = "";
            $file_handle = fopen($csvFile, 'r');

            // extrae cabecera del reporte
            $headerCsv = fgets($file_handle);

            Log::info('Tipo de Header:');
            Log::info($headerCsv);

            //validar el tipo de separador
            $columnsHeader = explode(";",$headerCsv);

            if(count($columnsHeader) > 1){
                Log::info("column header size: ".count($columnsHeader) . " type delimiter = ';'");
                $delimiter = ";";
            }
            else{
                throw new \Exception('Csv file error processing incorrect Delimiter',90);
            }

            //validar cabecera y cantidad de columnas

            $validateColumnAlfha = $this->validateHeader($columnsHeader);

            if((count($columnsHeader) != MAX_COLUMN_CSV_USERS) && ($typeDocument == REPORT_USERS)){

                throw new \Exception("Error wrong file format file ".$name_file.", please check to complete the process",91);
            }
            else if(!$validateColumnAlfha && $typeDocument == REPORT_USERS){

                throw new \Exception("Error wrong file format file ".$name_file.", please check to complete the process",91);
            }
            else if(count($columnsHeader) != MAX_COLUMN_CSV_SCHOOL && $typeDocument == REPORT_SCHOOL){

                throw new \Exception("Error wrong file format file ".$name_file.", please check to complete the process",91);
            }
            else if(!$validateColumnAlfha && $typeDocument == REPORT_SCHOOL){

                throw new \Exception("Error wrong file format file ".$name_file.", please check to complete the process",91);
            }
            else if((count($columnsHeader) != MAX_COLUMN_CSV_STOPS) &&($typeDocument == REPORT_STOPS)){

                throw new \Exception("Error wrong file format file ".$name_file.", please check to complete the process",91);
            }
            else if(!$validateColumnAlfha && $typeDocument == REPORT_STOPS){

                throw new \Exception("Error wrong file format file ".$name_file.", please check to complete the process",91);
            }

            // se extraelas demas lineas del documento  y se guarda en un arreglo
            while (!feof($file_handle)) {
                $line_of_text[] = fgetcsv($file_handle, 1024, $delimiter);
            }

        }
        catch (\Exception $e){
            Log::info("Error leyendo archivo csv: ".$e->getMessage());

            if($e->getCode() == 90 || $e->getCode() == 91){
                throw $e;
            }

        }finally{
            fclose($file_handle);
        }

        return $line_of_text;
    }


    private function validateHeader($header){

        $validate = true;

        foreach($header as $column){
            if((!preg_match('/^[A-z]+$/',trim(str_replace(" ","",$column))))){
                Log::info("No es un nombre de columna valido: ".trim(str_replace(" ","",$column)));
                $validate = false;
                break;
            }
        }

        return $validate;
    }

    private function validateDate($str){

        $validate = true;
        //validar formato de fecha
        if((!preg_match('/^(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/[0-9]{4}$/',$str))){
            $validate = false;
            Log::info("No es valido: ".$str);
        }


        return $validate;
    }

    private function cleanText($str){
        $cleanTxt = preg_replace('([^A-Za-z0-9])', '', $str);
        return $cleanTxt;
    }

    /**
     * @param $nameUser
     * @return int
     */
    private function findTypeUserId($nameUser){

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
    private function findSchoolId($idNumberSchool){

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
                    Log::info("No se pudo obtener longitud y latitude de la siguiente direccion: ".$address);
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


    private function findByStopsNumber($numberStop){

        $stopId = null;
        $stopTable = TableRegistry::get("Stops");
        $stopData = $stopTable->findByStopNumber($numberStop);

        if($stopData->count()>0){
            $stopData = $stopData->toArray();
            $stopId = $stopData[0]['stop_number'];
        }

        return $stopId;
    }

}