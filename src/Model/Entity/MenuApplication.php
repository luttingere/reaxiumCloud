<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 13/5/2016
 * Time: 01:55
 */

namespace App\Model\Entity;

use Cake\ORM\Entity;
class MenuApplication extends Entity{

    protected $_accessible = [
        '*' => true,
    ];
}