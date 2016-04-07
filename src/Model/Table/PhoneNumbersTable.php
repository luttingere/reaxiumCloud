<?php
namespace App\Model\Table;
use Cake\ORM\Table;
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 06/04/2016
 * Time: 08:54 AM
 */
class PhoneNumbersTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('phone_numbers');
        $this->primaryKey('phone_number_id');
        $this->belongsTo('Users',
            array('targetForeignKey' => 'user_id',
                'foreignKey' => 'phone_number_id',
                'joinTable' => 'phone_numbers_relationship'));
    }


}