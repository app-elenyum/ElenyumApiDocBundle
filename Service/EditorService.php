<?php

namespace Elenyum\ApiDocBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Elenyum\ApiDocBundle\Entity\BaseEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use ReflectionClass;

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

                $classes[$name]['version'] = explode('\\', $reflectionClass->getName())[2] ?? 'V1';
                $classes[$name]['name'] = $name;
                $classes[$name]['entity'][$key]['class'] = $reflectionClass->getShortName();
                //Тут класс передаём дважды в случае если имя класса изменилось то сможем понять какой класс изменился
                $classes[$name]['entity'][$key]['oldClassName'] = $reflectionClass->getShortName();

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

                    if (!empty($columnAttribute) && !empty($columnAttribute->getArguments()) && isset($columnAttribute->getArguments()['type'])) {
                        $column = [
                            'type' => $columnAttribute->getArguments()['type'],
                            'nullable' => $columnAttribute->getArguments()['nullable'] ?? false,
                        ];
                    }

                    $classes[$name]['entity'][$key]['properties'][$propertyKey] = [
                        'name' => $property->getName(),
                        'column' => $column,
                        'group' => is_array($group?->getArguments()[0]) ? implode(
                            ', ',
                            $group?->getArguments()[0]
                        ) : $group?->getArguments()[0],
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