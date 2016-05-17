<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 13/5/2016
 * Time: 02:04
 */

namespace App\Model\Table;

use Cake\ORM\Table;
class SubMenuApplicationTable extends Table{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('sub_menu_application');
        $this->primaryKey('sub_menu_id');

    }
}