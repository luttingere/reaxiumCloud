<?php

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 18/03/2016
 * Time: 12:51 PM
 */
class ReaxiumDeviceTable extends Table{

    /**
     * Relacion entre la Tabla reaxium_device con el modelo ORM de Cake
     * @param array $config
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('reaxium_device');
        $this->primaryKey('device_id');
        $this->belongsToMany('Applications',
            array('targetForeignKey' => 'application_id',
                'foreignKey' => 'device_id',
                'joinTable' => 'applications_relationship'));

        $this->belongsTo('Status', array('foreignKey' => 'status_id'));

        $this->belongsToMany('Business',
            array('targetForeignKey' => 'business_id',
                'foreignKey' => 'device_id',
                'joinTable' => 'device_business'));
    }


}