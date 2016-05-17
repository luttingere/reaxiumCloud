<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 13/5/2016
 * Time: 04:48
 */

namespace App\Model\Entity;

use Cake\ORM\Entity;
class AccessOptionsRol extends Entity{

    protected $_accessible = [
        '*' => true,
    ];
}