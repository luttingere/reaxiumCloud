<?php
namespace App\Model\Entity;
use Cake\ORM\Entity;
/**
 * Created by PhpStorm.
 * User: SinAsignari54GB1TB
 * Date: 22/03/2016
 * Time: 03:29 AM
 */
class AccessType extends Entity
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
        '*' => true
    ];
}