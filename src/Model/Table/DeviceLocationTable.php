<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 26/05/2016
 * Time: 07:02 PM
 */

namespace App\Model\Table;


use Cake\ORM\Table;

class DeviceLocationTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('device_location');
        $this->primaryKey('device_location_id');
        $this->belongsTo('Driver',array('className' => 'Users', 'foreignKey' => 'user_id'));
        $this->belongsTo("ReaxiumDevice", array('foreignKey' => 'device_id'));
        $this->belongsTo("Routes", array('foreignKey' => 'id_route'));
    }

}