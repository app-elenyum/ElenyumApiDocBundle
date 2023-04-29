<?php

namespace Elenyum\ApiDocBundle\Util;

use Doctrine\ORM\QueryBuilder;
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
     * @throws \ReflectionException
     */
    public function select(array $fields = []): void
    {
        if (empty($fields)) {
            return;
        }

        $select = [];
        foreach ($fields as $item) {
            /** ReflectionException if not define fields */
            new ReflectionProperty($this->entityName, $item);

            $select[] = $this->alias.'.'.$item;
            $this->qb->select($select);
        }
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