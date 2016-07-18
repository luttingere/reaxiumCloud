<?php
/**
 * Created by PhpStorm.
 * User: VladimirIlich
 * Date: 15/7/2016
 * Time: 03:23
 */

use Cake\ORM\Entity;

class BusinessRoute extends Entity{

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