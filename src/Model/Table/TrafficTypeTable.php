<?php
namespace App\Model\Table;

use Cake\ORM\Table;
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 28/03/2016
 * Time: 10:04 AM
 */
class TrafficTypeTable extends Table {

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('traffic_type');
        $this->primaryKey('traffic_type_id');
    }

}