<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 31/5/2016
 * Time: 10:23
 */

namespace App\Model\Table;

use Cake\ORM\Table;
class RouteTypeTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('route_type');
        $this->primaryKey('route_type_id');

    }
}