<?php

namespace App\Model\Entity;
use Cake\ORM\Entity;
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 13/4/2016
 * Time: 04:35
 */

class UserAccessData extends Entity{

    protected $_accessible = [
        '*' => true
    ];
}