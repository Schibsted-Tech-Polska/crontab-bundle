<?php

namespace Stp\CrontabBundle\Command;

use Crontab\Manager\JobManagerInterface;
use Crontab\Model\Job;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command
 */
class ListCommand extends BaseCommand
{
    /** @var array */
    protected $columns;

    /**
     * Configure
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('crontab:list')
            ->setDescription('Crontab job list')
            ->addOption('columns', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Columns (' . $this->getArrayAsString(self::COLUMNS) . ')', [
                    'nr',
                    'id',
                    'exp',
                    'cmd',
                    'typ',
                    'act',
                    'sta',
                    'srt',
                    'end',
                    'dur',
                ])
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

        $this->columns = $this->getValidatedColumns($input->getOption('columns'));
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
        $headers = [];
        $rows = [];

        try {
            /** @var JobManagerInterface $jobManager */
            $jobManager = $this->container->get('stp_crontab.manager.job');

            foreach ($this->columns as $column) {
                $headers[] = self::COLUMNS[$column];
            }

            $jobs = $jobManager->getJobs();
            foreach ($jobs as $number => $job) {
                $row = [];
                foreach ($this->columns as $column) {
                    $row[] = $this->getDataForColumn($column, $number, $job);
                }
                $rows[] = $row;
            }

            $res = 1;
        } catch (Exception $e) {
            $res = 0;
        }

        if ($res) {
            $this->io->table($headers, $rows);
        } else {
            $this->io->error('An error occurred during listing jobs');
        }

        return intval(!$res);
    }

    /**
     * Get data for column
     *
     * @param string $column column
     * @param int    $number number
     * @param Job    $job    job
     *
     * @return string
     */
    protected function getDataForColumn($column, $number, Job $job)
    {
        $dateFormat = 'Y-m-d H:i:s';
        $unknownStatement = '-';
        $data = '';

        switch ($column) {
            case 'act':
                if ($job->getActive() == true) {
                    $activePrefix = '<info>';
                    $activeSuffix = '</info>';
                } else {
                    $activePrefix = '<comment>';
                    $activeSuffix = '</comment>';
                }
                $data = $activePrefix . ($job->getActive() ? 'yes' : 'no') . $activeSuffix;
                break;

            case 'cmd':
                $data = $job->getCommand();
                break;

            case 'com':
                $data = $job->getComment();
                break;

            case 'cre':
                $data = ($job->getCreatedAt() !== null ? $job->getCreatedAt()
                    ->format($dateFormat) : '-');
                break;

            case 'dur':
                $data = ($job->getDuration() !== null ? $job->getDuration()
                    ->format('%H:%I:%S') : $unknownStatement);
                break;

            case 'end':
                $data = ($job->getEndedAt() !== null ? $job->getEndedAt()
                    ->format($dateFormat) : $unknownStatement);
                break;

            case 'exp':
                $data = $job->getExpression();
                break;

            case 'id':
                $data = $job->getId();
                break;

            case 'nr':
                $data = $number + 1;
                break;

            case 'srt':
                $data = ($job->getStartedAt() !== null ? $job->getStartedAt()
                    ->format($dateFormat) : $unknownStatement);
                break;

            case 'sta':
                if ($job->getStatus() == Job::STATUS_IN_PROGRESS) {
                    $statusPrefix = '<comment>';
                    $statusSuffix = '</comment>';
                } elseif ($job->getStatus() == Job::STATUS_DONE) {
                    $statusPrefix = '<info>';
                    $statusSuffix = '</info>';
                } else {
                    $statusPrefix = '<question>';
                    $statusSuffix = '</question>';
                }
                $data = $statusPrefix . Job::STATUSES[$job->getStatus()] . $statusSuffix;
                break;

            case 'typ':
                $data = Job::TYPES[$job->getType()];
                break;

            case 'upd':
                $data = ($job->getUpdatedAt() !== null ? $job->getUpdatedAt()
                    ->format($dateFormat) : '-');
                break;
        }

        return $data;
    }
}
