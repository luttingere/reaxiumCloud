<?php
/**
 * Created by PhpStorm.
 * User: SinAsignari54GB1TB
 * Date: 20/04/2016
 * Time: 05:37 PM
 */

namespace App\Controller;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use App\Util\ReaxiumApiMessages;

define("BIOMETRIC_FILE_PATH", "/reaxium_user_images/biometric_user_images/");
define("BIOMETRIC_FILE_FULL_PATH","/var/www/html/reaxium_user_images/biometric_user_images/");
//define("BIOMETRIC_FILE_FULL_PATH", "C:/xampp/htdocs/reaxium_user_images/biometric_user_images/");

class BiometricController extends ReaxiumAPIController
{

    public function biometricAccess()
    {
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $object = parent::getJsonReceived();
        $biometricHexaCode = !isset($object['biometricHexaCode']) ? null : $object['biometricHexaCode'];
        $biometricImage = !isset($object['biometricImage']) ? null : $object['biometricImage'];
        $biometricImageName = !isset($object['biometricImageName']) ? null : $object['biometricImageName'];
        $userId = !isset($object['user_id']) ? null : $object['user_id'];
        Log::info($biometricImage);
        Log::info($biometricImageName);
        Log::info($userId);
        if (isset($biometricHexaCode) && isset($biometricImage) && isset($userId) && isset($biometricImageName)) {
            try {
                $userDataAccessTable = TableRegistry::get("UserAccessData");
                $biometricInfo = $userDataAccessTable->findByUserIdAndAccessTypeId($userId, 2);
                if ($biometricInfo->count() > 0) {

                    $biometricInfo = $biometricInfo->toArray();
                    $userDataAccessTable->updateAll(array('biometric_code' => $biometricHexaCode), array('user_access_data_id' => $biometricInfo[0]['user_access_data_id']));

                    Log::info("Biometrico actualizado para el usuario: " + $userId);
                    Log::info(json_encode($biometricInfo));

                } else {

                    $userAccessData = $userDataAccessTable->newEntity();
                    $userAccessData->user_id = $userId;
                    $userAccessData->access_type_id = 2;
                    $userAccessData->biometric_code = $biometricHexaCode;
                    $userAccessData = $userDataAccessTable->save($userAccessData);

                    Log::info("Biometrico creado para el usuario: " + $userId);
                    Log::info(json_encode($userAccessData));

                }
                $userTable = TableRegistry::get("Users");
                $imageFullPath = "http://" . $_SERVER['SERVER_NAME'] . BIOMETRIC_FILE_PATH . $biometricImageName;
                $userTable->updateAll(array('fingerprint' => $imageFullPath), array('user_id' => $userId));
                file_put_contents(BIOMETRIC_FILE_FULL_PATH . $biometricImageName, base64_decode($biometricImage));

                Log::info("Biometrico configurado con exito para el usuario: " + $userId);

                $response = parent::setSuccessfulResponse($response);
            } catch (\Exception $e) {
                Log::info('Error loading the biometric information for the user: ' . $userId);
                Log::info($e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::seInvalidParametersMessage($response);
        }
        $this->response->body(json_encode($response));
    }

}