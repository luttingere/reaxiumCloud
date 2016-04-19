<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 17/4/2016
 * Time: 08:18
 */

namespace App\Controller;


use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;


class RoutesController extends ReaxiumAPIController{


    /**
     * @api {post} /Routes/deviceGetRoutes all routes relationship by device
     * @apiName deviceGetRoutes
     * @apiGroup Routes
     *
     *
     *
     *  @apiParamExample {json} Request-Example:
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
     *      @apiErrorExample Error-Response Invalid status:
     *      {
     *          "ReaxiumResponse": {
     *              "code": 404,
     *              "message": "Device has no routes",
     *              "object": []
     *          }
     *      }
     */
    public function deviceGetRoutes(){

        Log::info("Get Routes information Service invoked");

        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $failure = false;

        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {

                if (isset($jsonObject['ReaxiumParameters']['ReaxiumDevice'])) {

                    if (isset($jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id'])) {


                        $deviceId = $jsonObject['ReaxiumParameters']['ReaxiumDevice']['device_id'];

                        $device_status = $this->getStatusDevice(array('device_id' => $deviceId));

                        if (isset($device_status)) {

                            if ($device_status[0]['status_id'] == ReaxiumApiMessages::$CODE_VALIDATE_STATUS) {

                                $this->loadModel('DeviceRoutes');
                                $arrayOfConditions = array('device_id' => $deviceId);

                                $routesDevice = $this->getRoutesByDevice($arrayOfConditions);

                                if (isset($routesDevice)) {

                                    Log::debug($routesDevice[0]);
                                    $response['ReaxiumResponse']['object'] = $routesDevice;
                                    $response = parent::setSuccessfulResponse($response);
                                } else {
                                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                                    $response['ReaxiumResponse']['message'] = 'Device has no routes';
                                }
                            } else {
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                                $response['ReaxiumResponse']['message'] = 'Device has status invalid';
                            }

                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
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
    private function getRoutesByDevice($arrayConditions)
    {
        $device = TableRegistry::get('DeviceRoutes');
        $device = $device->find()->where($arrayConditions)->contain(array('Routes'));

        if ($device->count() > 0) {

            $device = $device->toArray();
        } else {
            $device = null;
        }

        return $device;
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
     *  @apiParamExample {json} Request-Example:
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
     *      @apiErrorExample Error-Response Invalid status:
     *      {
     *          "ReaxiumResponse": {
     *              "code": 404,
     *              "message": "Not stops relation routes",
     *              "object": []
     *          }
     *      }
     */
    public function allStopsByRoute(){

        Log::info("Get Stops by routes information Service invoked");

        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if (parent::validReaxiumJsonHeader($jsonObject)) {

            try {
                if (isset($jsonObject['ReaxiumParameters']['RoutesDevice'])) {

                    if (isset($jsonObject['ReaxiumParameters']['RoutesDevice']['device_id']) &&
                        isset($jsonObject['ReaxiumParameters']['RoutesDevice']['id_route']))
                    {

                        $device_id = $jsonObject['ReaxiumParameters']['RoutesDevice']['device_id'];
                        $routes_id = $jsonObject['ReaxiumParameters']['RoutesDevice']['id_route'];
                        $arrayConditions = array('id_route' => $routes_id);
                        $stops = $this->getStopsByRoutes($arrayConditions);

                        if (isset($stops)) {

                            $data = $this->getCountStudentByStop($routes_id);

                            if(isset($data)){

                                $total_student_route = $this->totalStudentRoute($data);

                                for($i=0;$i<count($stops);++$i){

                                    for($z=0;$z<count($data);++$z){

                                        if($stops[$i]['id_stop'] == $data[$z]['id_stop']){
                                            $stops[$i]['stop']['students_by_stops'] = $data[$z]['students_by_stops'];
                                            break;
                                        }
                                    }
                                }

                                $response['ReaxiumResponse']['total_students_route'] = $total_student_route;
                                $response['ReaxiumResponse']['object'] = $stops;
                                $response = parent::setSuccessfulResponse($response);
                            }
                            else{
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                                $response['ReaxiumResponse']['message'] = 'Not students relation stops';
                            }

                        }
                        else{
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'Not stops relation routes';
                        }

                    }
                    else {
                        $response = parent::seInvalidParametersMessage($response);
                    }
                }
                else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            }
            catch (\Exception $e) {
                Log::info("Error: " . $e->getMessage());
                $response = $this->setInternalServiceError($response);
            }
        }
        else {

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
    private function getStopsByRoutes($arrayConditions){
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
    private function getCountStudentByStop($idRoute){

        $stops_relationship = TableRegistry::get('RoutesStopsRelationship');
        $query = $stops_relationship->find();
        $query->select(['RoutesStopsRelationship.id_stop','students_by_stops'=>$query->func()->count('su.user_id')]);
        $query->hydrate(false);
        $query->join([
            'su' => [
                'table' => 'stops_users',
                'type' => 'INNER',
                'conditions' => 'RoutesStopsRelationship.id_stop = su.id_stop'
            ],
            'u' =>[
                'table' => 'users',
                'type' => 'INNER',
                'conditions' => 'u.user_id = su.user_id'
            ],
            'dro' =>[
                'table' => 'device_routes',
                'type' => 'INNER',
                'conditions' => 'dro.id_route = ' . $idRoute
            ]
        ]);
       $query->andWhere(['su.end_time between dro.start_date and dro.end_date',
           'RoutesStopsRelationship.id_route' => $idRoute,
           'u.status_id'=>1]);
        $query->group('RoutesStopsRelationship.id_stop');

        if($query->count()>0){
            $query = $query->toArray();
        }else{
            $query=null;
        }

        return $query;
    }

    /**
     * Total student in route
     * @param $arrayStudent
     * @return int
     */
    private function totalStudentRoute($arrayStudent){
        $total = 0;

        foreach($arrayStudent as $row){
            $total = $total + $row['students_by_stops'];
        }

        return $total;
    }
}