<?php
/**
 * Unique Bug constraint.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueBug.
 *
 * @package Validator\Constraints
 */
class UniqueBug extends Constraint
{
    /**
     * Message.
     *
     * @var string $message
     */
    public $message = 'message.duplicate_bug';

    /**
     * Element id.
     *
     * @var int|string|null $elementId
     */
    public $elementId = null;

    /**
     * Bug repository.
     *
     * @var null|\Repository\BugRepository $repository
     */
    public $repository = null;
}