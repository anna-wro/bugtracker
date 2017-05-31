<?php
/**
 * Bookmark repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class BookmarkRepository.
 *
 * @package Repository
 */
class BookmarkRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 3;

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * TagRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Fetch all records.
     *
     * @return array Result
     */
    public function findAll()
    {
        $queryBuilder = $this->queryAll();

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Get records paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginated($page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT b.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    /**
     * Find one record.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('b.id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Save record.
     *
     * @param array $bookmark Bookmark
     *
     * @return boolean Result
     */
    public function save($bookmark)
    {
        $currentDateTime = new \DateTime();
        $bookmark['modified_at'] = $currentDateTime->format('Y-m-d H:i:s');
        if (isset($bookmark['id']) && ctype_digit((string) $bookmark['id'])) {
            // update record
            $id = $bookmark['id'];
            unset($bookmark['id']);

            return $this->db->update('si_bookmarks', $bookmark, ['id' => $id]);
        } else {
            // add new record
            $bookmark['created_at'] = $currentDateTime->format('Y-m-d H:i:s');

            return $this->db->insert('si_bookmarks', $bookmark);
        }
    }

    /**
     * Remove record.
     *
     * @param array $bookmark Bookmark
     *
     * @return boolean Result
     */
    public function delete($bookmark)
    {
        return $this->db->delete('si_bookmarks', ['id' => $bookmark['id']]);
    }

    /**
     * Query all records.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select(
            'b.id',
            'b.created_at',
            'b.modified_at',
            'b.title',
            'b.url',
            'b.is_public'
        )->from('si_bookmarks', 'b');
    }
}