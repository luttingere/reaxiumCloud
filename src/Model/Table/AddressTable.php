<?php
namespace App\Model\Table;
use Cake\ORM\Table;
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 06/04/2016
 * Time: 10:15 AM
 */
class AddressTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('address');
        $this->primaryKey('address_id');
        $this->belongsTo('Users',
            array('targetForeignKey' => 'user_id',
                'foreignKey' => 'address_id',
                'joinTable' => 'address_relationship'));
    }

}