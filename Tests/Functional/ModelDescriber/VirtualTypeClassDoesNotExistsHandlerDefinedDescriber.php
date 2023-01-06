<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests\Functional\ModelDescriber;

use Elenyum\ApiDocBundle\Model\Model;
use Elenyum\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use Elenyum\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations as OA;
use Symfony\Component\PropertyInfo\Type;

class VirtualTypeClassDoesNotExistsHandlerDefinedDescriber implements ModelDescriberInterface
{
    public function describe(Model $model, OA\Schema $schema)
    {
        $schema->type = 'object';
        $property = Util::getProperty($schema, 'custom_prop');
        $property->type = 'string';
    }

    public function supports(Model $model): bool
    {
        return Type::BUILTIN_TYPE_OBJECT === $model->getType()->getBuiltinType()
            && 'VirtualTypeClassDoesNotExistsHandlerDefined' === $model->getType()->getClassName();
    }
}
