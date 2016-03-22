<?php
namespace App\Model\Table;
use Cake\ORM\Table;
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 22/03/2016
 * Time: 03:27 AM
 */
class AccessTypeTable extends Table {

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('access_type');
        $this->primaryKey('access_type_id');
        $this->belongsTo('Status', array('foreignKey' => 'status_id'));
    }

}