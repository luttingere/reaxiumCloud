<?php
namespace App\Model\Table;
use Cake\ORM\Table;
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 06/04/2016
 * Time: 11:51 AM
 */
class StakeholdersTable extends Table {

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('stakeholders');
        $this->primaryKey('stakeholder_id');
        $this->belongsTo('Status', array('foreignKey' => 'status_id'));
        $this->belongsTo('Users', array('foreignKey' => 'user_id'));
    }

}