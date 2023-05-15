<?php
declare(strict_types=1);

namespace Elenyum\ApiDocBundle\Util;

use ArrayIterator;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Exception;
use Traversable;

class Paginator
{
    public const DELIMITER = '___';
    public const PAGE_SIZE = 10;
    private DoctrineQueryBuilder $queryBuilder;
    private int $currentPage;
    private int $pageSize;
    private ArrayIterator $results;
    private int $numResults;

    /**
     * Paginator constructor.
     * @param DoctrineQueryBuilder $queryBuilder
     */
    public function __construct(DoctrineQueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public static function transformArray($array): array
    {
        $newArray = array();
        foreach ($array as $key => $value) {
            $parts = explode(self::DELIMITER, $key);
            $subArray =& $newArray;
            foreach ($parts as $part) {
                if (!isset($subArray[$part])) {
                    $subArray[$part] = array();
                }
                $subArray =& $subArray[$part];
            }
            $subArray = $value;
        }

        return $newArray;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return $this
     * @throws Exception
     */
    public function paginate(int $offset = 0, int $limit = self::PAGE_SIZE): self
    {
        $this->currentPage = max(1, $offset / $limit);
        $this->pageSize = $limit;

        $query = $this->queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        if (0 === \count($this->queryBuilder->getDQLPart('join'))) {
            $query->setHint(CountWalker::HINT_DISTINCT, false);
        }

        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $paginator = new DoctrinePaginator($query, true);

        $useOutputWalkers = \count($this->queryBuilder->getDQLPart('having') ?: []) > 0;
        $paginator->setUseOutputWalkers($useOutputWalkers);

        $result = [];
        foreach ($paginator->getIterator() as $item) {
            $result[] = self::transformArray($item);
        }
        $this->results = new ArrayIterator($result);
        $this->numResults = $paginator->count();

        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @return int
     */
    public function getLastPage(): int
    {
        // Отсчёт от нулевого элемента
        return (int)ceil($this->numResults / $this->pageSize) - 1;
    }

    /**
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * @return bool
     */
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * @return int
     */
    public function getPreviousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    /**
     * @return bool
     */
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->getLastPage();
    }

    /**
     * @return int
     */
    public function getNextPage(): int
    {
        return min($this->getLastPage(), $this->currentPage + 1);
    }

    /**
     * @return bool
     */
    public function hasToPaginate(): bool
    {
        return $this->numResults > $this->pageSize;
    }

    /**
     * @return int
     */
    public function getNumResults(): int
    {
        return $this->numResults;
    }

    /**
     * @return Traversable
     */
    public function getResults(): Traversable
    {
        return $this->results;
    }
}
