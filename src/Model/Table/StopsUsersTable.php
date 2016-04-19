<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 18/4/2016
 * Time: 04:07
 */

namespace App\Model\Table;


class StopsUsersTable extends Table{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('stops_users');
        $this->primaryKey('id_stops_user');
    }

}