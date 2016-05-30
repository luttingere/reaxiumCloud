<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 29/05/2016
 * Time: 08:20 PM
 */

namespace App\Controller;


use Cake\ORM\TableRegistry;
use App\Util\ReaxiumUtil;
use App\Util\ReaxiumApiMessages;
use Cake\Log\Log;

class LogoutController extends ReaxiumAPIController
{

    public function logOutParentsApp()
    {
        Log::info('logOutParentsApp service involed ');
        parent::setResultAsAJson();
        $jsonObjectReceived = parent::getJsonReceived();
        $response = parent::getDefaultReaxiumMessage();
        Log::info('Object Received: ');
        Log::info(json_encode($jsonObjectReceived));
        try {
            if (parent::validReaxiumJsonHeader($jsonObjectReceived)) {
                if (isset($jsonObjectReceived['ReaxiumParameters']['LogOut'])) {
                    $arrayParametersToTest = array('user_id', 'device_platform');
                    $validationResult = ReaxiumUtil::validateParameters($arrayParametersToTest, $jsonObjectReceived['ReaxiumParameters']['LogOut']);
                    if ($validationResult['code'] == '0') {

                        //parametros
                        $userId = $jsonObjectReceived['ReaxiumParameters']['LogOut']['user_id'];
                        $devicePlatform = $jsonObjectReceived['ReaxiumParameters']['LogOut']['device_platform'];

                        $stakeholderTable = TableRegistry::get("Stakeholders");

                        if ($devicePlatform == 'ANDROID') {
                            $stakeholderTable->updateAll(array('android_id' => ''), array('user_id' => $userId));
                        } else if ($devicePlatform == 'IOS') {
                            $stakeholderTable->updateAll(array('ios_id' => ''), array('user_id' => $userId));
                        }

                        $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                        $response['ReaxiumResponse']['message'] = 'Logout successfully';
                        $response['ReaxiumResponse']['object'] = array();

                    } else {
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
            $response = parent::setInternalServiceError($response);
            Log::info("Error enviando la alarma, Error message: " . $e->getMessage());
        }
        Log::info('Respuesta del servicio de notificacion al Usuario:');
        Log::info(json_encode($response));
        $this->response->body(json_encode($response));
    }

}