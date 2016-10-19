<?php

namespace Stp\CrontabBundle\Command;

use Cron\CronExpression;
use Crontab\Model\Job;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Command
 */
abstract class BaseCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var SymfonyStyle */
    protected $io;

    /**
     * Initialize
     *
     * @param InputInterface  $input  input
     * @param OutputInterface $output output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * Require field
     *
     * @param string $fieldName  field name
     * @param mixed  $fieldValue field value
     *
     * @throws RuntimeException
     */
    protected function requireField($fieldName, $fieldValue)
    {
        if (empty($fieldValue)) {
            throw new RuntimeException('The "' . $fieldName . '" option cannot be empty');
        }
    }

    /**
     * Get validated id
     *
     * @param string $id id
     *
     * @return string
     */
    protected function getValidatedId($id)
    {
        if ($id !== null) {
            $id = strval($id);
        }

        return $id;
    }

    /**
     * Get validated expression
     *
     * @param string $expression expression
     *
     * @return string
     */
    protected function getValidatedExpression($expression)
    {
        if ($expression !== null) {
            if (!CronExpression::isValidExpression($expression)) {
                throw new RuntimeException('The "expression" option has an invalid crontab format');
            }
        }

        return $expression;
    }

    /**
     * Get validated command
     *
     * @param string $command command
     *
     * @return string
     */
    protected function getValidatedCommand($command)
    {
        if ($command !== null) {
            $command = strval($command);
        }

        return $command;
    }

    /**
     * Get validated type
     *
     * @param int $type type
     *
     * @return int
     *
     * @throws RuntimeException
     */
    protected function getValidatedType($type)
    {
        if ($type !== null) {
            if (!in_array($type, array_keys(Job::TYPES))) {
                throw new RuntimeException('The "type" option can have only one of those values: ' .
                    implode(', ', array_keys(Job::TYPES)));
            }
        }

        return $type;
    }

    /**
     * Get validated active
     *
     * @param bool $active active
     *
     * @return bool
     */
    protected function getValidatedActive($active)
    {
        if ($active !== null) {
            $active = boolval($active);
        }

        return $active;
    }

    /**
     * Get validated comment
     *
     * @param string $comment comment
     *
     * @return string
     */
    protected function getValidatedComment($comment)
    {
        if ($comment !== null) {
            $comment = strval($comment);
        }

        return $comment;
    }

    /**
     * Get types as string
     *
     * @return string
     */
    protected function getTypesAsString()
    {
        $types = [];

        foreach (Job::TYPES as $key => $value) {
            $types[] = $key . '=' . $value;
        }
        $string = implode(', ', $types);

        return $string;
    }
}
