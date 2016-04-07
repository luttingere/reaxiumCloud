<?php

namespace App\Model\Table;
use Cake\ORM\Table;

/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 06/04/2016
 * Time: 09:25 AM
 */
class UserTypeTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('user_type');
        $this->primaryKey('user_type_id');
    }
}