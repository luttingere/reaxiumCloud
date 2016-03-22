<?php
namespace App\Model\Table;
use Cake\ORM\Table;
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 22/03/2016
 * Time: 03:25 AM
 */
class DeviceAccessControlTable extends Table {

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('device_access_control');
        $this->primaryKey('access_device_control_id');
        $this->belongsTo('Status', array('foreignKey' => 'status_id'));
        $this->belongsTo('AccessType', array('foreignKey' => 'access_type_id'));
        $this->belongsTo('Applications', array('foreignKey' => 'application_id'));
        $this->belongsTo('ReaxiumDevice', array('foreignKey' => 'device_id'));
    }

}