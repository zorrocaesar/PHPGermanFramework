<?php

namespace Gearman\Tests;

use \Gearman\Framework\ImportManager;
use \Symfony\Component\Console\Output\NullOutput;


class ImportManagerTest extends \PHPUnit_Framework_TestCase {

    public function testGenerateWorkWhileInProgressIsNotAllowed() {
        $output = new NullOutput();
        $importManager = $this->getMock(
            '\Gearman\Framework\ImportManager',
            array('generateWhileInProgress', 'clearFinishedItems'),
            array($output)
        );

        $importManager->expects($this->once())
            ->method('generateWhileInProgress')
            ->will($this->returnValue(false));

        $importManager->expects($this->never())
            ->method('clearFinishedItems');
        $importManager->generateWork();

    }

    public function testGenerateWorkWhileInProgressIsAllowed() {
        $output = new NullOutput();

        $importManager = $this->getMock(
            '\Gearman\Framework\ImportManager',
            array('generateWhileInProgress', 'clearFinishedItems'),
            array($output)
        );

        $importManager->expects($this->once())
            ->method('generateWhileInProgress')
            ->will($this->returnValue(true));

        $importManager->expects($this->once())
            ->method('clearFinishedItems');
        $importManager->generateWork();
    }
}
 