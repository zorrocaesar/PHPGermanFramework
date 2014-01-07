<?php
/**
 * Created by PhpStorm.
 * User: Adi
 * Date: 07.01.2014
 * Time: 21:47
 */

namespace Gearman\Framework;

interface ImportManagerInterface {
    public function generateWork();
    public function processWork();
} 