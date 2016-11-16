<?php

namespace Stp\CrontabBundle\Command\Validator;

/**
 * Command validator
 */
trait IdTrait
{
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
}
