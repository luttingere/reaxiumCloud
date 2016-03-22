<?php
namespace App\Model\Table;
use Cake\ORM\Table;
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 18/03/2016
 * Time: 01:26 PM
 */

class ApplicationsTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('applications');
        $this->primaryKey('application_id');
        $this->belongsToMany('ReaxiumDevice',
            array('targetForeignKey' => 'device_id',
                'foreignKey' => 'application_id',
                'joinTable' => 'applications_relationship'));
        $this->belongsTo('Status', array('foreignKey' => 'status_id'));
    }

}