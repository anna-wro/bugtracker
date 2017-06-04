<?php
/**
 * Bookmark repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class ProjectRepository.
 *
 * @package Repository
 */
class ProjectRepository
{
    /**
     * Number of items per page.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS = 10;

    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * ProjectRepository constructor.
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
            ->select('COUNT(DISTINCT p.id) AS total_results')
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
        $queryBuilder->where('p.id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Save record.
     *
     * @param array $project Project
     *
     * @return boolean Result
     */
    public function save($project)
    {
        // FIXME: Getting real user id instead of this one
        $project['user_id'] = 1;

        if($project['start_date'] == 0000-00-00) $project['start_date'] = null;
        if($project['end_date'] == 0000-00-00) $project['end_date'] = null;
        if($project['end_date'] < $project['start_date']) {

            }

        if (isset($project['id']) && ctype_digit((string) $project['id'])) {
            // update record
            $id = $project['id'];
            unset($project['id']);

            return $this->db->update('pr_projects', $project, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('pr_projects', $project);
        }
    }

    /**
     * Remove record.
     *
     * @param array $project Project
     *
     * @return boolean Result
     */
    public function delete($project)
    {
        return $this->db->delete('pr_projects', ['id' => $project['id']]);
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
            'p.id',
            'p.name',
            'p.description',
            'p.start_date',
            'p.end_date',
            'p.user_id'
        )->from('pr_projects', 'p');
    }

    /**
     * Find for uniqueness.
     *
     * @param string          $name Element name
     * @param int|string|null $id   Element id
     *
     * @return array Result
     */
    public function findForUniqueness($name, $id = null)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('p.name = :name')
            ->setParameter(':name', $name, \PDO::PARAM_STR);
        if ($id) {
            $queryBuilder->andWhere('p.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }
}