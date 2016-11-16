<?php

namespace Stp\CrontabBundle\Command;

use Crontab\Manager\JobManagerInterface;
use Crontab\Model\Job;
use DateTime;
use Exception;
use Stp\CrontabBundle\Command\Validator\TypeTrait;
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
    use TypeTrait;

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
        $this->setName('crontab:worker')
            ->setDescription('Crontab worker')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Type (' . $this->getArrayAsString(Job::TYPES) . ')',
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

                $this->writeProcessesOutput();
                $this->finishOldProcesses();
            }
        } catch (Exception $e) {
            $this->logger->error('An error occurred during working as a worker. ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception' => get_class($e),
            ]);

            return 1;
        }

        $this->logger->critical('Congratulation! Worker should never stop, but it happened ;)');

        return 1;
    }

    /**
     * Run new processes
     */
    protected function runNewProcesses()
    {
        /** @var JobManagerInterface $jobManager */
        $jobManager = $this->container->get('stp_crontab.manager.job');

        $jobs = $jobManager->getDueJobs($this->type);
        /** @var Job $job */
        foreach ($jobs as $job) {
            $command = $this->getExecutableCommand($job->getCommand());
            if ($this->canRunCommand($command)) {
                try {
                    $process = new Process($command);
                    $process->start();

                    if ($job->getStatus() != Job::STATUS_IN_PROGRESS) {
                        $job->setStartedAt(new DateTime());
                        $jobManager->setJob($job->getId(), $job);
                    }

                    $processId = $process->getPid();
                    $array = [
                        $this->processKey => $process,
                        $this->jobKey => $job,
                    ];
                    $this->processes[$processId] = $array;
                } catch (Exception $e) {
                    $this->logger->warning('An error occurred during starting "' . $command . '" command. ' .
                        $e->getMessage(), [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'exception' => get_class($e),
                    ]);
                }
            }
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

        foreach ($this->processes as $processId => $array) {
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
        $sleep = $this->container->getParameter('stp_crontab.worker_sleep');
        $step = $this->container->getParameter('stp_crontab.worker_step');

        $year = $lastIteration->format('Y');
        $month = $lastIteration->format('m');
        $day = $lastIteration->format('d');
        $hour = $lastIteration->format('H');
        $min = $lastIteration->format('i');
        $sec = $lastIteration->format('s');

        // round seconds to multiple of step, like this:
        // $step = 10 - round $sec to 10, 20, 30, 40, 50, 60
        // $step = 15 - round $sec to 15, 30, 45, 60
        // $step = 30 - round $sec to 30, 60
        // $step = 60 /default value/ - round $sec to 60 /begin of next minute/
        $roundSec = floor(($sec + $step) / $step) * $step;
        $nextIteration = new DateTime(sprintf('%u-%u-%u %u:%u:%u', $year, $month, $day, $hour, $min, $roundSec));

        while (($now = new DateTime()) < $nextIteration) {
            $diff = $nextIteration->getTimestamp() - $now->getTimestamp();
            // use abs() because of negative $diff at daylight saving time in winter
            $sleep = min(abs($diff), $sleep);

            $this->writeProcessesOutput();
            $this->finishOldProcesses();

            sleep($sleep);
        }

        return $nextIteration;
    }

    /**
     * Write processes output
     */
    protected function writeProcessesOutput()
    {
        foreach ($this->processes as $processId => $array) {
            /** @var Process $process */
            $process = $array[$this->processKey];

            $this->logger->info($process->getOutput());
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

        foreach ($this->processes as $processId => $array) {
            /** @var Process $process */
            $process = $array[$this->processKey];
            /** @var Job $job */
            $job = $array[$this->jobKey];

            if ($forceFinish || $process->isTerminated()) {
                $job->setEndedAt(new DateTime());
                $jobManager->setJob($job->getId(), $job);

                unset($this->processes[$processId]);
            }
        }
    }
}
