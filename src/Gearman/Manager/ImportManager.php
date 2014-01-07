<?php

namespace Gearman\Manager;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Gearman\Framework;

class ImportManager extends  Framework\ImportManagerAbstract
{

    protected $_generateWhileInProgress = true;
    protected $pageSize = 1000;
    /** @var EntityManager */
    protected $_entityManager;

    /**
     * This method generates work items from an external data source
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function generateWork()
    {
        /**
         * Proceed only if generation of new work items is allowed
         * while old items are still in progress
         */
        if ($this->_generateWhileInProgress) {
            /**
             * Before proceeding, we should delete old, processed items
             */
            $this->_clearFinishedItems();

            $this->_output->write('Generating work...');
            /**
             * Here, we generate the Gearman work items out of the original items
             */
            foreach ($this->_getOriginalItems() as $result) {
                $gearmanItem = new \GearmanItem();
                $gearmanItem->setId($result['id']);
                $gearmanItem->setError(0);
                $gearmanItem->setInProgress(0);
                $gearmanItem->setFinished(0);
                $this->_entityManager->persist($gearmanItem);
            }

            try {
                $this->_entityManager->flush($gearmanItem);
            } catch (DBALException $e) {
                if ($e->getPrevious()->getCode() != 23000) {
                    throw $e;
                }
            }
            $this->_output->writeln('<info>done!</info>');
        } else {
            $this->_output->writeln('Generating not allowed while some items are not finished.');
        }
    }

    protected function _clearFinishedItems()
    {
        $this->_output->write('Removing previously finished data...');

        $repository = $this->_entityManager->getRepository('GearmanItem');
        $queryBuilder = $repository->createQueryBuilder('u')
            ->delete()
            ->andWhere('u.in_progress = 0')
            ->andWhere('u.finished = 1')
            ->andWhere('u.error = 0');
        $queryBuilder->getQuery()->execute();
        $this->_output->writeln('<info>done!</info>');

    }

    /**
     * Retrieves the Gearman work items, splits them into pages and sends the pages to Gearman as jobs
     */
    public function processWork()
    {
        /**
         * Only proceed if there are no more items still being processed
         */
        if ($this->_noWorkLeft()) {

            $gmClient = new \GearmanClient();
            $gmClient->addServer();
            if ($gmClient) {
                $gmClient->setCreatedCallback(array($this, 'createCallback'));
            }

            $page = 1;
            $count = $this->pageSize;
            while ($count > 0) {
                $idsToSend = $this->_getAvailableGearmanWorkItemsIds();
                if (!empty($idsToSend)) {
                    /**
                     * Gearman client is initialized and ready to send jobs to server
                     */
                    if ($gmClient) {
                        $this->_sendGearmanJob($page, $idsToSend, $gmClient);
                    } else {
                        /**
                         * If the Gearman client is not present, mark all IDs about to be sent as
                         * having an error
                         */
                        $this->_markAsError();
                    }
                }

                $count = count($idsToSend);
                $page++;
            }

            if ($gmClient) {
                $gmClient->runTasks();
            }
        }
    }

    /**
     * Checks if there are items to be processed. They will be having the in_progress = 1 and finished = 0
     * @return bool
     */
    protected function _noWorkLeft()
    {
        $this->_output->write('Check if there is work in progress...');
        $repository = $this->_entityManager->getRepository('GearmanItem');
        $results = $repository->findBy(array('in_progress' => '1', 'error' => '0'));
        $count = count($results);
        if ($count) {
            $this->_output->writeln('<comment>affirmative! Exit.</comment>');
            return false;
        } else {
            $this->_output->writeln('<info>negative! Proceeding.</info>');
            return true;
        }
    }

    protected function _getAvailableGearmanWorkItemsIds()
    {
        $repository = $this->_entityManager->getRepository('GearmanItem');
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

    protected function _sendGearmanJob($page, $idsToSend, $gmClient)
    {
        $this->_output->writeln('Sending task <comment>' . $page . '</comment>');
        $config = new \stdClass();
        $config->ids = $idsToSend;
        $gmClient->addTaskBackground('reverse', json_encode($config));
        $this->_entityManager->getConnection()->executeUpdate('
                            UPDATE gearmanItems
                            SET in_progress = 1
                            WHERE in_progress = 0
                            LIMIT ' . $this->pageSize . '
                        ');
    }

    /**
     * Mark work items as errors
     */
    protected function _markAsError()
    {
        $this->_entityManager->getConnection()->executeUpdate('
                            UPDATE gearmanItems
                            SET error = 1
                            WHERE in_progress = 0
                            AND error = 0
                            LIMIT ' . $this->pageSize . '
                        ');
    }

    /**
     * Saves newly created Gearman jobs in the database
     * in order to keep track of their progress later
     *
     * @param \GearmanTask $job
     */
    public function createCallback(\GearmanTask $job)
    {
        $gearmanJobStatus = new \GearmanJobStatus();
        $gearmanJobStatus->setId($job->jobHandle());
        $gearmanJobStatus->setStatus(1);
        $gearmanJobStatus->setPercent(0);
        $this->_entityManager->persist($gearmanJobStatus);
        $this->_entityManager->flush();
    }

    /**
     * Retrieves all the original items to be processed
     */
    protected function _getOriginalItems()
    {
        $repository = $this->_entityManager->getRepository('Item');
        $queryBuilder = $repository->createQueryBuilder('u')->select('u.id');
        $query = $queryBuilder->getQuery();
        return $query->getArrayResult();
    }
} 