<?php

namespace Elenyum\ApiDocBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Elenyum\ApiDocBundle\Entity\BaseEntity;
use Elenyum\ApiDocBundle\Repository\BaseRepository;
use ReflectionProperty;

class PrepareQueryParams
{
    /**
     * @throws \ReflectionException
     */
    public function __construct(
        private readonly QueryBuilder $qb,
        private readonly string $alias,
        private readonly string $entityName,
        RestParamsInterface $restParams
    ) {
        $this->addSort($restParams->getSort());
        $this->select($restParams->getField());
        $this->addWhere($restParams->getFilter());
    }

    /**
     * @throws \ReflectionException
     */
    public function addSort(array $sort = []): void
    {
        if (empty($sort)) {
            return;
        }

        foreach ($sort as $item) {
            if (preg_match('#^-\w+#', $item)) {
                $order = 'ASC';
            } else {
                $order = 'DESC';
            }
            $item = trim($item, '-+');
            new ReflectionProperty($this->entityName, $item);

            $this->qb->orderBy("$this->alias.$item", $order);
        }
    }

    /**
     * @param array $fields
     * @return void
     */
    public function select(array $fields = []): void
    {
        /** $fields - тут должны приходить все поля через точку которые нужно запрашивать */
        if (empty($fields)) {
            return;
        }

        $select = [];
        $select[] =  $this->alias.'.id as orderId';
        foreach ($fields as $item) {
            $strToNestedArray = explode('.', $item);
            // Если текущий ключ пустой значит нет вложенных
            if (count($strToNestedArray) === 1) {
                $select[] = $this->alias.'.'.$item;

            } else {
                $split = $strToNestedArray;
                $withoudEndKey = array_slice($split, 0, -1);
                foreach ($withoudEndKey as $keyElement => $element) {
                    if (!in_array($element, $this->qb->getAllAliases())) {
                        $parent = $keyElement === 0 ? $this->alias : $strToNestedArray[$keyElement - 1];
                        $this->qb->leftJoin($parent.'.'.$element, $element);
                    }
                }
                $splitForLastTwoElement = $strToNestedArray;
                $lastTwoElements = array_splice($splitForLastTwoElement, -2);
                $implodeKey = implode('.', $lastTwoElements);

                $orderIdKey = sprintf('%1$s.id as %2$s', current($lastTwoElements), implode(Paginator::DELIMITER,$withoudEndKey) . Paginator::DELIMITER . 'orderId');
                if (empty(array_search($orderIdKey, $select))) {
                    $select[] = $orderIdKey;
                }
                $select[] = sprintf('%1$s as %2$s', $implodeKey, implode(Paginator::DELIMITER, $strToNestedArray));
            }
        }

        $this->qb->select($select);
    }

    /**
     * @throws \ReflectionException
     */
    public function addWhere(array $filter): void
    {
        foreach ($filter as $key => $value) {
            new ReflectionProperty($this->entityName, str_replace(['<', '>', '<=', '>='], '', $key));
            $sign = key($value);
            if (is_array($value[key($value)]) && $sign === '=') {
                $sign = 'in';
            }
            $this->qb->andWhere($this->alias.'.'.$key.' '.$sign.' (:'.$key.')');
            $this->qb->setParameter($key, $value[key($value)]);
        }
    }
}