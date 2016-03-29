<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 20/03/2016
 * Time: 08:05 PM
 */

namespace App\Controller;

use Cake\Core\Exception\Exception;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;


class ApplicationsController extends ReaxiumAPIController
{

    /**
     * @api {post} /Applications/applicationInfo get Application Information by ID
     * @apiName applicationInfo
     * @apiGroup Applications
     *
     * @apiParamExample {json} Request-Example:
     *
     * {
     *  "ReaxiumParameters": {
     *      "Applications": {
     *      "application_id": "1"
     *      }
     *    }
     * }
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {"ReaxiumResponse": {
     *              "code": "00",
     *              "message": "SUCCESSFUL REQUEST",
     *               "object": [{
     *                   "application_id": 1,
     *                   "application_name": "Reaxium Access Control",
     *                   "status_id": 1,
     *                   "version"
     *                }]
     *              }
     *           }
     *
     * @apiErrorExample Error-Response Application Not Found:
     * {
     *  "ReaxiumResponse": {
     *      "code": 404,
     *      "message": "Application Not found",
     *      "object": []
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response Invalid Parameters:
     * {
     *      "ReaxiumResponse": {
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
     *      "code": 3,
     *      "message": "Invalid Json Object",
     *      "object": []
     *      }
     *  }
     */
    public function applicationInfo()
    {
        Log::info("Application info Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                Log::info($jsonObject);
                if (isset($jsonObject['ReaxiumParameters']['Applications'])) {
                    $this->loadModel("Applications");
                    $application = $this->Applications->newEntity();
                    $application = $this->Applications->patchEntity($application, $jsonObject['ReaxiumParameters']);
                    if (isset($application->application_id)) {
                        $application = $this->getApplicationInfo($application->application_id);
                        if (isset($application)) {
                            $response['ReaxiumResponse']['object'] = $application;
                            $response = parent::setSuccessfulResponse($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'Application Not found';
                        }
                    } else {
                        $response = parent::seInvalidParametersMessage($response);
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
     *
     * obtain all the information related to an specific application id
     *
     * @param $applicationId
     * @return \Cake\ORM\Table  --Application information
     */
    private function getApplicationInfo($applicationId)
    {
        $application = TableRegistry::get("Applications");
        $application = $application->findByApplicationId($applicationId)->contain("Status");
        if ($application->count() > 0) {
            $application = $application->toArray();
        } else {
            $application = null;
        }

        return $application;
    }


    /**
     * @api {get} /Applications/allApplicationInfo get All Application in the system
     * @apiName allApplicationInfo
     * @apiGroup Applications
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {"ReaxiumResponse": {
     *              "code": "",
     *              "message": "",
     *               "object": [
     *                  {"application_id": 1,
     *                   "application_id_name": "Test 1",
     *                   "version": 1.0,
     *                   "status": {
     *                      "status_id": 1,
     *                      "status_name": "ACTIVE",
     *                    }
     *                  },
     *                  {"application_id": 2,
     *                   "application_id_name": "Test 2",
     *                   "version": 1.0,
     *                   "status": {
     *                      "status_id": 1,
     *                   "status_name": "ACTIVE",
     *                    }
     *                  ]
     *                }
     *              }
     *
     *
     * @apiErrorExample Error-Response No Applications Found:
     * {
     *   "ReaxiumResponse": {
     *   "code": 404,
     *   "message": "No Applications Found",
     *   "object": []
     *   }
     * }
     *
     */
    public function allApplicationInfo()
    {
        Log::info("All Apps information Service invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        try {
            $applications = $this->getAllAppsInfo();
            if (isset($applications)) {
                $response['ReaxiumResponse']['object'] = $applications;
            } else {
                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                $response['ReaxiumResponse']['message'] = 'No Applications Found';
            }
        } catch (\Exception $e) {
            Log::info("Error: " . $e->getMessage());
            $response = parent::setInternalServiceError($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    /**
     *
     * obtain all information related to the applications in the system
     *
     * @return \Cake\ORM\Table  --All Apps information
     */
    private function getAllAppsInfo()
    {
        $applications = TableRegistry::get("Applications");
        $applications = $applications->find()->contain(array("Status"));
        if ($applications->count() > 0) {
            $applications = $applications->toArray();
        } else {
            $applications = null;
        }
        return $applications;
    }


    /**
     * @api {post} /Applications/createApplication Create A New Application in the system
     * @apiName createApplication
     * @apiGroup Applications
     *
     * @apiParamExample {json} Request-Example:
     *   {"ReaxiumParameters": {
     *      "Applications": {
     *      "application_name": "Another Application",
     *      "version": "1.0"
     *     }
     *   }
     * }
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *      "ReaxiumResponse": {
     *          "code": 0,
     *          "message": "SAVED SUCCESSFUL",
     *          "object": {
     *          "application_name": "Another Application",
     *          "version": "1.0",
     *          "application_id": 1
     *          "status_id": 1
     *          }
     *      }
     *  }
     *
     *
     * @apiErrorExample Error-Response: Application already exist
     *  {
     *      "ReaxiumResponse": {
     *           "code": 101,
     *          "message": "Application name already exist in the system",
     *          "object": []
     *      }
     *  }
     *
     */
    public function createAnApplication()
    {
        Log::info("Create a new Application service has been invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Applications"])) {
                    $result = $this->createANewApplication($jsonObject['ReaxiumParameters']);
                    Log::info('Resultado: ' . $result);
                    if ($result) {
                        $response = parent::setSuccessfulSave($response);
                        $response['ReaxiumResponse']['object'] = $result;
                    } else {
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                        $response['ReaxiumResponse']['message'] = 'There was a problem trying to save the application, please try later';
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error Saving the Device " . $e->getMessage());
                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$CANNOT_SAVE;
                $response['ReaxiumResponse']['message'] = 'Device name already exist in the system';
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     *
     * Register a new device in the system
     *
     * @param $applicationJSON
     * @return created application
     */
    private function createANewApplication($applicationJSON)
    {
        $this->loadModel("Applications");
        $application = $this->Applications->newEntity();
        $application = $this->Applications->patchEntity($application, $applicationJSON);
        $application = $this->Applications->save($application);
        return $application;
    }


    /**
     * @api {post} /Applications/deleteApplication Delete An Application From System
     * @apiName deleteApplication
     * @apiGroup Applications
     *
     * @apiParamExample {json} Request-Example:
     *
     * {"ReaxiumParameters": {
     *      "Applications": {
     *          "application_id": "1"
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
     * @apiErrorExample Error-Response Application Not Found:
     *      {"ReaxiumResponse": {
     *          "code": 404,
     *          "message": "Application Not found",
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
    public function deleteApplication()
    {
        Log::info("deleting  an Application service is running");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $deviceId = null;
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Applications"])) {
                    $this->loadModel("Applications");
                    $application = $this->Applications->newEntity();
                    $application = $this->Applications->patchEntity($application, $jsonObject['ReaxiumParameters']);
                    if (isset($application->application_id)) {
                        $applicationId = $application->application_id;
                        $application = $this->getApplicationInfo($applicationId);
                        if (isset($application)) {
                            $this->deleteAnApplication($applicationId);
                            $response = parent::setSuccessfulDelete($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'Application Not found';
                        }
                    } else {
                        $response = parent::seInvalidParametersMessage($response);
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error deleting the application: " . $applicationId . " error:" . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

    /**
     * Delete an application from de system
     * @param $applicationId
     */
    private function deleteAnApplication($applicationId)
    {
        $this->loadModel("Applications");
        $this->loadModel("ApplicationsRelationship");
        $associatedApplication = $this->ApplicationsRelationship->findByApplicationId($applicationId);
        if ($associatedApplication->count() > 0) {
            $associatedApplication = $associatedApplication->toArray();
            Log::info("The application id: " . $associatedApplication[0]['application_id'] . "has associations and they will be deleted");
            $this->ApplicationsRelationship->deleteAll(array('application_id' => $associatedApplication[0]['application_id']));
        }
        $this->Applications->updateAll(array('status_id' => '3'), array('application_id' => $associatedApplication[0]['application_id']));
    }

    /**
     * @api {post} /Applications/changeApplicationStatus Change The Status Of An Application
     * @apiName changeApplicationStatus
     * @apiGroup Applications
     *
     * @apiParamExample {json} Request-Example:
     *
     * {"ReaxiumParameters": {
     *      "Applications": {
     *          "application_id": "1"
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
     * @apiErrorExample Error-Response Application Not Found:
     *      {"ReaxiumResponse": {
     *          "code": 404,
     *          "message": "Application Not found",
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
    public function changeApplicationStatus()
    {
        Log::info("updating the status of an Application service is running");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $deviceId = null;
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Applications"])) {
                    $this->loadModel("Applications");
                    $application = $this->Applications->newEntity();
                    $application = $this->Applications->patchEntity($application, $jsonObject['ReaxiumParameters']);
                    if (isset($application->application_id) && isset($application->status_id)) {
                        $applicationId = $application->application_id;
                        $applicationFound = $this->getApplicationInfo($applicationId);
                        if (isset($applicationFound)) {
                            $this->updateApplication(array('status_id' => $application->status_id), array('application_id' => $applicationId));
                            $response = parent::setSuccessfulUpdated($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'Application Not found';
                        }
                    } else {
                        $response = parent::seInvalidParametersMessage($response);
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error deleting the application: " . $applicationId . " error:" . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    /**
     * @api {post} /Applications/changeApplicationVersion Change The Version Of An Application
     * @apiName changeApplicationVersion
     * @apiGroup Applications
     *
     * @apiParamExample {json} Request-Example:
     *
     * {"ReaxiumParameters": {
     *      "Applications": {
     *          "application_id": "1"
     *          "version": "2.0"
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
     * @apiErrorExample Error-Response Application Not Found:
     *      {"ReaxiumResponse": {
     *          "code": 404,
     *          "message": "Application Not found",
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
    public function changeApplicationVersion()
    {
        Log::info("updating the version of an Application service is running");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();
        $deviceId = null;
        Log::info('Object received: ' . json_encode($jsonObject));
        if (parent::validReaxiumJsonHeader($jsonObject)) {
            try {
                if (isset($jsonObject['ReaxiumParameters']["Applications"])) {
                    $this->loadModel("Applications");
                    $application = $this->Applications->newEntity();
                    $application = $this->Applications->patchEntity($application, $jsonObject['ReaxiumParameters']);
                    if (isset($application->application_id) && isset($application->version)) {
                        $applicationId = $application->application_id;
                        $applicationFound = $this->getApplicationInfo($applicationId);
                        if (isset($applicationFound)) {
                            $this->updateApplication(array('version' => $application->version), array('application_id' => $applicationId));
                            $response = parent::setSuccessfulUpdated($response);
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'Application Not found';
                        }
                    } else {
                        $response = parent::seInvalidParametersMessage($response);
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } catch (\Exception $e) {
                Log::info("Error deleting the application: " . $applicationId . " error:" . $e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::setInvalidJsonMessage($response);
        }
        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }




    /**
     * Update the attributes of an Application
     * @param $arrayFields
     * @param $arrayConditions
     */
    private function updateApplication($arrayFields, $arrayConditions)
    {
        $this->loadModel("Applications");
        $this->Applications->updateAll($arrayFields, $arrayConditions);
    }


}