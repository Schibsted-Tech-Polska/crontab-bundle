<?php

namespace Stp\CrontabBundle\Command;

use Crontab\Manager\JobManagerInterface;
use Crontab\Model\Job;
use DateTime;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

declare(ticks=1);

/**
 * Command
 */
class WorkerCommand extends BaseCommand
{
    /** @var string */
    protected $processKey = 'process';

    /** @var string */
    protected $jobKey = 'job';

    /** @var int */
    protected $type;

    /** @var array */
    protected $processes = [];

    /**
     * Shutdown handler
     */
    public function shutdownHandler()
    {
        $this->finishOldProcesses(true);

        // immediately finish application
        // so `exit` HAVE TO be here
        exit;
    }

    /**
     * Signal handler
     */
    public function signalHandler()
    {
        // immediately shutdown application,
        // so `exit` HAVE TO be here
        exit;
    }

    /**
     * Register shutdown handlers
     */
    protected function registerShutdownHandlers()
    {
        register_shutdown_function([
            $this,
            'shutdownHandler',
        ]);

        pcntl_signal(SIGHUP, [
            $this,
            'signalHandler',
        ]);
        pcntl_signal(SIGINT, [
            $this,
            'signalHandler',
        ]);
        pcntl_signal(SIGTERM, [
            $this,
            'signalHandler',
        ]);
    }

    /**
     * Configure
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('crontab:worker')
            ->setDescription('Crontab worker')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Type (' . $this->getTypesAsString() . ')',
                Job::TYPE_SINGLE)
        ;
    }

    /**
     * Initialize
     *
     * @param InputInterface  $input  input
     * @param OutputInterface $output output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->type = $this->getValidatedType($input->getOption('type'));

        $this->requireField('type', $this->type);
    }

    /**
     * Execute
     *
     * @param InputInterface  $input  input
     * @param OutputInterface $output output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lastIteration = new DateTime();

        try {
            $this->registerShutdownHandlers();

            while (true) {
                $this->runNewProcesses();
                $lastIteration = $this->waitForNextIteration($lastIteration);
            }

            $res = 1;
        } catch (Exception $e) {
            $res = 0;
        }

        if ($res) {
            $this->io->success('Congratulation! Worker should never stop, but it happened!');
        } else {
            $this->io->error('An error occurred during working as a worker');
        }

        return intval(!$res);
    }

    /**
     * Run new processes
     */
    protected function runNewProcesses()
    {
        /** @var JobManagerInterface $jobManager */
        $jobManager = $this->container->get('stp_crontab.manager.job');

        $jobs = $jobManager->getDueJobs($this->type);
        foreach ($jobs as $job) {
            $command = $this->getExecutableCommand($job->getCommand());
            if ($this->canRunCommand($command)) {
                $process = new Process($command);
                $process->start();

                $job->setStartedAt(new DateTime());
                $jobManager->setJob($job->getId(), $job);

                $processPid = $process->getPid();
                $array = [
                    $this->processKey => $process,
                    $this->jobKey => $job,
                ];
                $this->processes[$processPid] = $array;
            }
        }
    }

    /**
     * Finish old processes
     *
     * @param bool $forceFinish force finish
     */
    protected function finishOldProcesses($forceFinish = false)
    {
        /** @var JobManagerInterface $jobManager */
        $jobManager = $this->container->get('stp_crontab.manager.job');

        foreach ($this->processes as $processesPid => $array) {
            /** @var Process $process */
            $process = $array[$this->processKey];
            /** @var Job $job */
            $job = $array[$this->jobKey];

            if ($forceFinish || $process->isTerminated()) {
                $job->setEndedAt(new DateTime());
                $jobManager->setJob($job->getId(), $job);

                unset($this->processes[$processesPid]);
            }
        }
    }

    /**
     * Write processes output
     */
    protected function writeProcessesOutput()
    {
        foreach ($this->processes as $processesPid => $array) {
            /** @var Process $process */
            $process = $array[$this->processKey];

            $this->io->write($process->getOutput());
        }
    }

    /**
     * Get executable command
     *
     * @param string $command command
     *
     * @return string
     */
    protected function getExecutableCommand($command)
    {
        // @README: Due to some limitations in PHP, if you're using signals with the Process component,
        // you may have to prefix your commands with exec.
        // http://symfony.com/doc/current/components/process.html#process-signals
        $command = 'exec ' . $command;

        return $command;
    }

    /**
     * Can run command /system is NOT overloaded/?
     *
     * @param string $command command
     *
     * @return bool
     */
    protected function canRunCommand($command)
    {
        $processesLimit = getenv('PROCESSES_LIMIT');
        if (empty($processesLimit)) {
            $processesLimit = $this->container->getParameter('stp_crontab.processes_limit');
        }

        if (count($this->processes) < $processesLimit) {
            return true;
        }

        foreach ($this->processes as $processesPid => $array) {
            /** @var Process $process */
            $process = $array[$this->processKey];

            if ($command == $process->getCommandLine()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Wait for next iteration
     *
     * @param DateTime $lastIteration last iteration
     *
     * @return DateTime
     */
    protected function waitForNextIteration(DateTime $lastIteration)
    {
        $year = $lastIteration->format('Y');
        $month = $lastIteration->format('m');
        $day = $lastIteration->format('d');
        $hour = $lastIteration->format('H');
        $min = $lastIteration->format('i');
        $sec = $lastIteration->format('s');

        $delay = $this->container->getParameter('stp_crontab.worker_delay');
        $sec = floor(($sec + $delay) / $delay) * $delay;
        $nextIteration = new DateTime(sprintf('%u-%u-%u %u:%u:%u', $year, $month, $day, $hour, $min, $sec));

        while (new DateTime() < $nextIteration) {
            $this->writeProcessesOutput();
            $this->finishOldProcesses();

            sleep(1);
        }

        return $nextIteration;
    }
}
