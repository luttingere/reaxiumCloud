<?php
namespace App\Model\Table;
use Cake\ORM\Table;
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 06/04/2016
 * Time: 11:18 AM
 */
class AddressRelationshipTable extends Table {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('address_relationship');
    }
}