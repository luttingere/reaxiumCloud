<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 18/4/2016
 * Time: 03:05
 */

namespace App\Model\Table;


use Cake\ORM\Table;

class RoutesStopsRelationshipTable extends Table{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('routes_stops_relationship');
        $this->belongsTo('Stops', array('foreignKey' => 'id_stop'));
        $this->belongsTo('Routes', array('foreignKey' => 'id_route'));

    }

}