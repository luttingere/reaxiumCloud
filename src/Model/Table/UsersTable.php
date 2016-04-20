<?php

namespace App\Model\Table;
use Cake\ORM\Table;

/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 22/03/2016
 * Time: 02:31 AM
 */
class UsersTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('users');
        $this->primaryKey("user_id");
        $this->belongsTo("Status", array('foreignKey' => 'status_id'));
        $this->belongsTo("UserType", array('foreignKey' => 'user_type_id'));
        $this->belongsTo("Business", array('foreignKey' => 'business_id'));
        $this->belongsToMany('PhoneNumbers',
            array('targetForeignKey' => 'phone_number_id',
                'foreignKey' => 'user_id',
                'joinTable' => 'phone_numbers_relationship'));

        $this->belongsToMany('Address',
            array('targetForeignKey' => 'address_id',
                'foreignKey' => 'user_id',
                'joinTable' => 'address_relationship'));

        $this->belongsToMany('Stakeholders',
            array('targetForeignKey' => 'stakeholder_id',
                'foreignKey' => 'user_id',
                'joinTable' => 'users_relationship'));
    }

}