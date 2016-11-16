<?php

namespace Stp\CrontabBundle\Command\Validator;

use Crontab\Model\Job;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Command validator
 */
trait TypeTrait
{
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
}
