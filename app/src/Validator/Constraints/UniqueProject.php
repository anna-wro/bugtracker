<?php
/**
 * Unique Project constraint.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueProject.
 *
 * @package Validator\Constraints
 */
class UniqueProject extends Constraint
{
    /**
     * Message.
     *
     * @var string $message
     */
    public $message = 'message.duplicate_project';

    /**
     * Element id.
     *
     * @var int|string|null $elementId
     */
    public $elementId = null;

    /**
     * Project repository.
     *
     * @var null|\Repository\ProjectRepository $repository
     */
    public $repository = null;

    /**
     * User id.
     *
     * @var int|string|null $userId
     */
    public $userId = null;
}