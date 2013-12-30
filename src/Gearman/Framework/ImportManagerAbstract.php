<?php
/**
 * Created by PhpStorm.
 * User: Adi
 * Date: 24.11.2013
 * Time: 23:45
 */

namespace Gearman\Framework;


/**
 * The Import Manager handles the creation and distribution of work for the Gearman workers
 *
 * Class ImportManagerAbstract
 * @package Gearman\Framework
 */
abstract class ImportManagerAbstract {

    protected $pageSize = 250;

    public function noWorkLeft() {

    }
} 