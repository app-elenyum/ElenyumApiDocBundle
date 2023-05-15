<?php

namespace Elenyum\ApiDocBundle\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Elenyum\ApiDocBundle\Entity\BaseEntity;
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
            if (!is_subclass_of($property->getType()->getName(), BaseEntity::class) &&
                end($group) !== false &&
                in_array($name, current(end($group)?->getArguments()))
            ) {
                $result[] = $property->getName();
            }
            if (class_exists($property->getType()->getName()) && is_subclass_of($property->getType()->getName(),
                    BaseEntity::class
                )) {

                /** @var BaseRepository $repository */
                $repository = $this->getQueryBuilder()->getEntityManager()->getRepository($property->getType()->getName());
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
     * @throws NonUniqueResultException
     * @throws \ReflectionException
     */
    public function getItemsWithFields(int $id, array $fields = []): array
    {
        $qb = $this->getQueryBuilder();
        $select = [];
        $fields = !empty($fields) ? $fields : $this->getPropertyByGroup('GET');
        if (!empty($fields)) {
            foreach ($fields as $field) {
                // Разбиваем строку на части по символу "."
                $parts = explode('.', $field);

                if (count($parts) > 1) {
                    $split = $parts;
                    foreach (array_slice($split, 0, -1) as $keyElement => $element) {
                        if (!in_array($element, $this->qb->getAllAliases())) {
                            $parent = $keyElement === 0 ? self::ALIAS : $parts[$keyElement - 1];
                            $this->qb->leftJoin($parent.'.'.$element, $element);
                        }
                    }
                    $splitForLastTwoElement = $parts;
                    $lastTwoElements = array_splice($splitForLastTwoElement, -2);
                    $implodeKey = implode('.', $lastTwoElements);
                    $select[] = sprintf('%1$s as %2$s', $implodeKey, implode(Paginator::DELIMITER, $parts));
                } else {
                    //  "." нет, это поле из таблицы пользователей
                    $select[] = self::ALIAS.'.'.$field;
                }
            }
        } else {
            $select[] = self::ALIAS;
        }

        $qb->select($select);
        $qb->andWhere(self::ALIAS.'.id = :id')
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @throws \ReflectionException
     * @throws NonUniqueResultException
     */
    public function getItem(int $id, array $fields = []): ?array
    {
        $itemsWithFields = $this->getItemsWithFields($id, $fields);
        if (!empty($itemsWithFields)) {
            return $itemsWithFields;
        }

        return null;
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