<?php

namespace Elenyum\ApiDocBundle\Repository;

use Elenyum\ApiDocBundle\Util\Paginator;
use Elenyum\ApiDocBundle\Util\PrepareQueryParams;
use Elenyum\ApiDocBundle\Util\RestParamsInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Exception;
use ReflectionClass;
use Symfony\Component\Serializer\Annotation\Groups;

abstract class BaseRepository
    extends ServiceEntityRepository
    implements PaginatorInterface, GetItemInterface, GetItemForPutInterface, GetItemForDeleteInterface
{
    /**
     * @throws \ReflectionException
     */
    protected function addParams(QueryBuilder $qb, string $alias, RestParamsInterface $params): void
    {
        new PrepareQueryParams($qb, $alias, $this->getEntityName(), $params);
    }

    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    protected function getPropertyByGroup(string $name): array
    {
        $reflectionClass = new ReflectionClass($this->getEntityName());
        $result = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $group = $property->getAttributes(Groups::class);
            if (end($group) !== false && in_array($name, current(end($group)?->getArguments()))) {
                $result[] = $property->getName();
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
        define("ALIAS", 'entity');

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
        $qb = $this->createQueryBuilder(ALIAS);
        $this->addParams($qb, ALIAS, $params);

        return (new Paginator($qb))->paginate($params->getOffset(), $params->getLimit());
    }

    public function getItem(int $id): ?object
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function getItemsForDelete(array $ids): ?array
    {
        return $this->findBy(['id' => $ids]);
    }

    public function getItemForPut(int $id): ?object
    {
        return $this->findOneBy(['id' => $id]);
    }
}