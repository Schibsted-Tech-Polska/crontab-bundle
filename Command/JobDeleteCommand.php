<?php

namespace Stp\CrontabBundle\Command;

use Crontab\Manager\JobManagerInterface;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command
 */
class JobDeleteCommand extends BaseCommand
{
    /** @var string */
    protected $id;

    /**
     * Configure
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('crontab:job:delete')
            ->setDescription('Crontab job delete')
            ->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'Id (12 chars long)')
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

        $this->id = $this->getValidatedId($input->getOption('id'));

        $this->requireField('id', $this->id);
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
        try {
            /** @var JobManagerInterface $jobManager */
            $jobManager = $this->container->get('stp_crontab.manager.job');

            $res = $jobManager->removeJob($this->id);
        } catch (Exception $e) {
            $res = 0;
        }

        if ($res) {
            $this->io->success('Job was deleted');
        } else {
            $this->io->error('An error occurred during deleting job');
        }

        return intval(!$res);
    }
}
