<?php

use Gearman\Framework\ImportManager;
use Gearman\Framework\StatusChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;

class Launcher extends Command
{
    protected function configure()
    {
        $this
            ->setName('gearman')
            ->setDescription('Gearman related actions')
            ->addArgument(
                'amount',
                InputArgument::OPTIONAL,
                'How much data do you want to add?'
            )
            ->addOption(
               'worker',
               'w',
               InputOption::VALUE_NONE,
               'Launches a Gearman worker'
            )
            ->addOption(
                'generateTestData',
                'g',
                InputOption::VALUE_OPTIONAL,
                'Fills database with test data'
            )
            ->addOption(
                'startManager',
                'm',
                InputOption::VALUE_NONE,
                'Starts the Import Manager'
            )
            ->addOption(
                'checkStatus',
                'c',
                InputOption::VALUE_NONE,
                'Checks the status of running jobs'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('worker')) {
            $worker = new \GearmanWorker();
            $worker->addServer();
            $worker->addFunction("reverse", array('\Gearman\Worker\GearmanWorker', 'reverse'), $output);
            $output->writeln('<info>Worker started. Waiting for work...</info>');
            while ($worker->work());
        }

        if ($input->getOption('generateTestData')) {

            $amount = $input->getArgument('amount') ? $input->getArgument('amount') : null;
            $dataGenerator = new DataGenerator();
            $dataGenerator->generate($amount);
            $output->writeln('<info>Test data generated</info>');
        }

        if ($input->getOption('startManager')) {
            $importManager = new ImportManager($output);
            $importManager->generateWork();
            //$importManager->processWork();
        }

        if ($input->getOption('checkStatus')) {
            $statusChecker = new StatusChecker;
            $result = $statusChecker->check();
            if ($result) {
                $countJobsRunning = count($result);
                $output->writeln('<info>' . $countJobsRunning . ' job(s) running:</info>');

                foreach ($result as $jobRunning) {
                    /**
                     * @var \Symfony\Component\Console\Helper\ProgressHelper $progress
                     */
                    $progress = $this->getHelperSet()->get('progress');
                    $progress->start($output, $jobRunning[3]);
                    $progress->setCurrent($jobRunning[2]);
                    $progress->finish();
                }
            }
        }
    }
}