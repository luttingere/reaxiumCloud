<?php

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 28/03/2016
 * Time: 10:03 AM
 */
class TrafficTable extends Table{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('traffic');
        $this->primaryKey('traffic_id');
        $this->belongsTo('Status', array('foreignKey' => 'status_id'));
        $this->belongsTo('TrafficType', array('foreignKey' => 'traffic_type_id'));
        $this->belongsTo('ReaxiumDevice', array('foreignKey' => 'device_id'));
        $this->belongsTo('Users', array('foreignKey' => 'user_id'));
    }

}