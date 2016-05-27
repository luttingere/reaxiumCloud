<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 26/05/2016
 * Time: 12:48 PM
 */

namespace App\Controller;


use App\Util\ReaxiumUtil;
use Cake\Log\Log;
use App\Controller\TrafficController;
use App\Util\ReaxiumApiMessages;

class DeviceUpdateLocationController extends ReaxiumAPIController
{


    public function requestDeviceUpdateLocation()
    {
        Log::info("requestDeviceUpdateLocation service invoked ");
        parent::setResultAsAJson();
        $objectReceived = parent::getJsonReceived();
        $response = parent::getDefaultReaxiumMessage();
        Log::info("Object received: ");
        Log::info(json_encode($objectReceived));
        try {
            if (parent::validReaxiumJsonHeader($objectReceived)) {
                if (isset($objectReceived['ReaxiumParameters']['DeviceUpdateLocation'])) {
                    $arrayToTest = array('user_in_track_id','user_stakeholder_id','device_token','device_platform');
                    $validationResult = ReaxiumUtil::validateParameters($arrayToTest,$objectReceived['ReaxiumParameters']['DeviceUpdateLocation']);
                    if($validationResult['code'] == 0){
                        $trafficController = new TrafficController();
                        $lastTrafficResult = $trafficController->getLastTrafficOfAUser();
                        if($lastTrafficResult['userInABus']){





                        }else{
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'The student is not in a Bus';
                        }
                    }else{
                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$INVALID_PARAMETERS_CODE;
                        $response['ReaxiumResponse']['message'] = $validationResult['message'];
                    }
                } else {
                    $response = parent::seInvalidParametersMessage($response);
                }
            } else {
                $response = parent::setInvalidJsonHeader($response);
            }
        } catch (\Exception $e) {
            Log::info("Error solicitando ubnicacion de autobus: Error message " . $e->getMessage());
            $response = parent::setInternalServiceError($response);
        }
        Log::info("Response Object: " . json_encode($response));
        $this->response->body(json_encode($response));
    }

}