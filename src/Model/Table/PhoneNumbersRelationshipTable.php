<?php
namespace App\Model\Table;
use Cake\ORM\Table;
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 06/04/2016
 * Time: 10:14 AM
 */
class PhoneNumbersRelationshipTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('phone_numbers_relationship');
    }


}