<?php
namespace App\Util;

use Cake\I18n\Time;
use Cake\Log\Log;
define('TIME_ZONE', 'America/Caracas');
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 20/03/2016
 * Time: 03:46 PM
 */
class ReaxiumUtil
{

    public static function getDate($dateAsString)
    {
        $date = new \DateTime($dateAsString);
        return $date->format("Y-m-d H:i:s");
    }

    public static function validateParameters($arrayToTest, $arrayReceived)
    {
        $result = array('code' => '0', 'message' => '');
        foreach ($arrayToTest as $value) {
            if(!isset($arrayReceived[$value])){
                $result['code'] = '1';
                $result['message'] = 'invalid parameters, missing parameter '.$value;
                break;
            }
        }
        return $result;
    }

    public static function getSystemDate(){
        $time = Time::now();
        $time->setTimezone(TIME_ZONE);
        $dateAssigned = $time->i18nFormat('YYYY-MM-dd HH:mm:ss');
        return $dateAssigned;
    }


}