<?php

namespace Gearman\Framework;


class StatusChecker {

    /**
     * @var \GearmanClient
     */
    private $gmClient;
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function __construct() {
        $this->gmClient = new \GearmanClient();
        $this->gmClient->addServer();
        $this->entityManager = \DatabaseAccess::getInstance();
    }

    public function check() {
        $repository = $this->entityManager->getRepository('GearmanJobStatus');
        $gearmanJobs = $repository->findBy(array('status' => 1));

        $result = array();
        foreach ($gearmanJobs as $gearmanJob) {
            $jobStatus = $this->gmClient->jobStatus($gearmanJob->getId());
            if ($jobStatus[1]) {
                $result[] = $jobStatus;
            }
        }
        return $result;
    }
} 