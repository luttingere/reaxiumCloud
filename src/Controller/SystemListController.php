<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 06/04/2016
 * Time: 07:02 PM
 */

namespace App\Controller;

use Cake\Event\Event;
use Cake\Log\Log;
use App\Util\ReaxiumApiMessages;
use Cake\ORM\TableRegistry;

class SystemListController extends ReaxiumAPIController
{

    /**
     * @api {get} /SystemList/statusList get a list of all status in the system
     * @apiName statusList
     * @apiGroup SystemList
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *
            {
                "ReaxiumResponse": {
                    "code": "",
                    "message": "",
                    "object": [
                        {
                        "status_id": 1,
                        "status_name": "ACTIVE"
                        },
                        {
                        "status_id": 3,
                        "status_name": "DELETED"
                        },
                        {
                        "status_id": 2,
                        "status_name": "INACTIVE"
                        }
                        ]
                    }
            }
     *
     *
     */
    public function statusList()
    {
        Log::info("Create a new Device access service has been invoked");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
            $response['ReaxiumResponse']['object'] = $this->getStatusList();
        $this->response->body(json_encode($response));
    }

    /**
     * @return $this|array status's list
     */
    private function getStatusList(){
        $statusTable = TableRegistry::get("Status");
        $statusList = $statusTable->find()->order(array('status_name'));
        if ($statusList->count() > 0) {
            $statusList = $statusList->toArray();
        }
        return $statusList;
    }


    /**
     * @api {get} /SystemList/accessTypeList get a list of all access types in the system
     * @apiName accessTypeList
     * @apiGroup SystemList
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *
        {
            "ReaxiumResponse": {
                "code": "",
                "message": "",
                "object": [
                    {
                    "access_type_id": 2,
                    "access_type_name": "Biometric",
                    "status_id": 2
                    },
                    {
                    "access_type_id": 3,
                    "access_type_name": "RFID",
                    "status_id": 3
                    },
                    {
                    "access_type_id": 1,
                    "access_type_name": "User Login and Password",
                    "status_id": 1
                    }
                    ]
            }
        }
     *
     *
     */
    public function accessTypeList()
    {
        Log::info("Looking for the access type list ");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $response['ReaxiumResponse']['object'] = $this->getAccessTypeList();
        $this->response->body(json_encode($response));
    }

    /**
     * @return $this|array status's list
     */
    private function getAccessTypeList(){
        $accessTypeTable = TableRegistry::get("AccessType");
        $accessTypeList = $accessTypeTable->find()->order(array('access_type_name'));
        if ($accessTypeList->count() > 0) {
            $accessTypeList = $accessTypeList->toArray();
        }
        return $accessTypeList;
    }





}