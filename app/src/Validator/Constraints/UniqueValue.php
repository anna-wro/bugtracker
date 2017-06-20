<?php
/**
 * Unique Value constraint.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueProject.
 *
 * @package Validator\Constraints
 */
class UniqueValue extends Constraint
{
    /**
     * Message.
     *
     * @var string $message
     */
    public $message = 'message.duplicate_value';

    /**
     * Element id.
     *
     * @var int|string|null $elementId
     */
    public $elementId = null;

    /**
     * Repository.
     *
     * @var null|\Repository\ProjectRepository $repository
     */
    public $repository = null;

}