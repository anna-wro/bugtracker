<?php
/**
 * Unique Tag validator.
 */
namespace Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class UniqueProjectValidator.
 *
 * @package Validator\Constraints
 */
class UniqueProjectValidator extends ConstraintValidator
{
    /**
     * Validate method
     *
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint->repository) {
            return;
        }

        $result = $constraint->repository->findForUniqueness(
            $value,
            $constraint->elementId,
            $constraint->userId
        );

        if ($result && count($result)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ project }}', $value)
                ->addViolation();
        }
    }
}