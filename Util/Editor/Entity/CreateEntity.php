<?php

namespace Elenyum\ApiDocBundle\Util\Editor\Entity;

use DateTimeImmutable;
use Elenyum\ApiDocBundle\Annotation\Access;
use Exception;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

class CreateEntity
{
    private ?PhpNamespace $namespace = null;
    private array $groups = [];

    const TYPES = [
        'id' => 'id',
        'string' => 'string',
        'guid' => 'string',
        'text' => 'string',
        'integer' => 'int',
        'float' => 'float',
        'boolean' => 'bool',
        'date' => \DateTimeImmutable::class,
        'time' => \DateTimeImmutable::class,
        'datetime' => \DateTimeImmutable::class,
        'object' => 'array',
        'array' => 'array',
    ];

    const VALIDATORS = [
        'notNull' => NotNull::class,
        'length' => Length::class,
        'regex' => Regex::class,
        'count' => Count::class,
    ];

    private function propertyTypeToTypePhp(string $type): ?string
    {
        return self::TYPES[$type];
    }

    private function getValidatorByName(string $name): string
    {
        return str_replace('Symfony\Component\Validator\Constraints', 'Assert', self::VALIDATORS[$name]);
    }

    /**
     * @return PhpNamespace|null
     */
    public function getNamespace(): ?PhpNamespace
    {
        return $this->namespace;
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    private function addProperty(ClassType $classType, array $propertyData): Property
    {
        $property = $classType->addProperty($propertyData['name']);
        $columnType = $propertyData['column']['type'];

        if (isset($propertyData['validator']) && !empty($propertyData['validator'])) {
            foreach ($propertyData['validator'] as $key => $validation) {
                $validatorType = $this->getValidatorByName($key);
                $property->addAttribute($validatorType, $validation);
            }
        }

        if (!empty($propertyData['group'])) {
            $property->addAttribute('Groups', [$propertyData['group']]);
        }

        switch ($columnType) {
            case 'id':
                $property->addAttribute('ORM\Id');
                $property->addAttribute('ORM\GeneratedValue');
                $property->addAttribute('ORM\Column', ['type' => 'integer']);
                $property->setType('int');

                $this->createSetter($classType, $property);
                $this->createGetter($classType, $property);
                break;
            case 'ManyToMany':
                $prop = [];

                if (!empty($propertyData['column']['mappedBy'])) {
                    $prop['mappedBy'] = $propertyData['column']['mappedBy'];
                }
                if (!empty($propertyData['column']['targetEntity'])) {
                    $prop['targetEntity'] = $this->namespace->getName().'\\'.$propertyData['column']['targetEntity'];
                }
                $property->addAttribute('ORM\ManyToMany', $prop);
                $this->namespace->addUse('Doctrine\Common\Collections\Collection');
                $property->setType('Collection');

                $this->createSetter($classType, $property);
                $this->createGetter($classType, $property);
                break;
            case 'ManyToOne':
                $prop = [];
                if (!empty($propertyData['column']['targetEntity'])) {
                    $prop['targetEntity'] = $this->namespace->getName().'\\'.$propertyData['column']['targetEntity'];
                }
                $property->addAttribute('ORM\ManyToOne', $prop);
                $this->namespace->addUse($this->namespace->getName().'\\'.$propertyData['column']['targetEntity']);
                $property->setType($propertyData['column']['targetEntity']);

                $this->createSetter($classType, $property);
                $this->createGetter($classType, $property);
                break;
            case 'OneToOne':
                $prop = [];

                if (!empty($propertyData['column']['mappedBy'])) {
                    $prop['mappedBy'] = $propertyData['column']['mappedBy'];
                }
                if (!empty($propertyData['column']['targetEntity'])) {
                    $prop['targetEntity'] = $this->namespace->getName().'\\'.$propertyData['column']['targetEntity'];
                }

                $property->addAttribute('ORM\OneToOne', $prop);
                $this->namespace->addUse($this->namespace->getName().'\\'.$propertyData['column']['targetEntity']);
                $property->setType($propertyData['column']['targetEntity']);

                $this->createSetter($classType, $property);
                $this->createGetter($classType, $property);
                break;
            case 'OneToMany':
                $prop = [];
                $this->namespace->addUse('Doctrine\Common\Collections\Collection');

                if (!empty($propertyData['column']['mappedBy'])) {
                    $prop['mappedBy'] = $propertyData['column']['mappedBy'];
                }
                if (!empty($propertyData['column']['targetEntity'])) {
                    $prop['targetEntity'] = $this->namespace->getName().'\\'.$propertyData['column']['targetEntity'];
                }
                $this->namespace->addUse('Doctrine\Common\Collections\Collection');
                $property->setType('Collection');
                $property->addAttribute('ORM\OneToMany', $prop);

                $this->createSetter($classType, $property);
                $this->createGetter($classType, $property);
                break;
            case 'date':
            case 'time':
            case 'datetime':
                $this->namespace->addUse('DateTimeImmutable');
                $property->addAttribute('ORM\Column', $propertyData['column']);
                $property->setType($this->propertyTypeToTypePhp($columnType));

                $this->createSetterDate($classType, $property);
                $this->createGetterDate($classType, $property);
                break;
            default:
                /** string text  integer  float  boolean  guid  array  object */
                $property->addAttribute('ORM\Column', $propertyData['column']);
                $property->setType($this->propertyTypeToTypePhp($columnType));

                $this->createSetter($classType, $property);
                $this->createGetter($classType, $property);
        }

        return $property;
    }

    private function createSetter(ClassType $class, Property $property): void
    {
        $setter = $class->addMethod('set'.ucfirst($property->getName()));
        $setter->addParameter($property->getName())->setType($property->getType());
        $setter->addBody(
            '$this->'.$property->getName().' = $'.$property->getName().';'.
            PHP_EOL.
            PHP_EOL.
            'return $this;'
        );
        $setter->setReturnType('self');
    }

    private function createGetter(ClassType $class, Property $property): void
    {
        $getter = $class->addMethod('get'.ucfirst($property->getName()));
        $getter->addBody('return $this->'.$property->getName().';');
        $getter->setReturnType($property->getType());
    }

    private function createSetterDate(ClassType $class, Property $property): void
    {
        $setter = $class->addMethod('set'.ucfirst($property->getName()));
        $setter->addParameter($property->getName())->setType('string');

        $setter->addBody(
            '$this->'.$property->getName().' = new DateTimeImmutable($'.$property->getName().');'.
            PHP_EOL.
            PHP_EOL.
            'return $this;'
        );
        $setter->setReturnType('self');
    }

    private function createGetterDate(ClassType $class, Property $property): void
    {
        $getter = $class->addMethod('get'.ucfirst($property->getName()));
        $getter->addBody('return $this->'.$property->getName().'->format(DATE_ATOM);');
        $getter->setReturnType('string');
    }

    /**
     * @throws Exception
     */
    public function createEntityClass(array $entity, string $moduleNamespace): CreateEntity
    {
        $this->namespace = new PhpNamespace($moduleNamespace.'\\Entity');
        $this->namespace->addUse('Elenyum\ApiDocBundle\Entity\BaseEntity');
        $this->namespace->addUse('Doctrine\ORM\Mapping', 'ORM');
        $this->namespace->addUse('Symfony\Component\Serializer\Annotation\Groups');
        $this->namespace->addUse('Symfony\Component\Validator\Constraints', 'Assert');
        $this->namespace->addUse('Elenyum\ApiDocBundle\Annotation\Access');

        $class = $this->namespace->addClass($entity['class']);
        $class->setExtends('BaseEntity');
        $class->addAttribute('ORM\Table', ['name' => mb_strtolower($entity['class'])]);

        if (empty($entity['roles'])) {
            throw new Exception('Undefined roles');
        }

        $get = $entity['roles'][mb_strtoupper(Access::GET)] ?? [];
        $post = $entity['roles'][mb_strtoupper(Access::POST)] ?? [];
        $put = $entity['roles'][mb_strtoupper(Access::PUT)] ?? [];
        $delete = $entity['roles'][mb_strtoupper(Access::DELETE)] ?? [];

        $class->addAttribute('Access', [$get, $post, $put, $delete]);
        $class->addAttribute(
            'ORM\Entity',
            ['repositoryClass' => $moduleNamespace.'\Repository\\'.$entity['class'].'Repository']
        );

        $groups = [];
        foreach ($entity['properties'] as $property) {
            $this->addProperty($class, $property);
            $groups = array_merge($property['group'], $groups);
        }
        $this->groups = $groups;

        return $this;
    }
}