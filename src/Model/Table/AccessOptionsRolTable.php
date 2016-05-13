<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 13/5/2016
 * Time: 04:50
 */

namespace App\Model\Table;
use Cake\ORM\Table;

class AccessOptionsRolTable extends Table{

    public function initialize(array $config){
        parent::initialize($config);
        $this->table('access_options_rol');
        $this->belongsTo('MenuApplication',array('foreignKey'=>'menu_id'));


    }
}