<?php
namespace App\Util;
use Cake\Log\Log;

/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 20/03/2016
 * Time: 03:46 PM
 */
class ReaxiumUtil
{

    public static function getDate($dateAsString){
        $date = new \DateTime($dateAsString);
        return $date->format("Y-m-d H:i:s");
    }


}