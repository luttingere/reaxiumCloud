<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 13/5/2016
 * Time: 02:02
 */

namespace App\Model\Table;

use Cake\ORM\Table;
class MenuApplicationTable extends Table{

    public function initialize(array $config){
        parent::initialize($config);
        $this->table('menu_application');
        $this->primaryKey('menu_id');
        $this->hasMany('SubMenuApplication',array('foreignKey'=>'menu_id'));

    }
}