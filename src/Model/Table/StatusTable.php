<?php
namespace App\Model\Table;
use Cake\ORM\Table;
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 20/03/2016
 * Time: 07:16 PM
 */
class StatusTable extends Table
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('status');
        $this->primaryKey('status_id');
    }

}