<?php

namespace App\Util;
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 20/03/2016
 * Time: 03:48 PM
 */
class ReaxiumApiMessages
{

    public static $SUCCESS_CODE = 00;
    public static $SUCCESS_MESSAGE = 'SUCCESSFUL REQUEST';
    public static $SUCCESS_SAVE_MESSAGE = 'SAVED SUCCESSFUL';
    public static $SUCCESS_DELETED_MESSAGE = 'DELETED SUCCESSFUL';
    public static $SUCCESS_UPDATED_MESSAGE = 'UPDATED SUCCESSFUL';
    public static $SUCCESS_ACCESS = 'ACCESS GRANTED';


    public static $INVALID_JSON_OBJECT_CODE = 01;
    public static $INVALID_JSON_OBJECT_MESSAGE = 'Invalid Json Object';

    public static $INVALID_PARAMETERS_CODE = 02;
    public static $INVALID_PARAMETERS_MESSAGE = 'Invalid Parameters received, please checkout the api documentation';

    public static $INTERNAL_SERVER_ERROR_CODE = 03;
    public static $INTERNAL_SERVER_ERROR_MESSAGE = 'Internal Server Error, Please contact with the api administrator';

    public static $INVALID_JSON_HEADER_CODE = 04;
    public static $INVALID_JSON_HEADER_MESSAGE = 'Invalid Json Header';

    public static $INVALID_USER_ACCESS_CODE = 05;
    public static $INVALID_USER_ACCESS_MESSAGE = 'Invalid User';

    public static $INVALID_USER_STATUS_CODE = 06;
    public static $INVALID_USER_STATUS_MESSAGE = 'Invalid Status User';

    public static $DEVICE_ALREADY_CONFIGURED_CODE = 07;
    public static $DEVICE_ALREADY_CONFIGURED_MESSAGE = 'Device id already configured in a device';

    public static $DEVICE_NOT_CONFIGURED_CODE = 8;
    public static $DEVICE_NOT_CONFIGURED_MESSAGE = 'Device id not configured in a device';

    public static $INVALID_STATUS_CODE = 9;
    public static $INVALID_STATUS_MESSAGE = 'Device with invalid status in system';

    public static $USER_ALREADY_REGISTERED_CODE = 10;
    public static $USER_ALREADY_REGISTERED_MESSAGE = 'User already registered with the same document id';

    public static $ERROR_CREATING_A_BUSINESS_CODE = 12;
    public static $ERROR_CREATING_A_BUSINESS_MESSAGE = 'Error creating the user, see error log';


    public static $NOT_FOUND_CODE = 404;
    public static $CANNOT_SAVE = 101;

    public static $CODE_VALIDATE_STATUS = 1;

}