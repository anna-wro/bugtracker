<?php
/**
 * DBAL paginator.
 */
namespace Utils;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class Paginator.
 *
 * @package Utils
 */
class Paginator
{
    /**
     * Doctrine DBAL Query Builder for data.
     *
     * @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder
     */
    protected $queryBuilder;

    /**
     * Doctrine DBAL Query builder for number records count.
     *
     * @var \Doctrine\DBAL\Query\QueryBuilder $countQueryBuilder
     */
    protected $countQueryBuilder;

    /**
     * Current page number.
     *
     * @var int $currentPage
     */
    protected $currentPage = 1;

    /**
     * Max result per page.
     *
     * @var int $maxPerPage
     */
    protected $maxPerPage = 1;

    /**
     * Paginator constructor.
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $queryBuilder      Query Builder for data
     * @param \Doctrine\DBAL\Query\QueryBuilder $countQueryBuilder Query builder for number records count
     */
    public function __construct(QueryBuilder $queryBuilder, QueryBuilder $countQueryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        $this->countQueryBuilder = $countQueryBuilder;
    }

    /**
     * Sets current page number.
     *
     * @param int|string $currentPage Current page number
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = (integer) ((ctype_digit((string) $currentPage) && $currentPage > 0) ? $currentPage : 1);
    }

    /**
     * Sets max per page results (limit).
     *
     * @param int|string $maxPerPage Max per page results
     */
    public function setMaxPerPage($maxPerPage)
    {
        $this->maxPerPage = (integer) ((ctype_digit((string) $maxPerPage) && $maxPerPage > 0) ? $maxPerPage : 1);
    }

    /**
     * Gets result for current page.
     *
     * @return array Paginator data
     */
    public function getCurrentPageResults()
    {
        $pagesNumber = $this->countAllPages();

        return [
            'page' => $this->calculateCurrentPageNumber($pagesNumber),
            'max_result' => $this->maxPerPage,
            'pages_number' => $pagesNumber,
            'data' => $this->findData(),
        ];
    }

    /**
     * Gets data.
     *
     * @return array Result.
     */
    protected function findData()
    {
        $this->queryBuilder->setFirstResult(
            ($this->currentPage - 1) * $this->maxPerPage
        );
        $this->queryBuilder->setMaxResults($this->maxPerPage);

        return $this->queryBuilder->execute()->fetchAll();
    }

    /**
     * Counts all pages.
     *
     * @return int Number of pages
     */
    protected function countAllPages()
    {
        $result = $this->countQueryBuilder->execute()->fetch();

        if ($result) {
            $pagesNumber =  ceil($result['total_results'] / $this->maxPerPage);
        } else {
            $pagesNumber = 1;
        }

        return $pagesNumber;
    }

    /**
     * Calculates current page number.
     *
     * @param $pagesNumber Number of pages
     *
     * @return int Current page number
     */
    protected function calculateCurrentPageNumber($pagesNumber)
    {
        return ($this->currentPage < 1 || $this->currentPage > $pagesNumber) ? 1 : $this->currentPage;
    }
}