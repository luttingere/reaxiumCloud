<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 07/04/2016
 * Time: 01:05 PM
 */

namespace App\Controller;

use Cake\Core\Exception\Exception;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;
use Symfony\Component\Console\Helper\Table;


class BusinessObjectController extends ReaxiumAPIController
{


    /**
     * @api {post} /BusinessObject/schoolBusInfo Get a SchoolBus Information by its ID
     * @apiName schoolBusInfo
     * @apiGroup Business
     *
     * @apiParamExample {json} Request-Example:
     *
     * {
     * "ReaxiumParameters": {
     * "ReaxiumBusiness": {
     * "schoolbus_id": "1"
     * }
     * }
     * }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     * "ReaxiumResponse": {
     * "code": 0,
     * "message": "SUCCESSFUL REQUEST",
     * "object": [
     * {
     * "business_object_id": 1,
     * "schoolbus_serial_code": "BUS1",
     * "schoolbus_name": null,
     * "seats": 60,
     * "device_id": 1,
     * "user_id": 1,
     * "status_id": 1,
     * "reaxium_device": {
     * "device_id": 1,
     * "device_name": "Test",
     * "device_description": "Esto es una prueba",
     * "status_id": 3
     * },
     * "business": [
     * {
     * "business_id": 1,
     * "business_name": "Reaxium Admin System",
     * "business_id_number": "Reaxium-0001",
     * "_joinData": {
     * "business_object_id": 1,
     * "business_id": 1
     * }
     * }
     * ],
     * "driver": {
     * "user_id": 1,
     * "document_id": "19044081",
     * "first_name": "Eduardo",
     * "second_name": "Jose",
     * "first_last_name": "Luttinger",
     * "second_last_name": "Mogollon",
     * "status_id": 1,
     * "user_type_id": 1,
     * "user_photo": null,
     * "business_id": 1
     * },
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
     * @apiErrorExample Error-Response SchoolBus Not Found:
     * {
     *  "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "School BUS Not found",
     *      "object": []
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {
     *  "ReaxiumResponse": {
     *      "code": 2,
     *      "message": "Invalid Parameters received, please checkout the api documentation",
     *      "object": []
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
     *       }
     *     }
     */
    public function schoolBusInfo()
    {
        Log::info("School bus information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumBusiness"])) {

                    $schoolBusId = $jsonObject['ReaxiumParameters']["ReaxiumBusiness"]["schoolbus_id"];
                    $schoolBus = $this->getAllSchoolById($schoolBusId);

                    if (sizeof($schoolBus) > 0) {

                        $response['ReaxiumResponse']['object'] = $schoolBus;
                        $response = parent::setSuccessfulResponse($response);

                    } else {

                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'School Bus Not found';

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
     * @api {post} /BusinessObject/allSchoolBusInfo Get all SchoolBuses of a business id
     * @apiName allSchoolBusInfo
     * @apiGroup Business
     *
     * @apiParamExample {json} Request-Example:
     *
     * {
     * "ReaxiumParameters": {
     * "ReaxiumBusiness": {
     * "business_id": "1"
     * }
     * }
     * }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     * "ReaxiumResponse": {
     * "code": 0,
     * "message": "SUCCESSFUL REQUEST",
     * "object": [
     * {
     * "business_object_id": 1,
     * "schoolbus_serial_code": "BUS1",
     * "seats": 60,
     * "schoolbus_name": null
     * },
     * {
     * "business_object_id": 2,
     * "schoolbus_serial_code": "BUS2",
     * "seats": 64,
     * "schoolbus_name": null
     * },
     * {
     * "business_object_id": 3,
     * "schoolbus_serial_code": "BUS3",
     * "seats": 10,
     * "schoolbus_name": null
     * }
     * ]
     * }
     * }
     *
     *
     * @apiErrorExample Error-Response SchoolBuses Not Found:
     * {
     *  "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "Not found any bus by this business id",
     *      "object": []
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {
     *  "ReaxiumResponse": {
     *      "code": 2,
     *      "message": "Invalid Parameters received, please checkout the api documentation",
     *      "object": []
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
     *       }
     *     }
     */
    public function allSchoolBusInfo()
    {
        Log::info("All School bus information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumBusiness"]["business_id"])) {

                    $businessId = $jsonObject['ReaxiumParameters']["ReaxiumBusiness"]["business_id"];

                    $schoolBuses = $this->getAllSchoolBuses($businessId);

                    if (sizeof($schoolBuses) > 0) {

                        $response['ReaxiumResponse']['object'] = $schoolBuses;
                        $response = parent::setSuccessfulResponse($response);

                    } else {

                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'School Buses Not found';

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
     * @api {post} /BusinessObject/allSchoolBusInfoFiltered Get all SchoolBuses of a business id a filter
     * @apiName allSchoolBusInfoFiltered
     * @apiGroup Business
     *
     * @apiParamExample {json} Request-Example:
     *
     * {
     * "ReaxiumParameters": {
     * "ReaxiumBusiness": {
     * "business_id": "1"
     * "filter":"BUS1"
     * }
     * }
     * }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     * "ReaxiumResponse": {
     * "code": 0,
     * "message": "SUCCESSFUL REQUEST",
     * "object": [
     * {
     * "business_object_id": 1,
     * "schoolbus_serial_code": "BUS1",
     * "seats": 60,
     * "schoolbus_name": null
     * }
     * ]
     * }
     * }
     *
     *
     * @apiErrorExample Error-Response SchoolBuses Not Found:
     * {
     *  "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "Not found any bus by this business and filter",
     *      "object": []
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {
     *  "ReaxiumResponse": {
     *      "code": 2,
     *      "message": "Invalid Parameters received, please checkout the api documentation",
     *      "object": []
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
     *       }
     *     }
     */
    public function allSchoolBusInfoFiltered()
    {
        Log::info("All School bus information by filter Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumBusiness"]["business_id"])) {

                    $businessId = $jsonObject['ReaxiumParameters']["ReaxiumBusiness"]["business_id"];
                    $filter = $jsonObject['ReaxiumParameters']["ReaxiumBusiness"]["filter"];

                    $schoolBuses = $this->getAllSchoolBusesFiltered($businessId, $filter);

                    if (sizeof($schoolBuses) > 0) {

                        $response['ReaxiumResponse']['object'] = $schoolBuses;
                        $response = parent::setSuccessfulResponse($response);

                    } else {

                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'School Buses Not found';

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
     * @api {post} /BusinessObject/createASchoolBus Create a SchoolBus in the system
     * @apiName createASchoolBus
     * @apiGroup Business
     *
     * @apiParamExample {json} Request-Example:
     *
     * {
     * "ReaxiumParameters": {
     * "ReaxiumBusiness": {
     * "business_id":"1",
     * "SchoolBus": {
     * "schoolbus_serial_code":"BUZZZ001",
     * "schoolbus_name":"Autobus Azul 001",
     * "seats":"50",
     * "seats":"50",
     * "device_id":"1",
     * "user_id":"1",
     * "status_id":"1"
     * }
     * }
     * }
     * }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
          {
              "ReaxiumResponse": {
                  "code": 0,
                  "message": "SUCCESSFUL REQUEST",
                  "object": {
                      "schoolbus_serial_code": "BUZZZ001",
                      "schoolbus_name": "Autobus Azul 001",
                      "seats": 50,
                      "device_id": 1,
                      "user_id": 1,
                      "status_id": 1,
                      "business_object_id": 8
                  }
              }
          }
     *
     *
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {
     *  "ReaxiumResponse": {
     *      "code": 2,
     *      "message": "Invalid Parameters received, please checkout the api documentation",
     *      "object": []
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
     *       }
     *     }
     */
    public function createASchoolBus()
    {
        Log::info("All School bus information by filter Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumBusiness"]["SchoolBus"])) {

                    $schoolBus = $jsonObject['ReaxiumParameters']["ReaxiumBusiness"]["SchoolBus"];

                    $schoolBusCreated = $this->createABus($schoolBus);

                    if ($schoolBusCreated) {

                        if (isset($jsonObject['ReaxiumParameters']["ReaxiumBusiness"]["business_id"])) {
                            $businessId = $jsonObject['ReaxiumParameters']["ReaxiumBusiness"]["business_id"];
                            $this->addABusToABusiness($businessId, $schoolBusCreated['business_object_id']);
                            Log::info("Schoolbus ID: " . $schoolBusCreated['business_object_id'] . " was associated with businessId: " . $businessId);
                        }

                        $response['ReaxiumResponse']['object'] = $schoolBusCreated;
                        $response = parent::setSuccessfulResponse($response);

                    } else {

                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'School Buses was not saved properly';

                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error: " . $e->getMessage());
                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                $response['ReaxiumResponse']['message'] = 'SchoolBus already exist in the system';
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     * @api {post} /BusinessObject/deleteASchoolBus Delete a SchoolBus Information from a business by its ID
     * @apiName deleteASchoolBus
     * @apiGroup Business
     *
     * @apiParamExample {json} Request-Example:
     *
          {
          "ReaxiumParameters": {
          "ReaxiumBusiness": {
          "schoolbus_id": "1",
          "business_id": "1"
          }
          }
          }
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *    {
            "ReaxiumResponse": {
            "code": 0,
            "message": "DELETED SUCCESSFUL",
            "object": []
            }
          }
     *
     * @apiErrorExample Error-Response SchoolBus Not Found:
     * {
     *  "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "School BUS Not found",
     *      "object": []
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {
     *  "ReaxiumResponse": {
     *      "code": 2,
     *      "message": "Invalid Parameters received, please checkout the api documentation",
     *      "object": []
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
     *       }
     *     }
     */
    public function deleteASchoolBus()
    {
        Log::info("School bus information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["ReaxiumBusiness"])) {

                    $schoolBusId = $jsonObject['ReaxiumParameters']["ReaxiumBusiness"]["schoolbus_id"];
                    $businessId = $jsonObject['ReaxiumParameters']["ReaxiumBusiness"]["business_id"];
                    $result = $this->getAllSchoolById($schoolBusId);
                    if(sizeof($result)>0){
                        $this->deleteSchoolBus($businessId,$schoolBusId);
                        $response = parent::setSuccessfulDelete($response);
                    }else{
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'School Bus Not found';
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
     * get all buses of the system filtered by ID
     * @param $businessId
     * @return array|\Cake\ORM\Query
     */
    private function getAllSchoolBuses($businessId)
    {
        try {
            $schoolBusTable = TableRegistry::get("SchoolBus");
            $result = $schoolBusTable->find('all', [
                'join' => [
                    [
                        'table' => 'business_relationship',
                        'alias' => 'business_relationship',
                        'type' => 'INNER',
                        'conditions' => array('business_relationship.business_object_id = SchoolBus.business_object_id')
                    ], [
                        'table' => 'business',
                        'alias' => 'business',
                        'type' => 'INNER',
                        'conditions' => array('business.business_id = business_relationship.business_id')
                    ],
                ],
                'fields' => ['SchoolBus.business_object_id', 'SchoolBus.schoolbus_serial_code', 'SchoolBus.seats', 'SchoolBus.schoolbus_name'],
                'conditions' => array('business.business_id' => $businessId),
                'order' => ['SchoolBus.schoolbus_name']
            ]);
            if ($result->count() > 0) {
                $result = $result->toArray();
            }
        } catch (\Exception $e) {
            $result = array();
            Log::info($e->getMessage());
        }
        return $result;
    }

    /**
     * get all the buses of the system filtered by businessId and any other literal
     * @param $businessId
     * @param $filter
     * @return array|\Cake\ORM\Query
     */
    private function getAllSchoolBusesFiltered($businessId, $filter)
    {
        try {
            $schoolBusTable = TableRegistry::get("SchoolBus");
            $result = $schoolBusTable->find('all', [
                'join' => [
                    [
                        'table' => 'business_relationship',
                        'alias' => 'business_relationship',
                        'type' => 'INNER',
                        'conditions' => array('business_relationship.business_object_id = SchoolBus.business_object_id')
                    ], [
                        'table' => 'business',
                        'alias' => 'business',
                        'type' => 'INNER',
                        'conditions' => array('business.business_id = business_relationship.business_id')
                    ],
                ],
                'fields' => ['SchoolBus.business_object_id', 'SchoolBus.schoolbus_serial_code', 'SchoolBus.seats', 'SchoolBus.schoolbus_name'],
                'conditions' => array('business.business_id' => $businessId,
                    array('OR' => array(
                        array('SchoolBus.schoolbus_serial_code LIKE' => '%' . $filter . '%'),
                        array('SchoolBus.schoolbus_name LIKE' => '%' . $filter . '%'),
                        array('SchoolBus.seats LIKE' => '%' . $filter . '%')
                    ))),
                'order' => ['SchoolBus.schoolbus_name']
            ]);
            Log::info($result->count());
            if ($result->count() > 0) {
                $result = $result->toArray();
                Log::info(json_encode($result));
            }
        } catch (\Exception $e) {
            $result = array();
            Log::info($e->getMessage());
        }
        return $result;
    }


    /**
     * get a bus by its id
     * @param $schoolBusId
     * @return array|\Cake\ORM\Query
     */
    private function getAllSchoolById($schoolBusId)
    {
        try {
            $schoolBusTable = TableRegistry::get("SchoolBus");
            $result = $schoolBusTable->findByBusinessObjectId($schoolBusId)->contain(array('Status', 'Driver', 'Business', 'ReaxiumDevice'));
            Log::info($result->count());
            if ($result->count() > 0) {
                $result = $result->toArray();
                Log::info(json_encode($result));
            }
        } catch (\Exception $e) {
            $result = array();
            Log::info($e->getMessage());
        }
        return $result;
    }


    /**
     *
     * Create a schoolbus in the system and attach it to a business
     * @param $schoolBusJson
     * @return bool|\Cake\Datasource\EntityInterface|mixed
     */
    private function createABus($schoolBusJson)
    {
        $schoolBusTable = TableRegistry::get("SchoolBus");
        $shcoolBus = $schoolBusTable->newEntity();
        $schoolBusTable->patchEntity($shcoolBus, $schoolBusJson);
        $result = $schoolBusTable->save($shcoolBus);
        return $result;
    }

    /**
     * attach a bus to a business
     * @param $businessId
     * @param $bussinessObjectId
     * @return bool|\Cake\Datasource\EntityInterface|mixed
     */
    private function addABusToABusiness($businessId, $bussinessObjectId)
    {
        $businessRelationshipTable = TableRegistry::get("BusinessRelationship");
        $businessRelationship = $businessRelationshipTable->newEntity();
        $businessRelationship->business_id = $businessId;
        $businessRelationship->business_object_id = $bussinessObjectId;
        $result = $businessRelationshipTable->save($businessRelationship);
        return $result;
    }

    /**
     *  release a bus from a business
     * @param $businessId
     * @param $businessObjectId
     */
    private function deleteSchoolBus($businessId, $businessObjectId)
    {
        $businessRelationshipTable = TableRegistry::get("BusinessRelationship");
        $businessRelationshipTable->deleteAll(array('business_id' => $businessId, 'business_object_id' => $businessObjectId));
    }


}