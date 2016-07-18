<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 21/4/2016
 * Time: 03:44
 */

namespace App\Model\Table;
use Cake\ORM\Table;

class RoutesTable extends Table{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('routes');
        $this->primaryKey('id_route');

        $this->belongsToMany('Stops',
            array('targetForeignKey' => 'id_stop',
                'foreignKey' => 'id_route',
                'joinTable' => 'routesStopsRelationship'));

        $this->belongsToMany('Business',
            array('targetForeignKey' => 'business_id',
                'foreignKey' => 'id_route',
                'joinTable' => 'business_routes'));

    }

}