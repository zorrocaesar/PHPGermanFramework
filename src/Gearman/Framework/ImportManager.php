<?php

namespace Gearman\Framework;

use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Output\OutputInterface;

class ImportManager extends ImportManagerAbstract {

    protected $generateWhileInProgress = true;

    protected $pageSize = 1000;

    /**
     * @var OutputInterface
     */
    protected $output;

    /** @var \Doctrine\ORM\EntityManager */
    protected $entityManager;

    public function __construct(OutputInterface $output) {
        $this->output = $output;
        $this->entityManager = \DatabaseAccess::getInstance();
    }

    /**
     * Retrieves all the original items to be processed
     * @return array
     */
    private function getOriginalItems() {
        $repository = $this->entityManager->getRepository('Item');
        $queryBuilder = $repository->createQueryBuilder('u')->select('u.id');
        $query = $queryBuilder->getQuery();
        return $query->getArrayResult();
    }

    /**
     * This method generates work items from an external data source
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function generateWork() {
        /**
         * Proceed only if generation of new work items is allowed
         * while old items are still in progress
         */
        if ($this->generateWhileInProgress()) {
            /**
             * Before proceeding, we should delete old, processed items
             */
            $this->clearFinishedItems();

            $this->output->write('Generating work...');
            /**
             * Here, we generate the Gearman work items out of the original items
             */
            foreach ($this->getOriginalItems() as $result) {
                $gearmanItem = new \GearmanItem();
                $gearmanItem->setId($result['id']);
                $gearmanItem->setError(0);
                $gearmanItem->setInProgress(0);
                $gearmanItem->setFinished(0);
                $this->entityManager->persist($gearmanItem);
            }

            try {
                $this->entityManager->flush($gearmanItem);
            } catch (DBALException $e) {
                if ($e->getPrevious()->getCode() != 23000) {
                    throw $e;
                }
            }
            $this->output->writeln('<info>done!</info>');
        } else {
            $this->output->writeln('Generating not allowed while some items are not finished.');
        }
    }

    /**
     * Checks if there are items to be processed. They will be having the in_progress = 1 and finished = 0
     * @return bool
     */
    public function noWorkLeft() {
        $this->output->write('Check if there is work in progress...');
        $entityManager = \DatabaseAccess::getInstance();
        $repository = $entityManager->getRepository('GearmanItem');
        $results = $repository->findBy(array('in_progress' => '1', 'error' => '0'));
        $count = count($results);
        if ($count) {
            $this->output->writeln('<comment>affirmative! Exit.</comment>');
            return false;
        } else {
            $this->output->writeln('<info>negative! Proceeding.</info>');
            return true;
        }
    }

    /**
     * Retrieves the Gearman work items, splits them into pages and sends the pages to Gearman as jobs
     */
    public function processWork() {
        /**
         * Only proceed if there are no more items still being processed
         */
        if ($this->noWorkLeft()) {

            $gmClient = new \GearmanClient();
            $gmClient->addServer();
            if ($gmClient) {
                $gmClient->setCreatedCallback(array($this, 'createCallback'));
            }

            $page = 1;
            $count = $this->pageSize;
            while($count > 0) {
                $idsToSend = $this->getAvailableGearmanWorkItemsIds();
                if(!empty($idsToSend)) {
                    /**
                     * Gearman client is initialized and ready to send jobs to server
                     */
                    if($gmClient) {
                        $this->sendGermanJob($page, $idsToSend, $gmClient);
                    } else {
                        /**
                         * If the Gearman client is not present, mark all IDs about to be sent as
                         * having an error
                         */
                        $this->markAsError();
                    }
                }

                $count = count($idsToSend);
                $page++;
            }

            if($gmClient) {
                $gmClient->runTasks();
            }
        }
    }

    public function checkStatus($task) {
        echo "STATUS: " . $task->unique() . ", " . $task->jobHandle() . " - " . $task->taskNumerator() .
                "/" . $task->taskDenominator() . "\n";
    }

    /**
     * Saves newly created Gearman jobs in the database
     * in order to keep track of their progress later
     *
     * @param \GearmanTask $job
     */
    public function createCallback(\GearmanTask $job) {
        $gearmanJobStatus = new \GearmanJobStatus();
        $gearmanJobStatus->setId($job->jobHandle());
        $gearmanJobStatus->setStatus(1);
        $gearmanJobStatus->setPercent(0);
        $this->entityManager->persist($gearmanJobStatus);
        $this->entityManager->flush();
    }

    public function generateWhileInProgress() {
        return $this->generateWhileInProgress;
    }

    protected function clearFinishedItems() {
        $this->output->write('Removing previously finished data...');

        $entityManager = \DatabaseAccess::getInstance();
        $repository = $entityManager->getRepository('GearmanItem');
        $queryBuilder = $repository->createQueryBuilder('u')
            ->delete()
            ->andWhere('u.in_progress = 0')
            ->andWhere('u.finished = 1')
            ->andWhere('u.error = 0');
        $queryBuilder->getQuery()->execute();
        $this->output->writeln('<info>done!</info>');

    }

    /**
     * This method returns work items IDs that are ready to be processed
     * This means they will have the in_progress flag set to 0
     *
     * @return array
     */
    protected function getAvailableGearmanWorkItemsIds()
    {
        $repository = $this->entityManager->getRepository('GearmanItem');
        $queryBuilder = $repository->createQueryBuilder('u')
            ->select('u.id')
            ->where('u.in_progress = 0')
            ->setMaxResults($this->pageSize);
        $results = $queryBuilder->getQuery()->getResult();

        $idsToSend = array();
        foreach ($results as $result) {
            $idsToSend[] = $result['id'];
        }
        return $idsToSend;
    }

    /**
     * @param $page
     * @param $idsToSend
     * @param $gmClient
     */
    private function sendGermanJob($page, $idsToSend, $gmClient)
    {
        $this->output->writeln('Sending task <comment>' . $page . '</comment>');
        $config = new \stdClass();
        $config->ids = $idsToSend;
        $gmClient->addTaskBackground('reverse', json_encode($config));
        $this->entityManager->getConnection()->executeUpdate('
                            UPDATE gearmanItems
                            SET in_progress = 1
                            WHERE in_progress = 0
                            LIMIT ' . $this->pageSize . '
                        ');
    }

    /**
     * Mark work items as errors
     */
    protected function markAsError()
    {
        $this->entityManager->getConnection()->executeUpdate('
                            UPDATE gearmanItems
                            SET error = 1
                            WHERE in_progress = 0
                            AND error = 0
                            LIMIT ' . $this->pageSize . '
                        ');
    }
} 