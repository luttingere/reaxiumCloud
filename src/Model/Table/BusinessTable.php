<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 07/04/2016
 * Time: 12:14 PM
 */


namespace App\Model\Table;
use Cake\ORM\Table;


class BusinessTable extends Table{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('business');
        $this->primaryKey('business_id');
        $this->belongsTo('Address',array('foreignKey' => 'address_id'));
        $this->belongsTo('PhoneNumbers',array('foreignKey' => 'phone_number_id'));
        $this->belongsTo('Status', array('foreignKey' => 'status_id'));
        $this->belongsTo('BusinessType', array('foreignKey' => 'business_type_id'));

        $this->belongsToMany('ReaxiumDevice',
            array('targetForeignKey' => 'device_id',
                'foreignKey' => 'business_id',
                'joinTable' => 'device_business'));


        $this->belongsToMany('Routes',
            array('targetForeignKey' => 'id_route',
                'foreignKey' => 'business_id',
                'joinTable' => 'business_routes'));
    }

}