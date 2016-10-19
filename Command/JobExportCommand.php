<?php

namespace Stp\CrontabBundle\Command;

use Crontab\Manager\JobManagerInterface;
use Crontab\Model\Job;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command
 */
class JobExportCommand extends BaseCommand
{
    /**
     * Configure
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('crontab:job:export')
            ->setDescription('Crontab job export')
        ;
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
        $dateFormat = 'Y-m-d H:i:s';
        $headers = [
            'Id',
            'Expression',
            'Command',
            'Type',
            'Active',
            'Status',
            'Instance started at',
            'Instance ended at',
            'Created at',
            'Updated at',
            'Comment',
        ];
        $rows = [];

        try {
            /** @var JobManagerInterface $jobManager */
            $jobManager = $this->container->get('stp_crontab.manager.job');

            $jobs = $jobManager->getJobs();
            foreach ($jobs as $job) {
                $rows[] = [
                    $job->getId(),
                    $job->getExpression(),
                    $job->getCommand(),
                    Job::TYPES[$job->getType()],
                    ($job->getActive() ? 'yes' : 'no'),
                    Job::STATUSES[$job->getStatus()],
                    ($job->getStartedAt() !== null ? $job->getStartedAt()
                        ->format($dateFormat) : '-'),
                    ($job->getEndedAt() !== null ? $job->getEndedAt()
                        ->format($dateFormat) : '-'),
                    ($job->getCreatedAt() !== null ? $job->getCreatedAt()
                        ->format($dateFormat) : '-'),
                    ($job->getUpdatedAt() !== null ? $job->getUpdatedAt()
                        ->format($dateFormat) : '-'),
                    $job->getComment(),
                ];
            }

            $res = 1;
        } catch (Exception $e) {
            $res = 0;
        }

        if ($res) {
            $this->io->table($headers, $rows);
        } else {
            $this->io->error('An error occurred during exporting jobs');
        }

        return intval(!$res);
    }
}
