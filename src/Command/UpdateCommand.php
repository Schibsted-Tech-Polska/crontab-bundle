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
class UpdateCommand extends BaseCommand
{
    /** @var string */
    protected $id;

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

        $this->setName('crontab:update')
            ->setDescription('Crontab job update')
            ->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'Id (12 chars long)')
            ->addOption('expression', 'x', InputOption::VALUE_REQUIRED, 'Expression (in MM HH DD MM WW format)')
            ->addOption('command', 'c', InputOption::VALUE_REQUIRED, 'Command (accepted by bash)')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Type (' . $this->getArrayAsString(Job::TYPES) . ')')
            ->addOption('active', 'a', InputOption::VALUE_REQUIRED, 'Active (no=not active, yes=active)')
            ->addOption('comment', 'o', InputOption::VALUE_REQUIRED, 'Comment (any text)')
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
        $this->expression = $this->getValidatedExpression($input->getOption('expression'));
        $this->command = $this->getValidatedCommand($input->getOption('command'));
        $this->type = $this->getValidatedType($input->getOption('type'));
        $this->active = $this->getValidatedActive($input->getOption('active'));
        $this->comment = $this->getValidatedComment($input->getOption('comment'));

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
        $now = new DateTime();
        $updated = false;
        $msg = '';

        try {
            /** @var JobManagerInterface $jobManager */
            $jobManager = $this->container->get('stp_crontab.manager.job');

            $job = $jobManager->getJob($this->id);
            if ($this->expression !== null) {
                $job->setExpression($this->expression);
                $updated = true;
            }
            if ($this->command !== null) {
                $job->setCommand($this->command);
                $updated = true;
            }
            if ($this->type !== null) {
                $job->setType($this->type);
                $updated = true;
            }
            if ($this->active !== null) {
                $job->setActive($this->active);
                $updated = true;
            }
            if ($this->comment !== null) {
                $job->setComment($this->comment);
                $updated = true;
            }

            if ($updated) {
                $job->setUpdatedAt($now);

                $res = $jobManager->setJob($this->id, $job);
            } else {
                $res = 1;
            }
        } catch (Exception $e) {
            $res = 0;
            $msg = ' ' . $e->getMessage();
        }

        if ($res) {
            if ($updated) {
                $this->io->success('Job was updated.');
            } else {
                $this->io->note('Job wasn\'t updated.');
            }
        } else {
            $this->io->error('An error occurred during updating job.' . $msg);
        }

        return intval(!$res);
    }
}
