<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\ModelDescriber;

use Elenyum\ApiDocBundle\Model\Model;
use OpenApi\Annotations\Schema;

interface ModelDescriberInterface
{
    public function describe(Model $model, Schema $schema);

    public function supports(Model $model): bool;
}
