<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Render\Json;

use Elenyum\ApiDocBundle\Render\OpenApiRenderer;
use Elenyum\ApiDocBundle\Render\RenderOpenApi;
use OpenApi\Annotations\OpenApi;

/**
 * @internal
 */
class JsonOpenApiRenderer implements OpenApiRenderer
{
    public function getFormat(): string
    {
        return RenderOpenApi::JSON;
    }

    public function render(OpenApi $spec, array $options = []): string
    {
        $options += [
            'no-pretty' => false,
        ];
        $flags = $options['no-pretty'] ? 0 : JSON_PRETTY_PRINT;

        return json_encode($spec, $flags);
    }
}
