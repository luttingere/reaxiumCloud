<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Created by PhpStorm.
 * User: SinAsignari54GB1TB
 * Date: 20/03/2016
 * Time: 07:26 PM
 */
class Status extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'status_id' => false
    ];

}