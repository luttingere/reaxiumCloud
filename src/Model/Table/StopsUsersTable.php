<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 18/4/2016
 * Time: 04:07
 */

namespace App\Model\Table;
use Cake\ORM\Table;

class StopsUsersTable extends Table{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('stops_users');
        $this->primaryKey('id_stops_user');
        $this->belongsTo('Users',array('foreignKey'=>'user_id'));
        $this->belongsTo('Stops',array('foreignKey'=>'id_stop'));
        $this->belongsTo('Routes',array('foreignKey'=>'id_route'));

    }

}