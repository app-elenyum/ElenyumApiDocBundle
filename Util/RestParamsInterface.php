<?php

namespace Elenyum\ApiDocBundle\Util;

interface RestParamsInterface
{
    public function getOffset(): int;
    public function getLimit(): int;
    public function getFilter(): array;
    public function getField(): array;
    public function getSort(): array;
}