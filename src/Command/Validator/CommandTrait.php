<?php

namespace Stp\CrontabBundle\Command\Validator;

/**
 * Command validator
 */
trait CommandTrait
{
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
}
