<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 09/05/2016
 * Time: 08:21 PM
 */

namespace App\Controller;


use App\Util\ReaxiumApiMessages;
use App\Util\ReaxiumUtil;
use Cake\Core\Exception\Exception;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;


class RFIDController extends ReaxiumAPIController
{


    public function validateRFIDCard()
    {
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $object = parent::getJsonReceived();
        Log::info("Object Received:");
        Log::info(json_encode($object));
        try {
            if (parent::validReaxiumJsonHeader($object)) {
                if (isset($object['ReaxiumParameters']['RFIDValidation'])) {

                    $arrayToTest = array('rfid_code');
                    $validationResult = ReaxiumUtil::validateParameters($arrayToTest, $object['ReaxiumParameters']['RFIDValidation']);
                    if ($validationResult['code'] == '0') {
                        $rfidCode = $object['ReaxiumParameters']['RFIDValidation']['rfid_code'];
                        $userAccessDataTable = TableRegistry::get("UserAccessData");
                        $accessData = $userAccessDataTable->find('all', array(
                            'fields' => array(
                                'user_id'),
                            'conditions' => array('rfid_code' => $rfidCode)));
                        if ($accessData->count() > 0) {
                            $accessData = $accessData->toArray();
                            $userTable = TableRegistry::get("Users");
                            $user = $userTable->findByUserId($accessData[0]['user_id'])->contain(array('Business'));
                            if ($user->count() > 0) {

                                $user = $user->toArray();
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                                $response['ReaxiumResponse']['message'] = 'Card already registered in system';
                                $response['ReaxiumResponse']['object'] = array($user[0]);

                            } else {
                                $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                                $response['ReaxiumResponse']['message'] = 'The user id of this card are not registered in our system, contact Reaxium Support';
                            }
                        } else {
                            $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$NOT_FOUND_CODE;
                            $response['ReaxiumResponse']['message'] = 'This card are not configured to any user';
                        }
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
            Log::info("Error validando el RFID: " . $e->getMessage());
        }
        Log::info("Response:");
        Log::info(json_encode($response));
        $this->response->body(json_encode($response));
    }


    public function saveRFIDInformation()
    {
        parent::setResultAsAJson();
        $response = parent::getDefaultReaxiumMessage();
        $object = parent::getJsonReceived();
        $rfidCardNumber = !isset($object['rfidCardNumber']) ? null : $object['rfidCardNumber'];
        $deviceId = !isset($object['device_id']) ? null : $object['device_id'];
        $userId = !isset($object['user_id']) ? null : $object['user_id'];
        Log::info($rfidCardNumber);
        Log::info($userId);
        Log::info('DeviceId: ' . $deviceId);
        if (isset($rfidCardNumber) && isset($userId)) {
            try {
                $userDataAccessTable = TableRegistry::get("UserAccessData");
                $userAccessControlTable = TableRegistry::get("UserAccessControl");
                $rfidInfo = $userDataAccessTable->findByUserIdAndAccessTypeId($userId, 3);
                $successFullSave = false;
                if ($rfidInfo->count() > 0) {
                    try {
                        $rfidInfo = $rfidInfo->toArray();
                        $userDataAccessTable->updateAll(array('rfid_code' => $rfidCardNumber), array('user_access_data_id' => $rfidInfo[0]['user_access_data_id']));

                        if (isset($deviceId)) {
                            $userAccessControl = $userAccessControlTable->findByUserAccessDataIdAndDeviceId($rfidInfo[0]['user_access_data_id'], $deviceId);
                            if ($userAccessControl->count() < 1) {
                                $userAccessControl = $userAccessControlTable->newEntity();
                                $userAccessControl->device_id = $deviceId;
                                $userAccessControl->user_access_data_id = $rfidInfo[0]['user_access_data_id'];
                                $userAccessControlTable->save($userAccessControl);
                            }
                        }
                        $successFullSave = true;
                        Log::info("RFID actualizado para el usuario: " . $userId);
                        Log::info(json_encode($rfidInfo));
                    } catch (Exception $xe) {
                        Log::info('Error updating the rfid information for the user: ' . $userId);
                        Log::info($xe->getMessage());
                    }
                } else {
                    try {
                        $userAccessData = $userDataAccessTable->newEntity();
                        $userAccessData->user_id = $userId;
                        $userAccessData->access_type_id = 3;
                        $userAccessData->rfid_code = $rfidCardNumber;
                        $userAccessData = $userDataAccessTable->save($userAccessData);
                        if (isset($deviceId)) {
                            $userAccessControl = $userAccessControlTable->newEntity();
                            $userAccessControl->device_id = $deviceId;
                            $userAccessControl->user_access_data_id = $userAccessData['user_access_data_id'];
                            $userAccessControlTable->save($userAccessControl);
                        }
                        $successFullSave = true;
                        Log::info("Biometrico creado para el usuario: " + $userId);
                        Log::info(json_encode($userAccessData));
                    } catch (\Exception $ex) {
                        Log::info('Error saving the rfid information for the user: ' . $userId);
                        Log::info($ex->getMessage());
                    }
                }
                if ($successFullSave) {
                    Log::info("rfid configurado con exito para el usuario: " + $userId);
                    $response['ReaxiumResponse']['code'] = ReaxiumApiMessages::$SUCCESS_CODE;
                    $response['ReaxiumResponse']['message'] = 'Card successfully associated';
                } else {
                    $response['ReaxiumResponse']['code'] = 01;
                    $response['ReaxiumResponse']['message'] = 'Error saving the card information, the card is already set for another user.';
                }
            } catch (\Exception $e) {
                Log::info('Error loading the rfid information for the user: ' . $userId);
                Log::info($e->getMessage());
                $response = parent::setInternalServiceError($response);
            }
        } else {
            $response = parent::seInvalidParametersMessage($response);
        }
        Log::info("Response:");
        Log::info(json_encode($response));
        $this->response->body(json_encode($response));
    }

}