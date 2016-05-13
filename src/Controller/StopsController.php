<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 7/5/2016
 * Time: 07:06
 */

namespace App\Controller;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;

class StopsController extends ReaxiumAPIController
{


    /**
     * @api {post} /Stops/allStopsWithPagination get all stops
     * @apiName allStopsWithPagination
     * @apiGroup Stops
     *
     *
     *
     * @apiParamExample {json} Request-Example:
     *
     *        {
     *          "ReaxiumParameters": {
     *          ReaxiumStops:{
     *          "page": 1,
     *          "limit":5,
     *          "sortDir": "asc",
     *          "sortedBy": "route_name",
     *          "filter": ""
     *          }
     *
     *      }
     *  }
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
     * @apiErrorExample Error-Response Invalid status:
     *      {
     *          "ReaxiumResponse": {
     *              "code": 1,
     *              "message": "No Stops register",
     *              "object": []
     *          }
     *      }
     *
     *
     */
    public function allStopsWithPagination(){

        Log::info("Get All Stops Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            try {

                if (isset($jsonObject['ReaxiumParameters']['ReaxiumStops'])) {

                    $page = $jsonObject['ReaxiumParameters']['ReaxiumStops']["page"];
                    $sortedBy = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']["sortedBy"]) ?
                        'Stops.stop_name' : $jsonObject['ReaxiumParameters']['ReaxiumStops']["sortedBy"];

                    $sortDir = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']["sortDir"]) ?
                        'desc' : $jsonObject['ReaxiumParameters']['ReaxiumStops']["sortDir"];

                    $filter = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']["filter"]) ?
                        '' : $jsonObject['ReaxiumParameters']['ReaxiumStops']["filter"];

                    $limit = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']["limit"]) ?
                        10 : $jsonObject['ReaxiumParameters']['ReaxiumStops']["limit"];

                    $stopList = $this->getStops($filter, $sortedBy, $sortDir);

                    $count = $stopList->count();
                    $this->paginate = array('limit' => $limit, 'page' => $page);
                    $stopsFound = $this->paginate($stopList);


                    if ($stopsFound->count() > 0) {

                        $maxPages = floor((($count - 1) / $limit) + 1);
                        $stopsFound = $stopsFound->toArray();
                        $response['ReaxiumResponse']['totalRecords'] = $count;
                        $response['ReaxiumResponse']['totalPages'] = $maxPages;
                        $response['ReaxiumResponse']['object'] = $stopsFound;
                        $response = parent::setSuccessfulResponse($response);

                    } else {
                        $response['ReaxiumResponse']['code'] = "1";
                        $response['ReaxiumResponse']['message'] = 'No Stops register';
                    }

                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }

            } catch (\Exception $e) {
                Log::info('Error get all stops of system: ');
                Log::info($e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::seInvalidParametersMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    /**
     * @param $filter
     * @param $sortedBy
     * @param $sortDir
     * @return $this
     */
    private function getStops($filter, $sortedBy, $sortDir)
    {

        $stopTable = TableRegistry::get("Stops");

        if (trim($filter) != "") {

            $whereCondition = array(array('OR' => array(
                array('Stops.stop_number LIKE' => '%' . $filter . '%'),
                array('Stops.stop_name LIKE' => '%' . $filter . '%'))));

            $stopList = $stopTable->find()
                ->where($whereCondition)
                ->andWhere(array('status_id'=>1))
                ->order(array($sortedBy . ' ' . $sortDir));

        } else {
            $stopList = $stopTable->find()
                ->where(array('status_id'=>1))
                ->order(array($sortedBy . ' ' . $sortDir));
        }

        return $stopList;

    }


    /**
     * @api {post} /Stops/allStopsWithFilter get all stops with filter
     * @apiName allStopsWithPagination
     * @apiGroup Stops
     *
     *
     *
     * @apiParamExample {json} Request-Example:
     *
     *        {
     *          "ReaxiumParameters": {
     *          "Stops": {
     *                      "filter": "ro"
     *                      }
     *                  }
     *              }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {"ReaxiumResponse": {
     *              "code": "0",
     *              "message": "SUCCESSFUL REQUEST ",
     *               "object": []
     *                }
     *              }
     *
     *
     * @apiErrorExample Error-Response Invalid status:
     *      {
     *          "ReaxiumResponse": {
     *              "code": 404,
     *              "message": "No Users found",
     *              "object": []
     *          }
     *      }
     *
     *
     */

    public function allStopsWithFilter()
    {

        Log::info("All User information with filter Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Stops"]["filter"])) {

                    $stopTable = TableRegistry::get('Stops');
                    $filter = $jsonObject['ReaxiumParameters']["Stops"]["filter"];
                    $whereCondition = array(array('OR' => array(
                        array('stop_number LIKE' => '%' . $filter . '%'),
                        array('stop_name LIKE' => '%' . $filter . '%'),
                        array('stop_address LIKE' => '%' . $filter . '%')
                    )));
                    $stopFound = $stopTable->find()
                        ->where($whereCondition)
                        ->andWhere(array('status_id'=>1))
                        ->order(array('stop_name', 'stop_address'));

                    if ($stopFound->count() > 0) {
                        $stopFound = $stopFound->toArray();
                        $response['ReaxiumResponse']['object'] = $stopFound;
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
     * @api {post} /Stops/createStops create stops
     * @apiName allStopsWithPagination
     * @apiGroup Stops
     *
     *
     *
     * @apiParamExample {json} Request-Example:
     *
     *        {
     *          "ReaxiumParameters": {
     *          "Stops": {
     *                      "filter": "ro"
     *                      }
     *                  }
     *              }
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
     * @apiErrorExample Error-Response Invalid status:
     *      {
     *          "ReaxiumResponse": {
     *              "code": 404,
     *              "message": "No Users found",
     *              "object": []
     *          }
     *      }
     *
     *
     */
    public function createStops(){

        Log::info("Create Route Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $validate=true;

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            $arrayObj = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']['object']) ?
                null : $jsonObject['ReaxiumParameters']['ReaxiumStops']['object'];

            if (isset($arrayObj)) {

                try {
                    $stopTable = TableRegistry::get("Stops");

                    foreach($arrayObj as $entity){

                        $stopData = $stopTable->newEntity();
                        $stopData->stop_number = $entity['stop_number'];
                        $stopData->stop_name = $entity['stop_name'];
                        $stopData->stop_latitude = $entity['stop_latitude'];
                        $stopData->stop_longitude = $entity['stop_longitude'];
                        $stopData->stop_address = $entity['stop_address'];

                        if (!$stopTable->save($stopData)) {
                            $validate = false;
                            break;
                        }
                    }

                    if ($validate) {
                        $response = parent::setSuccessfulResponse($response);
                    }
                    else {
                        Log::info('Error insertando elemento en tabla users_access_control');
                        $response = parent::setInternalServiceError($response);
                    }

                } catch (\Exception $e) {
                    Log::info('Error create route in system:');
                    Log::info($e->getMessage());
                    $response = parent::setInternalServiceError($response);
                }
            } else {
                $response = parent::seInvalidParametersMessage($response);
            }
        } else {
            $response = parent::seInvalidParametersMessage($response);
        }
        $this->response->body(json_encode($response));
    }


    /**
     * @api {post} /Stops/associationStopAndUser create stops
     * @apiName allStopsWithPagination
     * @apiGroup Stops
     *
     *
     *
     * @apiParamExample {json} Request-Example:
     *
     *        {
     *             "ReaxiumParameters": {
     *              "ReaxiumStops":{
     *              "object":[
     *              {
     *                  "id_stop":"15",
     *                  "user_id":"1",
     *                  "start_time":"09:10:00",
     *                  "end_time":"09:12:00"
     *              },
     *              {
     *                  "id_stop":"16",
     *                  "user_id":"1",
     *                  "start_time":"09:10:00",
     *                  "end_time":"09:12:00"
     *              }
     *           ]
     *          }
     *      }
     *  }
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
     * @apiErrorExample Error-Response Invalid status:
     *      {
     *          "ReaxiumResponse": {
     *              "code": 404,
     *              "message": "No Users found",
     *              "object": []
     *          }
     *      }
     *
     *
     */
    public function associationStopAndUser(){

        Log::info("Create association Stop And User invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $validate = true;

        if(parent::validReaxiumJsonHeader($jsonObject)){

            try{

                $arrayObj = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']['object']) ?
                    null : $jsonObject['ReaxiumParameters']['ReaxiumStops']['object'];

                Log::info(json_encode($arrayObj));

                if(isset($arrayObj)){

                    $stopsUserTable = TableRegistry::get("StopsUsers");

                    //$stopsUserData = $stopsUserTable->newEntities($arrayObj); //xq no inserta la fechas asi??

                    foreach($arrayObj as $entity){

                        if(!$this->checkExistRecord($stopsUserTable,
                            $entity['id_stop'],$entity['user_id'],$entity['start_time'],$entity['end_time'])){

                            $stopsUserData = $stopsUserTable->newEntity();
                            $stopsUserData->id_stop = $entity['id_stop'];
                            $stopsUserData->user_id = $entity['user_id'];
                            $stopsUserData->start_time = $entity['start_time'];
                            $stopsUserData->end_time = $entity['end_time'];

                            if (!$stopsUserTable->save($stopsUserData)) {
                                $validate = false;
                                break;
                            }
                        }else{
                            Log::info("Registro ya existe "."id_stop:".$entity['id_stop']." user_id: "
                                .$entity['user_id']." start_time: ".$entity['start_time']." end_time: ".$entity['end_time']);
                        }
                    }

                    if ($validate) {
                        $response = parent::setSuccessfulResponse($response);
                    } else {
                        Log::info('Error insertando elemento en tabla users_access_control');
                        $response = parent::setInternalServiceError($response);
                    }

                }else{
                    $response = parent::seInvalidParametersMessage($response);
                }
            }catch (\Exception $e){
                Log::info('Error create relation stops users: ' .$e->getMessage());
                $response = parent::setInternalServiceError($response);
            }

        }else{
            $response = parent::seInvalidParametersMessage($response);
        }
        Log::info(json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     * @param $stopsUserTable
     * @param $id_stop
     * @param $id_user
     * @param $start_date
     * @param $end_date
     * @return bool
     */
    private function checkExistRecord($stopsUserTable,$id_stop,$id_user,$start_date,$end_date){

        $validate = false;
        $arrayCondition = array('id_stop'=>$id_stop,'user_id'=>$id_user,'start_time'=>$start_date,'end_time'=>$end_date);
        $stopsUserData = $stopsUserTable->find()->where($arrayCondition);
        if($stopsUserData->count()>0){$validate = true;}

        return $validate;
    }



    public function deleteStops(){

        Log::info("Create association Stop And User invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();


        if(parent::validReaxiumJsonHeader($jsonObject)){

            try{
                $id_stop = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']['id_stop']) ?
                    null : $jsonObject['ReaxiumParameters']['ReaxiumStops']['id_stop'];

                if(isset($id_stop)){

                    $this->loadModel('Stops');
                    $this->Stops->updateAll(array('status_id' => '3'), array('id_stop' => $id_stop));
                    $response = parent::setSuccessfulResponse($response);
                }
                else{
                    $response = parent::seInvalidParametersMessage($response);
                }
            }
            catch (\Exception $e){
                Log::info('Error create relation stops users: ' .$e->getMessage());
                $response = parent::setInternalServiceError($response);
            }

        }else{
            $response = parent::seInvalidParametersMessage($response);
        }

        Log::info(json_encode($response));
        $this->response->body(json_encode($response));
    }


    public function getStopById(){

        Log::info("Stop by id service invoke");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if(parent::validReaxiumJsonHeader($jsonObject)){

            try{

                $stop_id = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']['id_stop']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumStops']['id_stop'];

                if(isset($stop_id)){
                    $stopsTable = TableRegistry::get("Stops");
                    $stopsData = $stopsTable->find()->where(array('id_stop'=>$stop_id,'status_id'=>1));

                    if($stopsData->count() > 0){
                        $stopsData = $stopsData->toArray();
                        $response['ReaxiumResponse']['object'] = $stopsData;
                        $response = parent::setSuccessfulResponse($response);

                    }else{
                        $response['ReaxiumResponse']['code'] =ReaxiumApiMessages::$NOT_FOUND_CODE ;
                        $response['ReaxiumResponse']['message'] = "No stop found";
                    }
                }else{
                    $response = parent::seInvalidParametersMessage($response);
                }
            }
            catch (\Exception $e){
                Log::info('Error get stop by id: ' .$e->getMessage());
                $response = parent::setInternalServiceError($response);
            }

        }else{
            $response = parent::seInvalidParametersMessage($response);
        }

        Log::info(json_encode($response));
        $this->response->body(json_encode($response));
    }


    public function getUserByStops(){

        Log::info("Get All User by Stop Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();


        if(parent::validReaxiumJsonHeader($jsonObject)){

            try{

                if(isset($jsonObject['ReaxiumParameters']['ReaxiumStops'])){

                    $stop_id = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']['id_stop']) ? null :$jsonObject['ReaxiumParameters']['ReaxiumStops']['id_stop'];
                    $page = $jsonObject['ReaxiumParameters']['ReaxiumStops']["page"];
                    $sortedBy = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']["sortedBy"]) ? 'Users.first_name' : $jsonObject['ReaxiumParameters']['ReaxiumStops']["sortedBy"];
                    $sortDir = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']["sortDir"]) ? 'desc' : $jsonObject['ReaxiumParameters']['ReaxiumStops']["sortDir"];
                    $filter = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']["filter"]) ? '' : $jsonObject['ReaxiumParameters']['ReaxiumStops']["filter"];
                    $limit = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']["limit"]) ? 10 : $jsonObject['ReaxiumParameters']['ReaxiumStops']["limit"];

                    if(isset($stop_id)){

                        $stops_status = $this->getStatusStop(array('id_stop'=>$stop_id));

                        if(isset($stops_status)){
                            if($stops_status[0]['status_id'] == ReaxiumApiMessages::$CODE_VALIDATE_STATUS){
                                //$this->loadModel('StopsUsers');

                                $stopsUserFound = $this->getUserByStop($stop_id,$filter,$sortedBy,$sortDir);

                                $count = $stopsUserFound->count();
                                $this->paginate = array('limit' => $limit, 'page' => $page);
                                $stopsUserFound = $this->paginate($stopsUserFound);


                                if ($stopsUserFound->count()>0) {

                                    $maxPages = floor((($count - 1) / $limit) + 1);
                                    $stopsUserFound = $stopsUserFound->toArray();
                                    $response['ReaxiumResponse']['totalRecords'] = $count;
                                    $response['ReaxiumResponse']['totalPages'] = $maxPages;
                                    $response['ReaxiumResponse']['object'] = $stopsUserFound;
                                    $response = parent::setSuccessfulResponse($response);

                                } else {
                                    $response['ReaxiumResponse']['code'] = "1";
                                    $response['ReaxiumResponse']['message'] = 'The stop has no members';
                                }
                            }else{
                                $response['ReaxiumResponse']['code'] = "2";
                                $response['ReaxiumResponse']['message'] = 'Stop has status invalid';
                            }
                        }

                    }else{
                        $response['ReaxiumResponse']['code'] = "2";
                        $response['ReaxiumResponse']['message'] = 'Stop has status invalid';
                    }

                }else{
                    $response = parent::seInvalidParametersMessage($response);
                }
            }
            catch(\Exception $e){
                Log::info('Error get stop by id: ' .$e->getMessage());
                $response = parent::setInternalServiceError($response);
            }

        }else{
            $response = parent::seInvalidParametersMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    private function getStatusStop($arrayConditions){

        $status_stop = TableRegistry::get('Stops');
        $status_stop = $status_stop->find()->where($arrayConditions);

        if ($status_stop->count() > 0) {
            $status_stop = $status_stop->toArray();
        } else {
            $status_stop = null;
        }
        return $status_stop;

    }


    private function getUserByStop($idDevice,$filter,$sortedBy,$sortDir){

        $stopsUsersTable = TableRegistry::get('StopsUsers');

        if(trim($filter) !=""){

            $whereCondition = array(array('OR' => array(
                array('Users.first_name LIKE' => '%' . $filter . '%'),
                array('Users.first_last_name LIKE' => '%' . $filter . '%'),
                array('Users.document_id LIKE' => '%' . $filter . '%'))));

            $stopUserFound = $stopsUsersTable->find()
                ->where($whereCondition)
                ->andWhere(array('Users.status_id' => 1,'id_stop' => $idDevice))
                ->contain(array('Users'))
                ->order(array($sortedBy . ' ' . $sortDir));

        }else{
            $stopUserFound = $stopsUsersTable->find()
                ->where(array('id_stop' => $idDevice))
                ->andWhere(array('Users.status_id' => 1))
                ->contain(array('Users'))
                ->order(array($sortedBy . ' ' . $sortDir));
        }

        return $stopUserFound;
    }


    public function deleteUserRelationShipStop(){

        Log::info("Stop by id service invoke");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();


        if(parent::validReaxiumJsonHeader($jsonObject)){

            try{

                $id_stops_user = !isset($jsonObject['ReaxiumParameters']['ReaxiumStops']['id_stops_user']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumStops']['id_stops_user'];

                if(isset($id_stops_user)){
                    $userByStopsTable = TableRegistry::get("StopsUsers");
                    $userByStopsTable->deleteAll(array('id_stops_user'=>$id_stops_user));
                    $response = parent::setSuccessfulDelete($response);

                }else{
                    $response = parent::seInvalidParametersMessage($response);
                }

            }
            catch(\Exception $e){
                Log::info('Error get stop by id: ' .$e->getMessage());
                $response = parent::setInternalServiceError($response);
            }

        }else{
            $response = parent::seInvalidParametersMessage($response);
        }

        Log::info(json_encode($response));
        $this->response->body(json_encode($response));
    }

}