<?php

namespace Elenyum\ApiDocBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Elenyum\ApiDocBundle\Annotation\Access;
use Elenyum\ApiDocBundle\Annotation\NotEditable;
use Elenyum\ApiDocBundle\Entity\BaseEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use ReflectionClass;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

class EditorService
{
    public function __construct(
        public Registry $registry
    ) {
    }

    /**
     * @return array
     */
    public function getModules(): array
    {
        $managerNames = $this->registry->getManagerNames();
        $classes = array();
        foreach ($managerNames as $name => $connection) {
            $metas = $this->registry->getManager($name)->getMetadataFactory()->getAllMetadata();
            foreach ($metas as $key => $meta) {
                $reflectionClass = $meta->getReflectionClass();
                if (!empty($reflectionClass->getAttributes(NotEditable::class))) {
                    continue;
                }

                $classes[$name]['version'] = explode('\\', $reflectionClass->getName())[2] ?? 'V1';
                $classes[$name]['name'] = $name;
                $classes[$name]['entity'][$key]['class'] = $reflectionClass->getShortName();
                //Тут класс передаём дважды в случае если имя класса изменилось то сможем понять какой класс изменился
                $classes[$name]['entity'][$key]['oldClassName'] = $reflectionClass->getShortName();

                $access = $reflectionClass->getAttributes(Access::class);
                if (!empty($access) && !empty($access[0])) {
                    /** @var Access $accessClass */
                    $accessClass = $access[0]->newInstance();

                    $classes[$name]['entity'][$key]['roles'] = $accessClass->getRoles();
                } else {
                    $classes[$name]['entity'][$key]['roles'] = [
                        mb_strtoupper(Access::GET) => [],
                        mb_strtoupper(Access::POST) => [],
                        mb_strtoupper(Access::PUT) => [],
                        mb_strtoupper(Access::DELETE) => [],
                    ];
                }

                foreach ($reflectionClass->getProperties() as $propertyKey => $property) {

                    $column = [
                        'type' => $property->getType()?->getName(),
                        'nullable' => $property->getType()?->allowsNull(),
                    ];
                    /** @var \ReflectionAttribute $column */
                    $columnAttribute = $property->getAttributes(Column::class)[0] ?? null;
                    /** @var \ReflectionAttribute $manyToOne */
                    $manyToOne = $property->getAttributes(ManyToOne::class)[0] ?? null;
                    /** @var \ReflectionAttribute $oneToOne */
                    $oneToOne = $property->getAttributes(OneToOne::class)[0] ?? null;
                    /** @var \ReflectionAttribute $oneToMany */
                    $oneToMany = $property->getAttributes(OneToMany::class)[0] ?? null;
                    /** @var \ReflectionAttribute $manyToMany */
                    $manyToMany = $property->getAttributes(ManyToMany::class)[0] ?? null;
                    /** @var \ReflectionAttribute $group */
                    $group = $property->getAttributes(Groups::class)[0] ?? null;

                    $toMapping = [];
                    if (!empty($manyToOne)) {
                        /** @todo нужно пройтись по каждому типу данных связей и корректно собирать параметры и передавать их */
                        $column['targetEntity'] = $column['type'];
                        $column['type'] = 'ManyToOne';
                    }
                    if (!empty($oneToOne)) {
                        /** @todo нужно пройтись по каждому типу данных связей и корректно собирать параметры и передавать их */
                        $column['targetEntity'] = $column['type'];
                        $column['type'] = 'OneToOne';
                    }
                    if (!empty($oneToMany)) {
                        /** @todo нужно пройтись по каждому типу данных связей и корректно собирать параметры и передавать их */
                        $column['targetEntity'] = $column['type'];
                        $column['type'] = 'OneToMany';
                    }
                    if (!empty($manyToMany)) {
                        /** @todo нужно пройтись по каждому типу данных связей и корректно собирать параметры и передавать их */
                        $column['targetEntity'] = $column['type'];
                        $column['type'] = 'ManyToMany';
                    }

                    if (!empty($columnAttribute) && !empty(
                        $columnAttribute->getArguments()
                        ) && isset($columnAttribute->getArguments()['type'])) {
                        $column = [
                            'type' => $columnAttribute->getArguments()['type'],
                            'nullable' => $columnAttribute->getArguments()['nullable'] ?? false,
                        ];
                    }

                    $validator = [];
                    $getAttributesValidator = array_filter(
                        [
                            /**
                             * @todo нужно получать короткое имя для валидатора
                             */
                            $property->getAttributes(Length::class)[0] ?? null,
                            $property->getAttributes(NotNull::class)[0] ?? null,
                            $property->getAttributes(Regex::class)[0] ?? null,
                            $property->getAttributes(Count::class)[0] ?? null,
                            $property->getAttributes(NotBlank::class)[0] ?? null,
                            $property->getAttributes(Email::class)[0] ?? null,
                        ]
                    );

                    /** @var \ReflectionAttribute $item */
                    foreach ($getAttributesValidator as $item) {
                        $objectValidator = $item->newInstance();
                        $nameClass = lcfirst(
                            str_replace('Symfony\Component\Validator\Constraints\\', '', get_class($objectValidator))
                        );
                        $validator[$nameClass] = $item->getArguments();
                    }

                    $classes[$name]['entity'][$key]['properties'][$propertyKey] = [
                        'name' => $property->getName(),
                        'column' => $column,
                        'group' => $group?->getArguments()[0],
                        'validator' => $validator,
                    ];

                    if (!empty($toMapping)) {
                        $classes[$name]['entity'][$key]['properties'][$propertyKey]['toMapping'] = $toMapping;
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * @return array
     */
    public function getTypes(): array
    {
        $manyToOne = new ReflectionClass(ManyToOne::class);
        $oneToOne = new ReflectionClass(OneToOne::class);
        $oneToMany = new ReflectionClass(OneToMany::class);
        $manyToMany = new ReflectionClass(ManyToMany::class);

        $types = [
            '\\'.$manyToOne->getName() => $manyToOne->getShortName(),
            '\\'.$oneToOne->getName() => $oneToOne->getShortName(),
            '\\'.$oneToMany->getName() => $oneToMany->getShortName(),
            '\\'.$manyToMany->getName() => $manyToMany->getShortName(),
        ];

        $oClass = new ReflectionClass(\Doctrine\DBAL\Types\Types::class);
        $simpleTypes = $oClass->getConstants();

        foreach ($simpleTypes as $simpleTypeKey => $simpleTypeValue) {
            $simpleTypes['\\'.\Doctrine\DBAL\Types\Types::class.'::'.$simpleTypeKey] = $simpleTypeValue;

            unset($simpleTypes[$simpleTypeKey]);
        }

        return array_merge($types, $simpleTypes);
    }

    public function getGroups(): array
    {
        return [
            BaseEntity::TYPE_GET,
            BaseEntity::TYPE_LIST,
            BaseEntity::TYPE_PUT_RES,
            BaseEntity::TYPE_PUT_REQ,
            BaseEntity::TYPE_POST_RES,
            BaseEntity::TYPE_POST_REQ,
            BaseEntity::TYPE_DEL_RES,
        ];
    }
}