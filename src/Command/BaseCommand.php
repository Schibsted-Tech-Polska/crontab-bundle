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

    /** @const array */
    const COLUMNS = [
        'act' => 'Active',
        'cmd' => 'Command',
        'com' => 'Comment',
        'cre' => 'Created at',
        'dur' => 'Duration',
        'end' => 'Ended at',
        'exp' => 'Expression',
        'id' => 'Id',
        'nr' => '#',
        'srt' => 'Started at',
        'sta' => 'Status',
        'typ' => 'Type',
        'upd' => 'Updated at',
    ];

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
     * Get validated columns
     *
     * @param array $columns columns
     *
     * @return array
     *
     * @throws RuntimeException
     */
    protected function getValidatedColumns(array $columns = [])
    {
        if ($columns !== []) {
            foreach ($columns as $column) {
                if (!in_array($column, array_keys(self::COLUMNS))) {
                    throw new RuntimeException('The "columns" option can have only any of those values: ' .
                        implode(', ', array_keys(self::COLUMNS)));
                }
            }
        }

        return $columns;
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
     * Get array as string
     *
     * @param array $items items
     *
     * @return string
     */
    protected function getArrayAsString(array $items)
    {
        $array = [];

        foreach ($items as $key => $value) {
            $array[] = $key . '=' . $value;
        }
        $string = implode(', ', $array);

        return $string;
    }
}
