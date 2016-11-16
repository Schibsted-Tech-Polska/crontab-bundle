<?php

namespace Stp\CrontabBundle\Command\Validator;

use Cron\CronExpression;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Command validator
 */
trait ExpressionTrait
{
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
}
