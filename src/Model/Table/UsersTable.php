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
    }

}