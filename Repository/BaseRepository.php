<?php

namespace Elenyum\ApiDocBundle\Repository;

use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Elenyum\ApiDocBundle\Entity\BaseEntity;
use Elenyum\ApiDocBundle\Util\Paginator;
use Elenyum\ApiDocBundle\Util\PrepareQueryParams;
use Elenyum\ApiDocBundle\Util\RestParamsInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Exception;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\Serializer\Annotation\Groups;

abstract class BaseRepository
    extends ServiceEntityRepository
    implements PaginatorInterface, GetItemInterface, GetItemForPutInterface, GetItemForDeleteInterface
{
    const ALIAS = 'entity';

    private ?QueryBuilder $qb = null;

    /**
     * @throws \ReflectionException
     */
    protected function addParams(RestParamsInterface $params): void
    {
        new PrepareQueryParams($this->getQueryBuilder(), self::ALIAS, $this->getEntityName(), $params);
    }

    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    public function getPropertyByGroup(string $name): array
    {
        $reflectionClass = new ReflectionClass($this->getEntityName());
        $result = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $group = $property->getAttributes(Groups::class);

            $attributes = array_filter([
                current($property->getAttributes(ManyToMany::class)),
                current($property->getAttributes(OneToMany::class)),
                current($property->getAttributes(ManyToOne::class)),
                current($property->getAttributes(OneToOne::class)),
            ], function ($item) {
                return !empty($item);
            });

            if (empty($attributes) &&
                end($group) !== false &&
                in_array($name, current(end($group)?->getArguments()))
            ) {
                $result[] = $property->getName();
            }

            if (!empty($attributes)) {
                /** @var ReflectionAttribute $attribute */
                $attribute = current($attributes);

                /** @var BaseRepository $repository */
                $repository = $this->getQueryBuilder()->getEntityManager()->getRepository(
                    $attribute->getArguments()['targetEntity']
                );
                foreach ($repository->getPropertyByGroup($name) as $item) {
                    $result[] = $property->getName().'.'.$item;
                }
            }
        }
        if (empty($result)) {
            throw new Exception('Not found group for "'.$name.'" by entity: '.$this->getEntityName());
        }

        return $result;
    }

    /**
     * @param RestParamsInterface $params
     * @return Paginator
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function getPaginator(
        RestParamsInterface $params
    ): Paginator {
        $qb = $this->getQueryBuilder();
        $propertyByGroup = $this->getPropertyByGroup('LIST');

        if (!empty($params->getField())) {
            $field = array_intersect($params->getField(), $propertyByGroup);
            if (empty($field)) {
                throw new Exception(
                    'Not found property "'.
                    implode(',', $params->getField()).
                    '" by entity: '.$this->getEntityName()
                );
            }
            $params->setField($field);
        } else {
            $params->setField($propertyByGroup);
        }
        $this->addParams($params);

        return (new Paginator($qb))->paginate($params->getOffset(), $params->getLimit());
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function getItem(int $id): ?BaseEntity
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function getItemsForDelete(array $ids): ?array
    {
        return $this->findBy(['id' => $ids]);
    }

    public function getQueryBuilder(): QueryBuilder
    {
        if ($this->qb === null) {
            $this->qb = $this->createQueryBuilder(self::ALIAS);
        }

        return $this->qb;
    }

    public function getItemForPut(int $id): ?object
    {
        return $this->findOneBy(['id' => $id]);
    }
}