<?php

namespace Stp\CrontabBundle\Command;

use Crontab\Manager\JobManagerInterface;
use Crontab\Model\Job;
use DateTime;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command
 */
class InsertCommand extends BaseCommand
{
    /** @var string */
    protected $expression;

    /** @var string */
    protected $command;

    /** @var int */
    protected $type;

    /** @var bool */
    protected $active;

    /** @var string */
    protected $comment;

    /**
     * Configure
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('crontab:insert')
            ->setDescription('Crontab job insert')
            ->addOption('expression', 'x', InputOption::VALUE_REQUIRED, 'Expression (in MM HH DD MM WW format)',
                '* * * * *')
            ->addOption('command', 'c', InputOption::VALUE_REQUIRED, 'Command (accepted by bash)')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Type (' . $this->getArrayAsString(Job::TYPES) . ')',
                Job::TYPE_SINGLE)
            ->addOption('active', 'a', InputOption::VALUE_REQUIRED, 'Active (no=not active, yes=active)', 'yes')
            ->addOption('comment', 'o', InputOption::VALUE_REQUIRED, 'Comment (any text)', ' ')
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

        $this->expression = $this->getValidatedExpression($input->getOption('expression'));
        $this->command = $this->getValidatedCommand($input->getOption('command'));
        $this->type = $this->getValidatedType($input->getOption('type'));
        $this->active = $this->getValidatedActive($input->getOption('active'));
        $this->comment = $this->getValidatedComment($input->getOption('comment'));

        $this->requireField('command', $this->command);
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
        $now = new DateTime();

        try {
            /** @var JobManagerInterface $jobManager */
            $jobManager = $this->container->get('stp_crontab.manager.job');

            $job = new Job();
            $job->setExpression($this->expression)
                ->setCommand($this->command)
                ->setType($this->type)
                ->setActive($this->active)
                ->setComment($this->comment)
                ->setCreatedAt($now)
                ->setUpdatedAt($now)
            ;

            $res = $jobManager->addJob($job);
        } catch (Exception $e) {
            $res = 0;
        }

        if ($res) {
            $this->io->success('Job was added');
        } else {
            $this->io->error('An error occurred during adding job');
        }

        return intval(!$res);
    }
}
