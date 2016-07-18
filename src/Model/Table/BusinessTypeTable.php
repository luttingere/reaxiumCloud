<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 14/7/2016
 * Time: 10:48
 */

namespace App\Model\Table;
use Cake\ORM\Table;

class BusinessTypeTable extends Table{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('business_type');
        $this->primaryKey('business_type_id');

    }
}