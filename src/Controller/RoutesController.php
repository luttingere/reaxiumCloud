<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 17/4/2016
 * Time: 08:18
 */

namespace App\Controller;


use Cake\Core\Exception\Exception;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;


class RoutesController extends ReaxiumAPIController
{


    /**
     * @api {post} /Routes/deviceGetRoutes get all routes relationship by device
     * @apiName deviceGetRoutes
     * @apiGroup Routes
     *
     *
     *
     * @apiParamExample {json} Request-Example:
     *
     *        {
     *          "ReaxiumParameters": {
     *          "ReaxiumDevice": {
     *          "device_id": "1"
     *          }
     *         }
     *      }
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
     *              "message": "Device has status invalid",
     *              "object": []
     *          }
     *      }
     *
     * @apiErrorExample Error-Response Invalid status:
     *      {
     *          "ReaxiumResponse": {
     *              "code": 404,
     *              "message": "Device has no routes",
     *              "object": []
     *          }
     *      }
     */
    public function deviceGetRoutes()
    {

        Log::info("Get Routes information Service invoked");

        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $failure = false;

        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {

                if (isset($jsonObject['ReaxiumParameters']['ReaxiumDevice'])) {

                    $deviceId = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id'];
                    $page = $jsonObject['ReaxiumParameters']['ReaxiumDevice']["page"];
                    $sortedBy = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']["sortedBy"]) ? 'Routes.route_name' : $jsonObject['ReaxiumParameters']['ReaxiumDevice']["sortedBy"];
                    $sortDir = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']["sortDir"]) ? 'desc' : $jsonObject['ReaxiumParameters']['ReaxiumDevice']["sortDir"];
                    $filter = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']["filter"]) ? '' : $jsonObject['ReaxiumParameters']['ReaxiumDevice']["filter"];
                    $limit = !isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']["limit"]) ? 10 : $jsonObject['ReaxiumParameters']['ReaxiumDevice']["limit"];


                    if (isset($deviceId)) {

                        $deviceId = $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id'];

                        $device_status = $this->getStatusDevice(array('device_id' => $deviceId));

                        if (isset($device_status)) {

                            if ($device_status[0]['status_id'] == ReaxiumApiMessages::$CODE_VALIDATE_STATUS) {

                                $this->loadModel('DeviceRoutes');

                                $routesDevice = $this->getRoutesByDevice($deviceId,$filter,$sortedBy,$sortDir);

                                $count = $routesDevice->count();
                                $this->paginate = array('limit' => $limit, 'page' => $page);
                                $routesFound = $this->paginate($routesDevice);


                                if ($routesFound->count()>0) {

                                    $maxPages = floor((($count - 1) / $limit) + 1);
                                    $routeFound = $routesFound->toArray();
                                    $response['ReaxiumResponse']['totalRecords'] = $count;
                                    $response['ReaxiumResponse']['totalPages'] = $maxPages;
                                    $response['ReaxiumResponse']['object'] = $routeFound;
                                    $response = parent::setSuccessfulResponse($response);

                                } else {
                                    $response['ReaxiumResponse']['code'] = "1";
                                    $response['ReaxiumResponse']['message'] = 'Device has no routes';
                                }
                            } else {
                                $response['ReaxiumResponse']['code'] = "2";
                                $response['ReaxiumResponse']['message'] = 'Device has status invalid';
                            }

                        } else {
                            $response['ReaxiumResponse']['code'] = "3";
                            $response['ReaxiumResponse']['message'] = 'Device has status invalid';
                        }


                    } else {
                        $response = parent::seInvalidParametersMessage($response);
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error: " . $e->getMessage());
                $response = $this->setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     * @param $arrayConditions
     * @return $this|array|\Cake\ORM\Table|null
     */
    private function getRoutesByDevice($idDevice,$filter,$sortedBy,$sortDir)
    {
        $devicesRouteTable = TableRegistry::get('DeviceRoutes');

        if (trim($filter) != "") {

            $whereCondition = array(array('OR' => array(
                array('Routes.route_number LIKE' => '%' . $filter . '%'),
                array('Routes.route_name LIKE' => '%' . $filter . '%'))));

            $deviceRouteFound = $devicesRouteTable->find()
                ->where($whereCondition)
                ->andWhere(array('Routes.status_id' => 1,'device_id' => $idDevice))
                ->contain(array('Routes'))
                ->order(array($sortedBy . ' ' . $sortDir));
        } else {
            $deviceRouteFound = $devicesRouteTable->find()
                ->where(array('device_id' => $idDevice))
                ->andWhere(array('Routes.status_id' => 1))
                ->contain(array('Routes'))
                ->order(array($sortedBy . ' ' . $sortDir));
        }


        return $deviceRouteFound;
    }

    /**
     * @param $arrayConditions
     * @return $this|\Cake\ORM\Table|null
     */
    private function getStatusDevice($arrayConditions)
    {
        $status_device = TableRegistry::get('ReaxiumDevice');
        $status_device = $status_device->find()->where($arrayConditions);

        if ($status_device->count() > 0) {
            $status_device = $status_device->toArray();
        } else {
            $status_device = null;
        }
        return $status_device;
    }


    /**
     * @api {post} /Routes/allStopsByRoute all routes relationship by device
     * @apiName allStopsByRoute
     * @apiGroup Routes
     *
     *
     *
     * @apiParamExample {json} Request-Example:
     *
     *        {
     *          "ReaxiumParameters": {
     *          "ReaxiumDevice": {
     *          "device_id": "1",
     *          "id_route":"1"
     *          }
     *         }
     *      }
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
     *              "message": "Device has status invalid",
     *              "object": []
     *          }
     *      }
     *
     * @apiErrorExample Error-Response Invalid status:
     *      {
     *          "ReaxiumResponse": {
     *              "code": 404,
     *              "message": "Not stops relation routes",
     *              "object": []
     *          }
     *      }
     */
    public function allStopsByRoute()
    {

        Log::info("Get Stops by routes information Service invoked");

        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            try {
                if (isset($jsonObject['ReaxiumParameters']['RoutesDevice'])) {

                    if (isset($jsonObject['ReaxiumParameters']['RoutesDevice']['device_id']) &&
                        isset($jsonObject['ReaxiumParameters']['RoutesDevice']['id_route'])
                    ) {

                        $device_id = $jsonObject['ReaxiumParameters']['RoutesDevice']['device_id'];
                        $routes_id = $jsonObject['ReaxiumParameters']['RoutesDevice']['id_route'];
                        $arrayConditions = array('id_route' => $routes_id);
                        $stops = $this->getStopsByRoutes($arrayConditions);

                        if (isset($stops)) {

                            $data = $this->getCountStudentByStop($routes_id);

                            if (isset($data)) {

                                $total_student_route = $this->totalStudentRoute($data);

                                for ($i = 0; $i < count($stops); ++$i) {

                                    for ($z = 0; $z < count($data); ++$z) {

                                        if ($stops[$i]['id_stop'] == $data[$z]['id_stop']) {
                                            $stops[$i]['stop']['students_by_stops'] = $data[$z]['students_by_stops'];
                                            break;
                                        }
                                    }
                                }

                                $response['ReaxiumResponse']['total_students_route'] = $total_student_route;
                                $response['ReaxiumResponse']['object'] = $stops;
                                $response = parent::setSuccessfulResponse($response);
                            } else {
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                                $response['ReaxiumResponse']['message'] = 'Not students relation stops';
                            }

                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'Not stops relation routes';
                        }

                    } else {
                        $response = parent::seInvalidParametersMessage($response);
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error: " . $e->getMessage());
                $response = $this->setInternalServiceError($response);
            }
        } else {

            $response = parent::seInvalidParametersMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));

    }

    /**
     * get stops by routes
     * @param $arrayConditions
     * @return $this|array|\Cake\ORM\Table|null
     */
    private function getStopsByRoutes($arrayConditions)
    {
        $stops = TableRegistry::get('RoutesStopsRelationship');
        $stops = $stops->find()->where($arrayConditions)->contain(array('Stops'));

        if ($stops->count() > 0) {
            $stops = $stops->toArray();
        } else {
            $stops = null;
        }

        return $stops;
    }

    /**
     * @param $idRoute
     * @return array|\Cake\ORM\Query|null
     *
     *     select rel.id_stop,count(su.user_id) as 'students_by_stops'
     *     from reaxium.routes_stops_relationship as rel
     *     inner join reaxium.stops_users as su
     *     on rel.id_stop  =su.id_stop
     *     inner join reaxium.users as u
     *     on u.user_id = su.user_id
     *     inner join reaxium.device_routes as dro
     *     on dro.id_route = 1
     *     and rel.id_route = 1
     *     and u.status_id =1
     *     and su.end_time between dro.start_date and dro.end_date
     *     group by rel.id_stop
     *
     */
    private function getCountStudentByStop($idRoute)
    {

        $stops_relationship = TableRegistry::get('RoutesStopsRelationship');
        $query = $stops_relationship->find();
        $query->select(['RoutesStopsRelationship.id_stop', 'students_by_stops' => $query->func()->count('su.user_id')]);
        $query->hydrate(false);
        $query->join([
            'su' => [
                'table' => 'stops_users',
                'type' => 'INNER',
                'conditions' => 'RoutesStopsRelationship.id_stop = su.id_stop'
            ],
            'u' => [
                'table' => 'users',
                'type' => 'INNER',
                'conditions' => 'u.user_id = su.user_id'
            ],
            'dro' => [
                'table' => 'device_routes',
                'type' => 'INNER',
                'conditions' => 'dro.id_route = ' . $idRoute
            ]
        ]);
        $query->andWhere(['su.end_time between dro.start_date and dro.end_date',
            'RoutesStopsRelationship.id_route' => $idRoute,
            'u.status_id' => 1]);
        $query->group('RoutesStopsRelationship.id_stop');

        if ($query->count() > 0) {
            $query = $query->toArray();
        } else {
            $query = null;
        }

        return $query;
    }

    /**
     * Total student in route
     * @param $arrayStudent
     * @return int
     */
    private function totalStudentRoute($arrayStudent)
    {
        $total = 0;

        foreach ($arrayStudent as $row) {
            $total = $total + $row['students_by_stops'];
        }

        return $total;
    }


    //TODO nuevo servicio pendiente documentacion

    public function allRoutesWithPagination()
    {

        Log::info("Get All Routes Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        try {

            if(isset($jsonObject['ReaxiumParameters']["page"])){

                $page = $jsonObject['ReaxiumParameters']["page"];
                $sortedBy = !isset($jsonObject['ReaxiumParameters']["sortedBy"])? 'route_name':$jsonObject['ReaxiumParameters']["sortedBy"];
                $sortDir = !isset($jsonObject['ReaxiumParameters']["sortDir"])? 'desc':$jsonObject['ReaxiumParameters']["sortDir"];
                $filter = !isset($jsonObject['ReaxiumParameters']["filter"])? '':$jsonObject['ReaxiumParameters']["filter"];
                $limit = !isset($jsonObject['ReaxiumParameters']["limit"])? 10:$jsonObject['ReaxiumParameters']["limit"];


                $routeTable = TableRegistry::get("Routes");

                if(trim($filter) != '' ){
                    $whereCondition = array(array('OR' => array(
                        array('route_number LIKE' => '%' . $filter . '%'),
                        array('route_name LIKE' => '%' . $filter . '%'),
                        array('route_address LIKE' => '%' . $filter . '%')
                    )));
                    $routeFound = $routeTable->find()
                        ->where($whereCondition)
                        ->andWhere(array('Routes.status_id'=>1))
                        ->order(array($sortedBy.' '.$sortDir));
                }else{
                    $routeFound = $routeTable->find()
                        ->where(array('Routes.status_id'=>1))
                        ->order(array($sortedBy.' '.$sortDir));
                }

                $count = $routeFound->count();
                $this->paginate = array('limit' => $limit, 'page' => $page);
                $routeFound = $this->paginate($routeFound);


                if ($routeFound->count() > 0) {
                    $maxPages = floor((($count - 1) / $limit) + 1);
                    $routeFound = $routeFound->toArray();
                    $response['ReaxiumResponse']['totalRecords'] = $count;
                    $response['ReaxiumResponse']['totalPages'] = $maxPages;
                    $response['ReaxiumResponse']['object'] = $routeFound;
                    $response = parent::setSuccessfulResponse($response);
                }
                else {
                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                    $response['ReaxiumResponse']['message'] = 'No Routes found';
                }

            }else{
                $response = parent::seInvalidParametersMessage($response);
            }

        } catch (\Exception $e) {
            Log::info('Error loading the biometric information for the user: ');
            Log::info($e->getMessage());
            $response = parent::setInternalServiceError($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));


    }

   //TODO nuevo servicio pendiente documentacion

    public function allStopsSystem()
    {

        Log::info("Get All Stops Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();

        try {
            $stopTable = TableRegistry::get("Stops");
            $stopList = $stopTable->find()->order("id_stop");

            if ($stopList->count() > 0) {
                $stopList = $stopList->toArray();
                $response = parent::setSuccessfulResponse($response);
                $response['ReaxiumResponse']['object'] = $stopList;
            } else {
                $response = parent::setInternalServiceError($response);
            }
        } catch (\Exception $e) {
            Log::info('Error loading the biometric information for the user: ');
            Log::info($e->getMessage());
            $response = parent::setInternalServiceError($response);
        } finally {
            $this->response->body(json_encode($response));
        }
    }


    //TODO agregar cpmentarios
    public function allStopsWithFilter(){

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
                    $stopFound = $stopTable->find()->where($whereCondition)->order(array('stop_name', 'stop_address'));
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

    //TODO nuevo servicio pendiente documentacion

    public function createRoutes(){

        Log::info("Create Route Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            $route_name = !isset($jsonObject["ReaxiumParameters"]["ReaxiumRoutes"]["route_name"]) ? null : $jsonObject["ReaxiumParameters"]["ReaxiumRoutes"]["route_name"];
            $route_number = !isset($jsonObject["ReaxiumParameters"]["ReaxiumRoutes"]["route_number"]) ? null : $jsonObject["ReaxiumParameters"]["ReaxiumRoutes"]["route_number"];
            $route_address = !isset($jsonObject["ReaxiumParameters"]["ReaxiumRoutes"]["route_address"]) ? null : $jsonObject["ReaxiumParameters"]["ReaxiumRoutes"]["route_address"];
            $stop_object = !isset($jsonObject["ReaxiumParameters"]["ReaxiumRoutes"]["stops"]) ? null : $jsonObject["ReaxiumParameters"]["ReaxiumRoutes"]["stops"];

            if (isset($route_name) && isset($route_number) && isset($route_address) && isset($stop_object)) {

                try {
                    $routeDataTable = TableRegistry::get("Routes");
                    $exitIdRoute = $this->existRoute($route_number,$route_name,$routeDataTable);
                    $arrayRoutesRelationStops = [];
                    $validate = true;

                    if($exitIdRoute == 0){

                        Log::info("Modo crear rutas");

                        $routeData = $routeDataTable->newEntity();
                        $routeData->route_number = $route_number;
                        $routeData->route_name = $route_name;
                        $routeData->route_address = $route_address;
                        $routeData = $routeDataTable->save($routeData);


                        if(isset($routeData)){

                            foreach($stop_object as $obj){
                                array_push($arrayRoutesRelationStops,["id_route"=>$routeData["id_route"],"id_stop"=>$obj["id_stop"]]);
                            }

                            $routeByStopsTable = TableRegistry::get("RoutesStopsRelationship");
                            $routeByStopsData = $routeByStopsTable->newEntities($arrayRoutesRelationStops);

                            $cont_stops_save = 0;

                            foreach($routeByStopsData as $entity){

                                if(!$routeByStopsTable->save($entity)){
                                    $validate = false;
                                    break;
                                }else{
                                    $cont_stops_save++;
                                }
                            }

                            $routeDataTable->updateAll(array("routes_stops_count"=>$cont_stops_save),array("id_route"=>$routeData["id_route"]));


                            if($validate){

                                Log::info("ruta creada con exito...");
                                Log::info(json_encode($arrayRoutesRelationStops));
                                $response = parent::setSuccessfulResponse($response);

                            }else{
                                Log::info('Error insertando elemento en tabla users_access_control');
                                $response = parent::setInternalServiceError($response);
                            }

                        }else{
                            Log::info("Ruta no pudo se creada");
                            $response = parent::setInternalServiceError($response);
                        }
                    }else{
                        //si existe se borran las paradas asociadas

                        Log::info("Mode editar rutas id:"+$exitIdRoute);
                        $routeByStopsTable = TableRegistry::get("RoutesStopsRelationship");
                        $routeByStopsTable->deleteAll(["id_route"=>$exitIdRoute]);

                            foreach($stop_object as $obj){
                                array_push($arrayRoutesRelationStops,["id_route"=>$exitIdRoute,"id_stop"=>$obj["id_stop"]]);
                            }

                            $routeByStopsData = $routeByStopsTable->newEntities($arrayRoutesRelationStops);

                            $cont_stops_save = 0;

                            foreach($routeByStopsData as $entity){

                                if(!$routeByStopsTable->save($entity)){
                                    $validate = false;
                                    break;
                                }else{
                                    $cont_stops_save++;
                                }
                            }

                            $routeDataTable->updateAll(array("routes_stops_count"=>$cont_stops_save),array("id_route"=>$exitIdRoute));

                            if($validate){
                                Log::info("ruta creada con exito...");
                                Log::info(json_encode($arrayRoutesRelationStops));
                                $response = parent::setSuccessfulResponse($response);

                            }else{
                                Log::info('Error insertando elemento en tabla users_access_control');
                                $response = parent::setInternalServiceError($response);
                            }
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


    private function existRoute($route_number,$route_name,$routeDataTable){

        $id_route = 0;
        $routeData = $routeDataTable->findByRouteNumberAndRouteName($route_number,$route_name);

        if($routeData->count() > 0){

            $routeData = $routeData->toArray();

            foreach($routeData as $obj){
                $id_route = $obj['id_route'];
            }
        }

        return $id_route;
    }


//TODO nuevo servicio pendiente documentacion
    public function createStops(){

        Log::info("Create Route Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            $stop_number = !isset($jsonObject["ReaxiumParameters"]["Stops"]["stop_number"]) ? null : $jsonObject["ReaxiumParameters"]["Stops"]["stop_number"];
            $stop_name = !isset($jsonObject["ReaxiumParameters"]["Stops"]["stop_name"]) ? null : $jsonObject["ReaxiumParameters"]["Stops"]["stop_name"];
            $stop_latitude = !isset($jsonObject["ReaxiumParameters"]["Stops"]["stop_latitude"]) ? null : $jsonObject["ReaxiumParameters"]["Stops"]["stop_latitude"];
            $stop_longitude = !isset($jsonObject["ReaxiumParameters"]["Stops"]["stop_longitude"]) ? null : $jsonObject["ReaxiumParameters"]["Stops"]["stop_longitude"];
            $stop_address = !isset($jsonObject["ReaxiumParameters"]["Stops"]["stop_address"]) ? null : $jsonObject["ReaxiumParameters"]["Stops"]["stop_address"];

            if(isset($stop_number) && isset($stop_name) && isset($stop_latitude) && isset($stop_longitude) && isset($stop_address)) {

                try{
                    $stopTable = TableRegistry::get("Stops");
                    $stopData= $stopTable->newEntity();
                    $stopData->stop_number = $stop_number;
                    $stopData->stop_name = $stop_name;
                    $stopData->stop_latitude = $stop_latitude;
                    $stopData->stop_longitude = $stop_longitude;
                    $stopData->stop_address = $stop_address;
                    $stopData = $stopTable->save($stopData);

                    Log::info("stops creada con exito...");
                    Log::info(json_encode($stopData));

                    $response = parent::setSuccessfulResponse($response);
                }
                catch(\Exception $e){
                    Log::info('Error create route in system:');
                    Log::info($e->getMessage());
                    $response = parent::setInternalServiceError($response);
                }
            }
            else{
                $response = parent::seInvalidParametersMessage($response);
            }
        }
        else{
            $response = parent::seInvalidParametersMessage($response);
        }
            $this->response->body(json_encode($response));
    }


    //TODO nuevo servicio pendiente documentacion
    public function getRouteByIdRelationStop(){

        Log::info("Get Route by Id Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();


        if(parent::validReaxiumJsonHeader($jsonObject)){

            $id_route = !isset($jsonObject["ReaxiumParameters"]["ReaxiumRoutes"]["id_route"]) ? null : $jsonObject["ReaxiumParameters"]["ReaxiumRoutes"]["id_route"];

            if(isset($id_route)){

                try{
                    $routeTable = TableRegistry::get("Routes");
                    $routeData = $routeTable
                        ->find()
                        ->where(array('id_route'=>$id_route))
                        ->contain(array('Stops'));

                    if($routeData->count() > 0){
                        $routeData = $routeData->toArray();

                        Log::info(json_encode($routeData));
                        $response['ReaxiumResponse']['object'] = $routeData;
                        $response = parent::setSuccessfulResponse($response);
                    }else{
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'No Routes found';
                        $response['ReaxiumResponse']['object']=[];
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

        }
        else{
            $response = parent::seInvalidParametersMessage($response);
        }

        $this->response->body(json_encode($response));
    }


    public function allRouteWithFilter(){

        Log::info("All Route information with filter Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if(parent::validReaxiumJsonHeader($jsonObject)){

            try{
                if(isset($jsonObject['ReaxiumParameters']['ReaxiumRoutes']['filter'])){

                    $routeTable = TableRegistry::get("Routes");
                    $filter = $jsonObject['ReaxiumParameters']['ReaxiumRoutes']['filter'];
                    $whereCondition = array(array('OR' => array(
                        array('route_number LIKE' => '%' . $filter . '%'),
                        array('route_name LIKE' => '%' . $filter . '%'))));

                    $routeFound = $routeTable->find()
                        ->where($whereCondition)
                        ->andWhere(array('Routes.status_id'=>1))
                        ->order(array('route_number','route_name'));

                    if ($routeFound->count() > 0) {
                        $routeFound = $routeFound->toArray();
                        $response['ReaxiumResponse']['object'] = $routeFound;
                        $response = parent::setSuccessfulResponse($response);
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'No Users found';
                    }
                }
                else{
                    $response = parent::seInvalidParametersMessage($response);
                }
            }
            catch (\Exception $e){
                Log::info("Error getting the route " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
            Log::info("Responde Object: " . json_encode($response));
            $this->response->body(json_encode($response));
        }

    }


    public function deleteRoute(){

        Log::info("deleting  Route service is running");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $id_route = null;

        if(parent::validReaxiumJsonHeader($jsonObject)){

            try{
               if(isset($jsonObject['ReaxiumParameters']['ReaxiumRoutes'])){
                   $this->loadModel('Routes');
                   $route = $this->Routes->newEntity();
                   $route = $this->Routes->patchEntity($route,$jsonObject['ReaxiumParameters']['ReaxiumRoutes']);

                   if(isset($route->id_route)){
                       $id_route =  $route->id_route;
                       $route = $this->getRouteInfo($id_route);
                       if(isset($route)){
                        $this->deleteARoute($id_route);
                           $response = parent::setSuccessfulDelete($response);
                       }
                       else{
                           $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                           $response['ReaxiumResponse']['message'] = 'Device Not found';
                       }
                   }
                   else{
                       Log::info('Entro aqui 1');
                       $response = parent::seInvalidParametersMessage($response);
                   }
               }
               else{
                   Log::info('Entro aqui 2');
                   $response = parent::seInvalidParametersMessage($response);
               }

            }
            catch(\Exception $e){
                Log::info("Error deleting the route: " . $id_route . " error:" . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }

        }else{
            Log::info('Entro aqui 3');
            $response = parent::setInvalidJsonMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    private function getRouteInfo($routeId){

        Log::info("Id Ruta: ".$routeId);

        $routeTable = TableRegistry::get('Routes');
        $routeData = $routeTable->findByIdRoute($routeId);

        if ($routeData->count() > 0) {
            $routeData = $routeData->toArray();
        } else {
            $routeData = null;
        }

        return $routeData;

    }


    private function deleteARoute($routeId){

        $this->loadModel('Routes');
        $this->Routes->updateAll(array('status_id' => '3'), array('id_route' => $routeId));
    }



}