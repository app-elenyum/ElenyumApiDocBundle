<?php

/*
 * This file is part of the ElenyumApiDocBundle package.
 *
 * (c) Elenyum
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Elenyum\ApiDocBundle\Tests\ModelDescriber\Annotations;

use Doctrine\Common\Annotations\AnnotationReader;
use Elenyum\ApiDocBundle\Model\ModelRegistry;
use Elenyum\ApiDocBundle\ModelDescriber\Annotations\OpenApiAnnotationsReader;
use OpenApi\Annotations as OA;
use OpenApi\Attributes as OAattr;
use OpenApi\Generator;
use PHPUnit\Framework\TestCase;

class AnnotationReaderTest extends TestCase
{
    /**
     * @param object $entity
     * @dataProvider provideProperty
     */
    public function testProperty($entity)
    {
        $schema = new OA\Schema([]);
        $schema->merge([new OA\Property(['property' => 'property1'])]);
        $schema->merge([new OA\Property(['property' => 'property2'])]);

        $registry = new ModelRegistry([], new OA\OpenApi([]), []);
        $symfonyConstraintAnnotationReader = new OpenApiAnnotationsReader(new AnnotationReader(), $registry, ['json']);
        $symfonyConstraintAnnotationReader->updateProperty(new \ReflectionProperty($entity, 'property1'), $schema->properties[0]);
        $symfonyConstraintAnnotationReader->updateProperty(new \ReflectionProperty($entity, 'property2'), $schema->properties[1]);

        $this->assertEquals($schema->properties[0]->example, 1);
        $this->assertEquals($schema->properties[0]->description, Generator::UNDEFINED);

        $this->assertEquals($schema->properties[1]->example, 'some example');
        $this->assertEquals($schema->properties[1]->description, 'some description');
    }

    public function provideProperty(): iterable
    {
        yield 'Annotations' => [new class() {
            /**
             * @OA\Property(example=1)
             */
            private $property1;
            /**
             * @OA\Property(example="some example", description="some description")
             */
            private $property2;
        }];

        if (\PHP_VERSION_ID >= 80100) {
            yield 'Attributes' => [new class() {
                #[OAattr\Property(example: 1)]
                private $property1;
                #[OAattr\Property(example: 'some example', description: 'some description')]
                private $property2;
            }];
        }
    }
}
