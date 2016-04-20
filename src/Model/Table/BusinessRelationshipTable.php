<?php
/**
 * Created by PhpStorm.
 * User: Eduardo Luttinger
 * Date: 07/04/2016
 * Time: 01:16 PM
 */

namespace App\Model\Table;
use Cake\ORM\Table;

class BusinessRelationshipTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->table('business_relationship');
    }

}