<?php

namespace Elenyum\ApiDocBundle\Validator;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class Valid implements ValidInterface
{
    /**
     * @var array
     */
    private array $messages = [];

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function isValid(object $entity): bool
    {
        $errors = $this->validator->validate($entity);
        if ($errors->count() > 0) {
            for ($x = 0; $x < $errors->count(); $x++) {
                $error = $errors->get($x);
                $this->messages[$error->getPropertyPath()][] = $error->getMessage();
            }

            return false;
        }

        return true;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}