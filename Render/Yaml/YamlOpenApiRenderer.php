<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Render\Yaml;

use Elenyum\ApiDocBundle\Render\OpenApiRenderer;
use Elenyum\ApiDocBundle\Render\RenderOpenApi;
use OpenApi\Annotations\OpenApi;

/**
 * @internal
 */
class YamlOpenApiRenderer implements OpenApiRenderer
{
    public function getFormat(): string
    {
        return RenderOpenApi::YAML;
    }

    public function render(OpenApi $spec, array $options = []): string
    {
        return $spec->toYaml();
    }
}
