<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 02/05/2016
 * Time: 09:28 AM
 */

namespace App\Controller;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;

class BusinessController extends ReaxiumAPIController
{

    /**
     * @api {post} /Business/createBusiness create a business in our reaxium system
     * @apiName createBusiness
     * @apiGroup Business
     *
     * @apiParamExample {json} Save-Request:
     *
     *      {"ReaxiumParameters": {
     *          "Business": {
     *              "business_id": null,
     *              "business_name": "Business Name",
     *              "business_id_number": "J-00010001001"
     *               },
     *           "BusinessAddress":{
     *                 "address_id":null,
     *                 "address":"Miranda, San antonio de los altos, urbanizacion OPS torre 4, 1204",
     *                 "latitude":"10.37706",
     *                 "longitude":"-66.95635"
     *             },
     *            "BusinessPhoneNumbers":{
     *                  "phone_number_id":null,
     *                  "phone_name":"Office",
     *                  "phone_number":"0212-3734832"
     *            }
     *          }
     *       }
     *
     * @apiParamExample {json} Edit-Request:
     *
     *      {"ReaxiumParameters": {
     *          "Business": {
     *              "business_id": 1,
     *              "business_name": "Business Name",
     *              "business_id_number": "J-00010001001"
     *               },
     *           "BusinessAddress":{
     *                 "address_id":15,
     *                 "address":"Miranda, San antonio de los altos, urbanizacion OPS torre 4, 1204",
     *                 "latitude":"10.37706",
     *                 "longitude":"-66.95635"
     *             },
     *            "BusinessPhoneNumbers":{
     *                  "phone_number_id":18,
     *                  "phone_name":"Office",
     *                  "phone_number":"0212-3734832"
     *            }
     *          }
     *       }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *
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
    public function createBusiness()
    {
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        try {
            Log::info('Object received: ' . json_encode($jsonObject));
            if (parent::validReaxiumJsonHeader($jsonObject)) {
                if (isset($jsonObject['ReaxiumParameters']["Business"])) {
                    $businessCreated = $this->createOrEditABusiness($jsonObject['ReaxiumParameters']);
                    if (isset($businessCreated)) {
                        $response = parent::setSuccessfulSave($response);
                        $response['ReaxiumResponse']['object'] = $businessCreated;
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$ERROR_CREATING_A_BUSINESS_CODE;
                        $response['ReaxiumResponse']['message'] = ReaxiumApiMessages::$ERROR_CREATING_A_BUSINESS_MESSAGE;
                    }
                } else {
                    Log::info("Parameters received not contain business information in the right way");
                    $response = parent::seInvalidParametersMessage($response);
                }
            } else {
                Log::info("Object receive not valid with reaxium specifications");
                $response = parent::setInvalidJsonHeader($response);
            }
        } catch (\Exception $e) {
            Log::info("Error creating the business");
            Log::info($e->getMessage());
            $response = parent::setInternalServiceError($response);
        }
        $this->response->body(json_encode($response));
    }


    /**
     * create a business in our server
     * @param $businessJson
     * @return bool|\Cake\Datasource\EntityInterface|\Cake\ORM\Entity|mixed
     */
    private function createOrEditABusiness($businessJson)
    {
        try {

            $businessTable = TableRegistry::get("Business");
            $businessObject = $businessTable->newEntity();
            $businessObject = $businessTable->patchEntity($businessObject, $businessJson['Business']);

            if (isset($businessJson['BusinessAddress'])) {

                $addressTable = TableRegistry::get("Address");
                $addressObject = $addressTable->newEntity();
                $addressObject = $addressTable->patchEntity($addressObject, $businessJson['BusinessAddress']);
                $addressObject = $addressTable->save($addressObject);
                $businessObject->address_id = $addressObject->address_id;

            } else {
                Log::info("Business with no address information");
            }

            if (isset($businessJson['BusinessPhoneNumbers'])) {
                $phoneNumbersTable = TableRegistry::get("PhoneNumbers");
                $phoneNumbersObject = $phoneNumbersTable->newEntity();
                $phoneNumbersObject = $phoneNumbersTable->patchEntity($phoneNumbersObject, $businessJson['BusinessPhoneNumbers']);
                $phoneNumbersObject = $phoneNumbersTable->save($phoneNumbersObject);
                $businessObject->phone_number_id = $phoneNumbersObject->phone_number_id;
            } else {
                Log::info("Business with no phone numbers information");
            }

            $businessObject = $businessTable->save($businessObject);

            if ($businessObject) {
                Log::info("User successfully saved or edited");
                Log::info(json_encode($businessObject));
            } else {
                Log::info("problems saving the business data");
            }

        } catch (\Exception $e) {
            Log::info("Error creating the user. " . $e->getMessage());
            $businessObject = null;
        }
        return $businessObject;
    }


    /**
     * @api {post} /Business/allBusiness get all the business registered in reaxium cloud
     * @apiName allBusiness
     * @apiGroup Business
     *
     * @apiParamExample {json} Request:
     *
     *      {"ReaxiumParameters": {
     *              "page": "1",
     *              "limit": "10",
     *              "sortDir": "desc",
     *              "sortedBy": "business_name"
     *          }
     *       }
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *
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
    public function allBusiness()
    {
        Log::info("All business registered service invoked");
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

                    $businessFound = $this->lookUpAllBusiness($filter, $sortedBy, $sortDir);
                    $count = $businessFound->count();
                    $this->paginate = array('limit' => $limit, 'page' => $page);
                    $businessFound = $this->paginate($businessFound);

                    if ($businessFound->count() > 0) {
                        $maxPages = floor((($count - 1) / $limit) + 1);
                        $businessFound = $businessFound->toArray();
                        $response['ReaxiumResponse']['totalRecords'] = $count;
                        $response['ReaxiumResponse']['totalPages'] = $maxPages;
                        $response['ReaxiumResponse']['object'] = $businessFound;
                        $response = parent::setSuccessfulResponse($response);
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'No Business found';
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error getting all business information " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    /**
     * obtain all business registered in the reaxium cloud
     * @param $filter
     * @param $sortedBy
     * @param $sortDir
     * @return $this|null
     */
    private function lookUpAllBusiness($filter, $sortedBy, $sortDir)
    {
        $businessTable = TableRegistry::get("Business");
        $AllBusinessObject = null;
        if (isset($filter) && trim($filter) != '') {
            $whereCondition = array(array('OR' => array(
                array('business_name LIKE' => '%' . $filter . '%'),
                array('business_id_number LIKE' => '%' . $filter . '%')
            )), 'status_id' => '1');
            $AllBusinessObject = $businessTable->find()->where($whereCondition)->order(array($sortedBy . ' ' . $sortDir));
        } else {
            $whereCondition = array('status_id' => '1');
            $AllBusinessObject = $businessTable->find()->where($whereCondition)->order(array($sortedBy . ' ' . $sortDir));
        }
        return $AllBusinessObject;
    }


    /**
     * @api {post} /Business/businessById get a business information by its id
     * @apiName businessById
     * @apiGroup Business
     *
     * @apiParamExample {json} Request:
     *
     *      {"ReaxiumParameters": {
     *              "Business":{
     *              "business_id": "1"
     *             }
     *          }
     *       }
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *
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
    public function businessById()
    {
        Log::info("business By ID service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            Log::info('Object received: ' . json_encode($jsonObject));
            try {
                if (isset($jsonObject['ReaxiumParameters']["Business"]) &&
                    isset($jsonObject['ReaxiumParameters']["Business"]['business_id'])) {

                    $businessId = $jsonObject['ReaxiumParameters']["Business"]['business_id'];
                    $businessFound = $this->lookupABusinessByID($businessId);

                    if (isset($businessFound)) {
                        $response = parent::setSuccessfulResponse($response);
                        $response['ReaxiumResponse']['object'] = $businessFound;
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'No Business found';
                    }

                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error getting all business information " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }



    /**
     * @api {post} /Business/allBusinessFiltered filter and get all the business registered in reaxium cloud
     * @apiName allBusinessFiltered
     * @apiGroup Business
     *
     * @apiParamExample {json} Request:
     *
     *      {"ReaxiumParameters": {
     *              "Business": {
                       "filter":"j-5668"
     *            }
     *          }
     *       }
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *
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
    public function allBusinessFiltered()
    {
        Log::info("All business filtered service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            Log::info('Object received: ' . json_encode($jsonObject));
            try {
                if (isset($jsonObject['ReaxiumParameters']["Business"]["filter"])) {
                    $sortedBy = "business_name";
                    $sortDir = "desc";
                    $filter = $jsonObject['ReaxiumParameters']["Business"]["filter"];
                    $businessFound = $this->lookUpAllBusiness($filter, $sortedBy, $sortDir);
                    if ($businessFound->count() > 0) {
                        $businessFound = $businessFound->toArray();
                        $response = parent::setSuccessfulResponse($response);
                        $response['ReaxiumResponse']['object'] = $businessFound;
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                        $response['ReaxiumResponse']['message'] = 'No Business found';
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error getting all business information " . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }




    /**
     * search a business information by its ID
     * @param $businessId
     * @return null
     */
    private function lookupABusinessByID($businessId)
    {
        $businessTable = TableRegistry::get("Business");
        $whereCondition = array('business_id' => $businessId, 'Business.status_id' => '1');
        $businessObject = $businessTable->find()->where($whereCondition)->contain(array('Status', 'Address', 'PhoneNumbers'));
        if ($businessObject->count() > 0) {
            $businessObject = $businessObject->toArray();
        } else {
            $businessObject = null;
        }
        return $businessObject;
    }

    /**
     * soft delete of a business on the system
     * @param $businessId
     */
    private function deleteABusiness($businessId)
    {
        $businessTable = TableRegistry::get("Business");
        $businessTable->updateAll(array('status_id' => '3'), array('business_id' => $businessId));
    }


}