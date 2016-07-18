<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 15/7/2016
 * Time: 03:24
 */

namespace App\Model\Table;
use Cake\ORM\Table;


class BusinessRoutesTable extends Table{

    public function initialize(array $config){

        parent::initialize($config);
        $this->table('business_routes');
        $this->belongsTo('Business', array('foreignKey' => 'business_id'));
        $this->belongsTo('Routes', array('foreignKey' => 'id_route'));

    }
}