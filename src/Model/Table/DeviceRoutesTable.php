<?php

namespace App\Model\Table;
use Cake\ORM\Table;

/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 17/4/2016
 * Time: 06:31
 */

class DeviceRoutesTable extends Table{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('device_routes');
        $this->primaryKey('id_device_routes');
        $this->belongsTo('Routes',array('foreignKey'=>'id_route'));
        /*$this->belongsToMany('Routes',
            array('targetForeignKey' =>''));*/
    }
}