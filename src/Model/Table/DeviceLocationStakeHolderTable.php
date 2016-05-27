<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 26/05/2016
 * Time: 07:24 PM
 */

namespace App\Model\Table;


use Cake\ORM\Table;

class DeviceLocationStakeHolderTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('device_location_stakeholder');
        $this->primaryKey('device_location_stakeholder_id');
        $this->belongsTo('Stakeholder', [
            'className' => 'Users',
            'foreignKey' => 'user_id',
        ]);
        $this->belongsTo('UserInTrack', [
            'className' => 'Users',
            'foreignKey' => 'user_in_track_id',
        ]);
        $this->belongsTo("ReaxiumDevice", array('foreignKey' => 'device_id'));
    }

}