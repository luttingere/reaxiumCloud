<?php
namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 06/04/2016
 * Time: 12:53 PM
 */
class UsersRelationshipTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('users_relationship');
        $this->belongsTo('Users', array('foreignKey' => 'user_id'));
    }

}