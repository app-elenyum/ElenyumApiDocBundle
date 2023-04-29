<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Describer;

use Elenyum\ApiDocBundle\Model\ModelRegistry;

interface ModelRegistryAwareInterface
{
    public function setModelRegistry(ModelRegistry $modelRegistry);
}
