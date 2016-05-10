<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 05/05/2016
 * Time: 06:21 PM
 */

namespace App\Controller;


use App\Util\ReaxiumUtil;
use Cake\Log\Log;

class UserAccessController extends ReaxiumAPIController
{


    public function executeAnAccessOfAUser()
    {
        parent::setResultAsAJson();
        $result = parent::getDefaultReaxiumMessage();
        $jsonObjectReceived = parent::getJsonReceived();
        try {
            if (parent::validReaxiumJsonHeader($jsonObjectReceived)) {
                if (isset($jsonObjectReceived['ReaxiumParameters']['UserAccess'])) {
                    $arrayOfParametersToValidate = array('user_id', 'device_id', 'traffic_type', 'access_type', 'traffic_info');
                    $validation = ReaxiumUtil::validateParameters($arrayOfParametersToValidate, $jsonObjectReceived['ReaxiumParameters']['UserAccess']);
                    if ($validation['code'] == '0') {
                        $userId = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['user_id'];
                        $deviceId = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['device_id'];
                        $trafficType = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['traffic_type'];
                        $accessType = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['access_type'];
                        $trafficInfo = $jsonObjectReceived['ReaxiumParameters']['UserAccess']['traffic_info'];
                        $accessController = new AccessController();
                        $accessObject = $accessController->registerAUserAccess($userId, $deviceId, $accessType, $trafficType, $trafficInfo);
                        if (isset($accessObject)) {

                            $result = parent::setSuccessfulResponse($result);
                            $result['ReaxiumResponse']['object'] = array($accessObject);

                        } else {
                            $result['ReaxiumResponse']['code'] = 15;
                            $result['ReaxiumResponse']['message'] = 'Error generating the user access in our server';
                        }
                    } else {
                        $result['ReaxiumResponse']['code'] = 14;
                        $result['ReaxiumResponse']['message'] = $validation['message'];
                    }
                } else {
                    $result = parent::seInvalidParametersMessage($result);
                }
            } else {
                $result = parent::setInvalidJsonHeader($result);
            }
        } catch (\Exception $e) {
            $result = parent::setInternalServiceError($result);
            Log::info("Error executing the access of a user" . $e->getMessage());
        }
        Log::info("Responde Object: " . json_encode($result));
        $this->response->body(json_encode($result));
    }

}