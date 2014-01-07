<?php

namespace Gearman\Framework;

use Symfony\Component\Console\Output\OutputInterface;


/**
 * The Import Manager handles the creation and distribution of work for the Gearman workers
 *
 * Class ImportManagerAbstract
 * @package Gearman\Framework
 */
abstract class ImportManagerAbstract implements ImportManagerInterface {

    protected $_pageSize = 250;
    /** @var  OutputInterface */
    protected $_output;
    protected $_entityManager;
    /** @var  bool */
    protected $_generateWhileInProgress;

    public function __construct(OutputInterface $output, $entityManager) {
        $this->_output = $output;
        $this->_entityManager = $entityManager;
    }

    /**
     * This method generates work items from an external data source
     */
    public function generateWork() {}

    /**
     * @uses self::_noWorkLeft()
     * @uses self::createCallback()
     * @uses self::_getAvailableGearmanWorkItemsIds()
     * @uses self::__sendGermanJob()
     * @uses self::_markAsError()
     */
    public function processWork() {}

    /**
     * This method is called by the Gearman client whenever a new job is created.
     * It Saves newly created Gearman jobs in the database
     * in order to keep track of their progress later.
     *
     * Must be public so it can be callable
     *
     * @param \GearmanTask $job
     */
    public function createCallback(\GearmanTask $job) {}

    /**
     * Retrieves all the original items to be processed
     *
     * @uses $rntityManager
     * @return array
     */
    protected function _getOriginalItems() {}

    /**
     * This method clears the old finished items before generating some new ones
     */
    protected function _clearFinishedItems() {}

    /**
     * This method checks if there are items to be processed.
     */
    protected function _noWorkLeft() {}

    /**
     * This method returns work items IDs that are ready to be processed
     * This means they will have the in_progress flag set to 0
     *
     * @return array
     */
    protected function _getAvailableGearmanWorkItemsIds() {}

    /**
     * This method creates and sends the job to ghe Gearman client as a background task
     * It should also set the in_progress flag to 1 for the items being sent
     *
     *
     * @param int $page
     * @param array $idsToSend
     * @param \GearmanClient $gmClient
     */
    protected function _sendGearmanJob($page, $idsToSend, $gmClient) {}

    /**
     * Marks the items about to be sent as having an error
     * Usually used when the Gearman client returns an error and the job cannot be sent
     */
    protected function _markAsError() {}
} 