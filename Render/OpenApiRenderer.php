<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Render;

use OpenApi\Annotations\OpenApi;

/**
 * @internal
 */
interface OpenApiRenderer
{
    public function getFormat(): string;

    public function render(OpenApi $spec, array $options = []): string;
}
