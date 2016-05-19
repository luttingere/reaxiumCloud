<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 16/5/2016
 * Time: 10:22
 */

namespace App\Model\Table;
use Cake\ORM\Table;

class DeviceBusinessTable extends Table{

    public function initialize(array $config){
        parent::initialize($config);
        $this->table('device_business');
        $this->belongsTo('Business', array('foreignKey' => 'business_id'));
        $this->belongsTo('ReaxiumDevice',array('foreignKey'=>'device_id'));
    }
}