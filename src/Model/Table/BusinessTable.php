<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 07/04/2016
 * Time: 12:14 PM
 */


namespace App\Model\Table;
use Cake\ORM\Table;


class BusinessTable extends Table{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('business');
        $this->primaryKey('business_id');
    }

}