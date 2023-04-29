<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests\Functional\Controller;

use Elenyum\ApiDocBundle\Annotation\Areas;
use Elenyum\ApiDocBundle\Annotation\Model;
use Elenyum\ApiDocBundle\Annotation\Security;
use Elenyum\ApiDocBundle\Tests\Functional\Entity\Article;
use Elenyum\ApiDocBundle\Tests\Functional\Entity\Article81;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Annotation\Route;

class ApiController81 extends ApiController80
{
    #[OA\Get(responses: [
        new OA\Response(
            response: '200',
            description: 'Success',
            attachables: [
                new Model(type: Article::class, groups: ['light']),
            ],
        ),
    ])]
    #[OA\Parameter(ref: '#/components/parameters/test')]
    #[Route('/article_attributes/{id}', methods: ['GET'])]
    #[OA\Parameter(name: 'Accept-Version', in: 'header', attachables: [new OA\Schema(type: 'string')])]
    public function fetchArticleActionWithAttributes()
    {
    }

    #[Areas(['area', 'area2'])]
    #[Route('/areas_attributes/new', methods: ['GET', 'POST'])]
    public function newAreaActionAttributes()
    {
    }

    #[Route('/security_attributes')]
    #[OA\Response(response: '201', description: '')]
    #[Security(name: 'api_key')]
    #[Security(name: 'basic')]
    #[Security(name: 'oauth2', scopes: ['scope_1'])]
    public function securityActionAttributes()
    {
    }

    #[Route('/security_override_attributes')]
    #[OA\Response(response: '201', description: '')]
    #[Security(name: 'api_key')]
    #[Security(name: null)]
    public function securityOverrideActionAttributes()
    {
    }

    #[Route('/inline_path_parameters')]
    #[OA\Response(response: '200', description: '')]
    public function inlinePathParameters(
        #[OA\PathParameter] string $product_id
    ) {
    }

    #[Route('/enum')]
    #[OA\Response(response: '201', description: '', attachables: [new Model(type: Article81::class)])]
    public function enum()
    {
    }
}
