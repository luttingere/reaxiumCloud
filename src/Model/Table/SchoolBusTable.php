<?php
/**
 * Created by PhpStorm.
 * User: SinAsignari54GB1TB
 * Date: 07/04/2016
 * Time: 12:18 PM
 */

namespace App\Model\Table;

use Cake\ORM\Table;

class SchoolBusTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('schoolbus');
        $this->primaryKey('business_object_id');
        $this->belongsTo("Status", array('foreignKey' => 'status_id'));
        $this->belongsTo('Driver',array('className' => 'Users', 'foreignKey' => 'user_id'));
        $this->belongsTo("ReaxiumDevice", array('foreignKey' => 'device_id'));
        $this->belongsToMany('Business',
            array('targetForeignKey' => 'business_object_id',
                'foreignKey' => 'business_id',
                'joinTable' => 'business_relationship'));

    }
}