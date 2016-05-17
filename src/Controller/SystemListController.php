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
use PhpParser\Node\Expr\Cast\Array_;

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


    public function getMenu(){

        Log::info("Get Menu for rol user");

        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();

        if(parent::validReaxiumJsonHeader($jsonObject)){

            try{
                $type_user_id = !isset($jsonObject['ReaxiumParameters']['ReaxiumSystem']['type_user_id']) ? null : $jsonObject['ReaxiumParameters']['ReaxiumSystem']['type_user_id'];

                if(isset($type_user_id)){

                    $menuOptionTable = TableRegistry::get('MenuApplication');
                    $menuOptionFound =  $menuOptionTable->find()->contain(array('SubMenuApplication'));

                    if($menuOptionFound->count()>0){
                        $menuOptionFound = $menuOptionFound->toArray();

                        $arrayMenuFinal = $this->getActiveMenu($type_user_id,$menuOptionFound);

                        Log::info($arrayMenuFinal);

                        if(!empty($arrayMenuFinal)){
                            $response = parent::setSuccessfulResponse($response);
                            $response['ReaxiumResponse']['object'] = $arrayMenuFinal;
                        }else{
                            $response['ReaxiumResponse']['code'] = '1';
                            $response['ReaxiumResponse']['message'] = 'Menu not active for this user';
                            $response['ReaxiumResponse']['object'] = [];
                        }

                    }else{
                        $response['ReaxiumResponse']['code'] = '2';
                        $response['ReaxiumResponse']['message'] = 'No data  found';
                        $response['ReaxiumResponse']['object'] = [];
                    }

                }else{
                    $response = parent::seInvalidParametersMessage($response);
                }
            }
            catch (\Exception $e){

                Log::info("Error get options menu " . $e->getMessage());
                $response = $this->setInternalServiceError($response);
            }

        }else{
            $response = parent::seInvalidParametersMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    private function getActiveMenu($id_user_type,$arrayMenu){

        $arrayResponse = [];

        try{

            $accessOptions = TableRegistry::get('AccessOptionsRol');

            $accessOptionsFound = $accessOptions->findByUserTypeId($id_user_type);

            if($accessOptionsFound->count()>0){

                $accessOptionsFound =  $accessOptionsFound->toArray();

                foreach($arrayMenu as $menu){

                    foreach($accessOptionsFound as $access){

                        if($menu['menu_id'] == $access['menu_id']){

                            if($access['active_menu'] == ReaxiumApiMessages::$ACTIVE_MENU_FOR_TYPE_USER){
                                array_push($arrayResponse,$menu);
                            }
                        }
                    }
                }
            }
        }
        catch(\Exception $e){
            Log::info("Error get menu active");
            Log::info($e->getMessage());
            $arrayResponse = [];
        }


        return $arrayResponse;

    }


    public function updateAccessMenuByUserRol(){

        Log::info("update access menu for user");

        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $jsonObject = parent::getJsonReceived();


        if(parent::validReaxiumJsonHeader($jsonObject)){

            try{

                $objAccess = !isset($jsonObject['ReaxiumParameters']['ReaxiumSystem']['object'])? null : $jsonObject['ReaxiumParameters']['ReaxiumSystem']['object'];

                if(isset($objAccess)){

                    $accessOptionsTable = TableRegistry::get("AccessOptionsRol");

                    foreach($objAccess as $access){
                        $accessOptionsFound = $accessOptionsTable->updateAll(array('active_menu'=>$access['active_menu']),array('user_type_id'=>$access['type_user_id'],'menu_id'=>$access['menu_id']));
                    }

                    Log::info(json_encode($accessOptionsFound));

                    $response = parent::setSuccessfulResponse($response);

                }else{
                    $response = parent::seInvalidParametersMessage($response);
                }

            }
            catch (\Exception $e){
                Log::info("Error get options menu " . $e->getMessage());
                $response = $this->setInternalServiceError($response);
            }

        }else{
            $response = parent::seInvalidParametersMessage($response);
        }

        Log::info("Responde Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }


    public function getAccessActiveMenu(){

        Log::info("Looking for the access type list menu");
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();

        $response = parent::setSuccessfulResponse($response);
        $response['ReaxiumResponse']['object'] = $this->getDataAccessOptionsMenu();
        $this->response->body(json_encode($response));

    }

    private function getDataAccessOptionsMenu(){

        $accessOptions = TableRegistry::get('AccessOptionsRol');
        $accessOptionsFound = $accessOptions->find()->order(array('user_type_id'));
        if($accessOptionsFound->count()>0){
            $accessOptionsFound =  $accessOptionsFound->toArray();
        }

        return $accessOptionsFound;
    }

}