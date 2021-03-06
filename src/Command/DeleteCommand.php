<?php

namespace Stp\CrontabBundle\Command;

use Crontab\Manager\JobManagerInterface;
use Exception;
use Stp\CrontabBundle\Command\Validator\IdTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command
 */
class DeleteCommand extends BaseCommand
{
    use IdTrait;

    /** @var string */
    protected $id;

    /**
     * Configure
     */
    protected function configure()
    {
        $this->setName('crontab:delete')
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
            $this->io->error('An error occurred during deleting job. ' . $e->getMessage());

            return 1;
        }

        if ($res) {
            $this->io->success('Job was deleted.');
        } else {
            $this->io->note('Job wasn\'t deleted.');
        }

        return 0;
    }
}
