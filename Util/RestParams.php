<?php

namespace Elenyum\ApiDocBundle\Util;

class RestParams implements RestParamsInterface
{
    public function __construct(
        private int $offset = 0,
        private int $limit = 10,
        private array $filter = [],
        private array $sort = [],
        private array $field = []
    ) {
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return array
     */
    public function getFilter(): array
    {
        return $this->filter;
    }

    /**
     * @return array
     */
    public function getSort(): array
    {
        return $this->sort;
    }

    /**
     * @return array
     */
    public function getField(): array
    {
        return $this->field;
    }

    /**
     * @param array $field
     * @return RestParams
     */
    public function setField(array $field): RestParams
    {
        $this->field = $field;

        return $this;
    }
}