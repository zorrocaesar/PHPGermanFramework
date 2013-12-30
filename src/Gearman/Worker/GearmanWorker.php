<?php
/**
 * Created by PhpStorm.
 * User: Adi
 * Date: 17.11.2013
 * Time: 23:39
 */

namespace Gearman\Worker;


class GearmanWorker {

    public function reverse(\GearmanJob $job, $output) {
        $output->writeln('Starting work...');
        $workload = json_decode($job->workload());

        $entityManager = \DatabaseAccess::getInstance();
        $repositoryItem = $entityManager->getRepository('Item');
        $repositoryGearmanItem = $entityManager->getRepository('GearmanItem');

        $totalItems = count($workload->ids);
        $currentItem = 1;

        foreach ($workload->ids as $id) {
            $gearmanItem = $repositoryGearmanItem->find($id);

            /**
             * If the id item is finished, than it must have previously processed so we skip it
             */
            if ($gearmanItem->getFinished() == 1) {
                $output->writeln('Processing ID <comment>' . $id . '</comment>: <question>skipping</question>');
                continue;
            }

            $item = $repositoryItem->find($id);
            $output->writeln('Processing ID <comment>' . $id . '</comment>: ' . $item->getName() . ' ==reversing==> <info>' . strrev($item->getName()) . '</info>');

            $item->setName(strrev($item->getName()));
            $entityManager->persist($item);

            $gearmanItem->setFinished(1);
            $entityManager->persist($gearmanItem);

            $entityManager->flush();

            $job->sendStatus($currentItem, $totalItems);
            $currentItem++;
        }

        $output->writeln('Work done!');
        return;
    }
} 