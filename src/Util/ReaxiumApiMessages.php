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

    public static $INVALID_JSON_OBJECT_CODE = 01;
    public static $INVALID_JSON_OBJECT_MESSAGE = 'Invalid Json Object';

    public static $INVALID_PARAMETERS_CODE = 02;
    public static $INVALID_PARAMETERS_MESSAGE = 'Invalid Parameters received, please checkout the api documentation';

    public static $INTERNAL_SERVER_ERROR_CODE = 03;
    public static $INTERNAL_SERVER_ERROR_MESSAGE = 'Internal Server Error, Please contact with the api administrator';

    public static $NOT_FOUND_CODE = 404;
    public static $CANNOT_SAVE = 101;

}