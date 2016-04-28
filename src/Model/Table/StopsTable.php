<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 27/4/2016
 * Time: 09:47
 */

namespace App\Model\Table;
use Cake\ORM\Table;

class StopsTable extends Table{


    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('stops');
        $this->primaryKey('id_stop');
        $this->belongsTo('Stops',
            array('targetForeignKey' => 'id_route',
                'foreignKey' =>'id_stop',
                'joinTable' =>'routesStopsRelationship'));



    }






}