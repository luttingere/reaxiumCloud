<?php
namespace App\Model\Table;
use Cake\ORM\Table;
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 22/03/2016
 * Time: 04:21 AM
 */
class UserAccessControlTable extends Table{

    public function initialize(array $config){
        parent::initialize($config);
        $this->table('users_access_control');
        $this->primaryKey('access_id');
        $this->belongsTo('Status', array('foreignKey' => 'status_id'));
    }


}