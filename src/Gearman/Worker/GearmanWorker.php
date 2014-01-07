<?php
/**
 * Created by PhpStorm.
 * User: Adi
 * Date: 17.11.2013
 * Time: 23:39
 */

namespace Gearman\Worker;

use Gearman\Framework\GearmanWorkerInterface;
use \GearmanWorker as GMWorker;
use Symfony\Component\Console\Output\OutputInterface;


class GearmanWorker implements GearmanWorkerInterface {

    /**
     * @var OutputInterface
     */
    protected $_output;
    /** @var \Doctrine\ORM\EntityManager */
    protected $_entityManager;

    public function __construct(OutputInterface $output, \Doctrine\ORM\EntityManager $entityManager) {
        $this->_output = $output;
        $this->_entityManager = $entityManager;
    }

    public function work() {
        $worker = new GMWorker();
        $worker->addServer();
        $worker->addFunction("reverse", array($this, 'reverse'));
        $this->_output->writeln('<info>Worker started. Waiting for work...</info>');
        while ($worker->work());
    }

    public function reverse(\GearmanJob $job) {
        $this->_output->writeln('Starting work...');
        $workload = json_decode($job->workload());

        $repositoryItem = $this->_entityManager->getRepository('Item');
        $repositoryGearmanItem = $this->_entityManager->getRepository('GearmanItem');

        $totalItems = count($workload->ids);
        $currentItem = 1;

        foreach ($workload->ids as $id) {
            $gearmanItem = $repositoryGearmanItem->find($id);

            /**
             * If the id item is finished, than it must have previously processed so we skip it
             */
            if ($gearmanItem->getFinished() == 1) {
                $this->_output->writeln('Processing ID <comment>' . $id . '</comment>: <question>skipping</question>');
                continue;
            }

            $item = $repositoryItem->find($id);
            $this->_output->writeln('Processing ID <comment>' . $id . '</comment>: ' . $item->getName() . ' ==reversing==> <info>' . strrev($item->getName()) . '</info>');

            $item->setName(strrev($item->getName()));
            $this->_entityManager->persist($item);

            $gearmanItem->setFinished(1);
            $this->_entityManager->persist($gearmanItem);

            $this->_entityManager->flush();

            $job->sendStatus($currentItem, $totalItems);
            $currentItem++;
        }

        $this->_output->writeln('Work done!');
        return;
    }
} 