<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\PropertyDescriber;

use Elenyum\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Elenyum\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Elenyum\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations as OA;
use OpenApi\Generator;

class CompoundPropertyDescriber implements PropertyDescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;

    /** @var PropertyDescriberInterface[] */
    private $propertyDescribers;

    public function __construct(iterable $propertyDescribers)
    {
        $this->propertyDescribers = $propertyDescribers;
    }

    public function describe(array $types, OA\Schema $property, array $groups = null)
    {
        $property->oneOf = Generator::UNDEFINED !== $property->oneOf ? $property->oneOf : [];

        foreach ($types as $type) {
            $property->oneOf[] = $schema = Util::createChild($property, OA\Schema::class, []);
            foreach ($this->propertyDescribers as $propertyDescriber) {
                if ($propertyDescriber instanceof ModelRegistryAwareInterface) {
                    $propertyDescriber->setModelRegistry($this->modelRegistry);
                }
                if ($propertyDescriber->supports([$type])) {
                    $propertyDescriber->describe([$type], $schema, $groups);

                    break;
                }
            }
        }
    }

    public function supports(array $types): bool
    {
        return count($types) >= 2;
    }
}
