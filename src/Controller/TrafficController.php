<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 28/03/2016
 * Time: 04:14 PM
 */

namespace App\Controller;


use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;
use App\Util\ReaxiumUtil;


class TrafficController extends ReaxiumAPIController
{

    /**
     * @api {post} /Traffic/trafficByUser get The Traffic Information of a User
     * @apiName trafficByUser
     * @apiGroup Traffic
     *
     * @apiParamExample {json} Request-Example:
     *
     * {
     * "ReaxiumParameters": {
     *      "Traffic": {
     *      "user_id": "1",
     *      "date_init": "2016-03-28 10:00:00",
     *      "date_end": "2016-03-28 18:00:00"
     *      }
     *    }
     * }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
        {
            "ReaxiumResponse": {
                "code": 0,
                "message": "SUCCESSFUL REQUEST",
                "object": [{
                    "datetime": "2016-03-28T16:27:11+0000",
                    "reaxium_device": {
                        "device_id": 1,
                        "device_name": "Test"},
                    "traffic_type": {
                        "traffic_type_name": "IN"
                        },
                    "user": {
                        "user_id": 1,
                        "first_name": "Eduardo",
                        "first_last_name": "Luttinger",
                        "document_id": "19044081"
                    }
            },{
                "datetime": "2016-03-28T16:26:56+0000",
               "reaxium_device": {
                    "device_id": 1,
                    "device_name": "Test"
                    },
                "traffic_type": {
                    "traffic_type_name": "OUT"
                    },
                "user": {
                    "user_id": 1,
                    "first_name": "Eduardo",
                    "first_last_name": "Luttinger",
                    "document_id": "19044081"
                }
            }
            ]
        }
      }
     *
     *
     * @apiErrorExample Error-Response Traffic Not Found:
     * {
     *   "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "No traffic found",
     *      "object": []
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {
     *     "ReaxiumResponse": {
     *          "code": 2,
     *          "message": "Invalid Parameters received, please checkout the api documentation",
     *          "object": []
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response Invalid Json Object:
     * {
     *  "ReaxiumResponse": {
     *      "code": 2,
     *      "message": "Invalid Parameters received, please checkout the api documentation",
     *      "object": []
     *      }
     *    }
     */
    public function trafficByUser()
    {
        Log::info("traffic information by user invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Traffic"])) {
                    if (isset($jsonObject['ReaxiumParameters']["Traffic"]['user_id'])
                        && isset($jsonObject['ReaxiumParameters']["Traffic"]['date_init'])
                        && isset($jsonObject['ReaxiumParameters']["Traffic"]['date_end'])
                    ) {

                        $userId = $jsonObject['ReaxiumParameters']["Traffic"]['user_id'];

                        Log::info($jsonObject['ReaxiumParameters']["Traffic"]['date_init']);

                        $dateInit = ReaxiumUtil::getDate($jsonObject['ReaxiumParameters']["Traffic"]['date_init']);
                        $dateEnd = ReaxiumUtil::getDate($jsonObject['ReaxiumParameters']["Traffic"]['date_end']);

                        $trafficFound = $this->getTrafficByUser($userId, $dateInit, $dateEnd);

                        if (isset($trafficFound)) {
                            $response['ReaxiumResponse']['object'] = $trafficFound;
                            $response = parent::setSuccessfulResponse($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'No traffic found';
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
     * @api {post} /Traffic/trafficByDevice Get The Traffic Information of a Reaxium Device
     * @apiName trafficByDevice
     * @apiGroup Traffic
     *
     * @apiParamExample {json} Request-Example:
     *
     * {
     *  "ReaxiumParameters": {
     *      "Traffic": {
     *      "user_id": "1",
     *      "date_init": "2016-03-28 10:00:00",
     *      "date_end": "2016-03-28 18:00:00"
     *      }
     *    }
     * }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
    {
        "ReaxiumResponse": {
           "code": 0,
           "message": "SUCCESSFUL REQUEST",
           "object": [{
                "datetime": "2016-03-28T16:27:11+0000",
                "reaxium_device": {
                "device_id": 1,
                "device_name": "Test"},
                "traffic_type": {
                "traffic_type_name": "IN"
            },
            "user": {
                "user_id": 1,
                "first_name": "Eduardo",
                "first_last_name": "Luttinger",
                "document_id": "19044081"
                }
    },{
            "datetime": "2016-03-28T16:26:56+0000",
                "reaxium_device": {
                    "device_id": 1,
                    "device_name": "Test"
                    },
            "traffic_type": {
                "traffic_type_name": "OUT"
            },
            "user": {
                "user_id": 4,
                "first_name": "Diana",
                "first_last_name": "Mogollon",
                "document_id": "6910229"
             }
         }]
        }
       }
     *
     *
     * @apiErrorExample Error-Response Traffic Not Found:
     * {
     *  "ReaxiumResponse": {
         * "code": 404,
         * "message": "No traffic found",
         * "object": []
     *   }
     * }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {
     *   "ReaxiumResponse": {
     *      "code": 2,
     *      "message": "Invalid Parameters received, please checkout the api documentation",
     *      "object": []
     *      }
     * }
     *
     *
     * @apiErrorExample Error-Response Invalid Json Object:
     * {
     *  "ReaxiumResponse": {
     *      "code": 2,
     *      "message": "Invalid Parameters received, please checkout the api documentation",
     *      "object": []
     *      }
     *  }
     */
    public function trafficByDevice()
    {
        Log::info("traffic information by device invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Traffic"])) {
                    if (isset($jsonObject['ReaxiumParameters']["Traffic"]['device_id'])
                        && isset($jsonObject['ReaxiumParameters']["Traffic"]['date_init'])
                        && isset($jsonObject['ReaxiumParameters']["Traffic"]['date_end'])){

                        $deviceId = $jsonObject['ReaxiumParameters']["Traffic"]['device_id'];
                        $dateInit = ReaxiumUtil::getDate($jsonObject['ReaxiumParameters']["Traffic"]['date_init']);
                        $dateEnd = ReaxiumUtil::getDate($jsonObject['ReaxiumParameters']["Traffic"]['date_end']);

                        $trafficFound = $this->getTrafficByDevice($deviceId, $dateInit, $dateEnd);

                        if (isset($trafficFound)) {
                            $response['ReaxiumResponse']['object'] = $trafficFound;
                            $response = parent::setSuccessfulResponse($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'No traffic found';
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
     * get all infromation of the traffic made by one user in an time period
     *
     * @param $userId
     * @param $dateInit
     * @param $dateEnd
     * @return array|\Cake\ORM\Query|null
     */
    private function getTrafficByUser($userId, $dateInit, $dateEnd)
    {
        $trafficTable = TableRegistry::get("Traffic");
        $trafficFound = $trafficTable->find('all',
            array('fields' => array('Traffic.datetime','Users.user_id','Users.first_name','Users.first_last_name','Users.document_id','TrafficType.traffic_type_name','ReaxiumDevice.device_id','ReaxiumDevice.device_name'),
                'conditions' => array('Traffic.user_id' => $userId,
                    'Traffic.datetime >=' => $dateInit,
                    'Traffic.datetime <=' => $dateEnd)))->contain(array('Users', 'TrafficType','ReaxiumDevice'));
        if ($trafficFound->count() > 0) {
            $trafficFound = $trafficFound->toArray();
        } else {
            $trafficFound = null;
        }
        return $trafficFound;
    }


    /**
     * get all infromation of the traffic made by one device in an time period
     *
     * @param $deviceId
     * @param $dateInit
     * @param $dateEnd
     * @return array|\Cake\ORM\Query|null
     */
    private function getTrafficByDevice($deviceId, $dateInit, $dateEnd)
    {
        $trafficTable = TableRegistry::get("Traffic");
        $trafficFound = $trafficTable->find('all',
            array('fields' => array('Traffic.datetime','Users.user_id','Users.first_name','Users.first_last_name','Users.document_id','TrafficType.traffic_type_name','ReaxiumDevice.device_id','ReaxiumDevice.device_name'),
                'conditions' => array('Traffic.device_id' => $deviceId,
                    'Traffic.datetime >=' => $dateInit,
                    'Traffic.datetime <=' => $dateEnd)))->contain(array('Users', 'TrafficType','ReaxiumDevice'));
        if ($trafficFound->count() > 0) {
            $trafficFound = $trafficFound->toArray();
        } else {
            $trafficFound = null;
        }
        return $trafficFound;
    }


}