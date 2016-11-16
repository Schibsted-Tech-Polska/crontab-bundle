<?php

namespace Stp\CrontabBundle\Command\Validator;

/**
 * Command validator
 */
trait CommentTrait
{
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
}
