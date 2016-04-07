<?php
/**
 * Created by PhpStorm.
 * User: SinAsignari54GB1TB
 * Date: 20/03/2016
 * Time: 02:48 PM
 */

namespace App\Controller;

use Cake\Event\Event;
use Cake\Log\Log;
use App\Util\ReaxiumApiMessages;

class ReaxiumAPIController extends AppController
{

    private  $reaxiumResponseObject = array("ReaxiumResponse" => array("code"=>"","message"=>"","object"=>array()));

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->response->header(array('Access-Control-Allow-Origin' => '*'));
    }

    public function handleError($code, $description, $file = null, $line = null, $context = null) {
        Log::info("Handling the error fuck");
        if (error_reporting() == 0 || $code === 2048 || $code === 8192) {
            return;
        }
        // throw error for further handling
        throw new exception(strip_tags($description));
    }

    function exception_error_handler($errno, $errstr, $errfile, $errline ) {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    }

    public function setResultAsAJson()
    {
        $this->autoRender = false;
        $this->response->type('json');
    }

    public function getJsonReceived()
    {
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, TRUE);
        return $input;
    }

    public function validReaxiumJsonHeader($jsonObject){
        $isValid = false;
        if(isset($jsonObject['ReaxiumParameters'])){
            $isValid = true;
        }
        return $isValid;
    }

    public function getDefaultReaxiumMessage(){
        $reaxiumMessage = $this->reaxiumResponseObject;
        return $reaxiumMessage;
    }

    public function seInvalidParametersMessage($reaxiumMessage){
        $reaxiumMessage['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_PARAMETERS_CODE;
        $reaxiumMessage['ReaxiumResponse']['message'] = ReaxiumApiMessages::$INVALID_PARAMETERS_MESSAGE;
        return $reaxiumMessage;
    }

    public function setInternalServiceError($reaxiumMessage){
        $reaxiumMessage['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INTERNAL_SERVER_ERROR_CODE;
        $reaxiumMessage['ReaxiumResponse']['message'] = ReaxiumApiMessages::$INTERNAL_SERVER_ERROR_MESSAGE;
        return $reaxiumMessage;
    }

    public function setInvalidJsonMessage($reaxiumMessage){
        $reaxiumMessage['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_JSON_OBJECT_CODE;
        $reaxiumMessage['ReaxiumResponse']['message'] = ReaxiumApiMessages::$INVALID_JSON_OBJECT_MESSAGE;
        return $reaxiumMessage;
    }

    public function setInvalidJsonHeader($reaxiumMessage){
        $reaxiumMessage['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_JSON_HEADER_CODE;
        $reaxiumMessage['ReaxiumResponse']['message'] = ReaxiumApiMessages::$INVALID_JSON_HEADER_MESSAGE;
        return $reaxiumMessage;
    }

    public function setSuccessfulResponse($reaxiumMessage){
        $reaxiumMessage['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
        $reaxiumMessage['ReaxiumResponse']['message'] = ReaxiumApiMessages::$SUCCESS_MESSAGE;
        return $reaxiumMessage;
    }

    public function setSuccessAccess($reaxiumMessage){
        $reaxiumMessage['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
        $reaxiumMessage['ReaxiumResponse']['message'] = ReaxiumApiMessages::$SUCCESS_ACCESS;
        return $reaxiumMessage;
    }

    public function setSuccessfulDelete($reaxiumMessage){
        $reaxiumMessage['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
        $reaxiumMessage['ReaxiumResponse']['message'] = ReaxiumApiMessages::$SUCCESS_DELETED_MESSAGE;
        return $reaxiumMessage;
    }

    public function setSuccessfulUpdated($reaxiumMessage){
        $reaxiumMessage['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
        $reaxiumMessage['ReaxiumResponse']['message'] = ReaxiumApiMessages::$SUCCESS_UPDATED_MESSAGE;
        return $reaxiumMessage;
    }

    public function setSuccessfulSave($reaxiumMessage){
        $reaxiumMessage['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
        $reaxiumMessage['ReaxiumResponse']['message'] = ReaxiumApiMessages::$SUCCESS_SAVE_MESSAGE;
        return $reaxiumMessage;
    }

}