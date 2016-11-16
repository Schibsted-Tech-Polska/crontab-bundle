<?php

namespace Stp\CrontabBundle\Command\Validator;

/**
 * Command validator
 */
trait ActiveTrait
{
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
}
