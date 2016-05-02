<?php
namespace App\Model\Table;
use Cake\ORM\Table;
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 13/4/2016
 * Time: 04:50
 */

class UserAccessDataTable extends Table{

    public function initialize(array $config){
        parent::initialize($config);
        $this->table('user_access_data');
        $this->primaryKey('user_access_data_id');
        $this->belongsTo('Users' ,array('foreignKey' => 'user_id'));
        $this->belongsTo('AccessType' ,array('foreignKey' => 'access_type_id'));
    }


}