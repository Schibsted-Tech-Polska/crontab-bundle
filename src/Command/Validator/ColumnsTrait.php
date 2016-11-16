<?php

namespace Stp\CrontabBundle\Command\Validator;

use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Command validator
 */
trait ColumnsTrait
{
    /** @var array @README: should be const, but it's not allowed in traits */
    public static $columnTypes = [
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
                if (!in_array($column, array_keys(self::$columnTypes))) {
                    throw new RuntimeException('The "columns" option can have only any of those values: ' .
                        implode(', ', array_keys(self::$columnTypes)));
                }
            }
        }

        return $columns;
    }
}
