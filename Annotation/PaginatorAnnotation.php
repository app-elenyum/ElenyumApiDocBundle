<?php

namespace Elenyum\ApiDocBundle\Annotation;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class PaginatorAnnotation
{
    #[Assert\Type(type: Types::INTEGER)]
    public int $first;

    #[Assert\Type(type: Types::INTEGER)]
    public int $next;

    #[Assert\Type(type: Types::INTEGER)]
    public int $previous;

    #[Assert\Type(type: Types::INTEGER)]
    public int $last;

    #[Assert\Type(type: Types::INTEGER)]
    public int $current;
}