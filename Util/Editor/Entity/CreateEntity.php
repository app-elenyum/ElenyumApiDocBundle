<?php


use Elenyum\ApiDocBundle\Annotation\Access;
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

    public function propertyTypeToTypePhp(string $type): ?string
    {
        return self::TYPES[$type];
    }

    public function getValidatorByName(string $name): string
    {
        return str_replace('Symfony\Component\Validator\Constraints', 'Assert', self::VALIDATORS[$name]);
    }

    public function addProperty(ClassType $classType, array $propertyData): Property
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
                break;
            case 'ManyToMany':
                $prop = [];

                if (!empty($property['column']['mappedBy'])) {
                    $prop['mappedBy'] = $property['column']['mappedBy'];
                }
                if (!empty($property['column']['targetEntity'])) {
                    $prop['targetEntity'] = $this->namespace->getName().'\\'.$property['column']['targetEntity'];
                }
                $property->addAttribute('ORM\ManyToMany', $prop);
                $this->namespace->addUse('Doctrine\Common\Collections\Collection');
                $property->setType('Collection');
                break;
            case 'ManyToOne':
                $prop = [];
                if (!empty($property['column']['targetEntity'])) {
                    $prop['targetEntity'] = $this->namespace->getName().'\\'.$property['column']['targetEntity'];
                }
                $property->addAttribute('ORM\ManyToOne', $prop);
                $this->namespace->addUse($this->namespace->getName().'\\'.$property['column']['targetEntity']);
                $property->setType($property['column']['targetEntity']);
                break;
            case 'OneToOne':
                $prop = [];

                if (!empty($property['column']['mappedBy'])) {
                    $prop['mappedBy'] = $property['column']['mappedBy'];
                }
                if (!empty($property['column']['targetEntity'])) {
                    $prop['targetEntity'] = $this->namespace->getName().'\\'.$property['column']['targetEntity'];
                }

                $property->addAttribute('ORM\OneToOne', $prop);
                $this->namespace->addUse($this->namespace->getName().'\\'.$property['column']['targetEntity']);
                $property->setType($property['column']['targetEntity']);
                break;
            case 'OneToMany':
                $prop = [];
                $this->namespace->addUse('Doctrine\Common\Collections\Collection');

                if (!empty($property['column']['mappedBy'])) {
                    $prop['mappedBy'] = $property['column']['mappedBy'];
                }
                if (!empty($property['column']['targetEntity'])) {
                    $prop['targetEntity'] = $this->namespace->getName().'\\'.$property['column']['targetEntity'];
                }
                $this->namespace->addUse('Doctrine\Common\Collections\Collection');
                $property->setType('Collection');
                $property->addAttribute('ORM\OneToMany', $prop);
                break;
            default:
                /** string text  integer  float  boolean  guid  array  object */
                $property->addAttribute('ORM\Column', $propertyData['column']);
                $property->setType($this->propertyTypeToTypePhp($columnType));
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

    /**
     * @throws Exception
     */
    private function createEntityClass(array $entity, string $moduleNamespace): array
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
            $addProperty = $this->addProperty($class, $property);
            $this->createSetter($class, $addProperty);
            $this->createGetter($class, $addProperty);
            $groups = array_merge($property['group'], $groups);
        }

        if (!empty($this->creator[$entity['class']]['controllers'])) {
            $this->creator[$entity['class']]['service'] = $entity['class'].'Service';
            $this->creator[$entity['class']]['repository'] = $entity['class'].'Repository';

        }

        return $groups;
    }
}