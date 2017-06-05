<?php
/**
 * Bug repository.
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Silex\Application;
use Utils\Paginator;

/**
 * Class BugRepository.
 *
 * @package Repository
 */
class BugRepository
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
     * BugRepository constructor.
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
            ->setParameter(':id', $id);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Get project's name
     *
     * @param string $id Project id
     *
     * @return array|mixed Result
     */
    public function getLinkedProject($id)
    {
        $query = $this->findOneById($id);
        $projectId = $query['project_id'];

        $queryBuilder = $this->db->createQueryBuilder()
            ->select('pr.name')
            ->from('pr_projects', 'pr')
            ->where('pr.id = :id')
            ->setParameter(':id', $projectId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();
        return !$result ? [] : $result;
    }

    /**
     * Get bug's status
     *
     * @param string $id Bug id
     *
     * @return array|mixed Result
     */
    public function getLinkedStatus(Application $app, $id)
    {
        $query = $this->findOneById($id);
        $projectId = $query['status_id'];
        $statusRepository = new StatusRepository($app['db']);
        return $statusRepository->findOneById($projectId);
    }

    /**
     * Get bug's priority
     *
     * @param Application $app
     * @param string $id Bug id
     * @return array|mixed Result
     */
    public function getLinkedPriority(Application $app, $id)
    {
        $query = $this->findOneById($id);
        $projectId = $query['priority_id'];
        $priorityRepository = new PriorityRepository($app['db']);
        return $priorityRepository->findOneById($projectId);
    }

    /**
     * Get bug's type
     *
     * @param string $id Bug id
     *
     * @return array|mixed Result
     */
    public function getLinkedType($id)
    {
        $query = $this->findOneById($id);
        $projectId = $query['type_id'];

        $queryBuilder = $this->db->createQueryBuilder()
            ->select('pr.name')
            ->from('pr_types', 'pr')
            ->where('pr.id = :id')
            ->setParameter(':id', $projectId, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetchAll();
        return !$result ? [] : $result[0];
    }

    /**
     * Find all from the project
     *
     * @param $projectId
     * @return array|mixed Result
     * @internal param string $id Project id
     *
     */
    public function findAllFromProject($projectId)
    {
        $queryBuilder = $this->db->createQueryBuilder();

            return $queryBuilder->select(
                'b.id',
                'b.name',
                'b.description',
                'b.expected_result',
                'b.reproduction',
                'b.start_date',
                'b.end_date',
                'b.type_id',
                'b.priority_id',
                'b.status_id',
                'b.project_id',
                'b.user_id'
            )->from('pr_bugs', 'b')
                ->where('b.project_id = :id')
                ->setParameter(':id', $projectId, \PDO::PARAM_INT);
    }

    /**
     * Get records paginated.
     *
     * @param int $page Current page number
     *
     * @return array Result
     */
    public function findAllPaginatedFromProject($id, $page = 1)
    {
        $countQueryBuilder = $this->findAllFromProject($id)
            ->select('COUNT(DISTINCT b.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->findAllFromProject($id), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
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
            'b.name',
            'b.description',
            'b.expected_result',
            'b.reproduction',
            'b.start_date',
            'b.end_date',
            'b.type_id',
            'b.priority_id',
            'b.status_id',
            'b.project_id',
            'b.user_id'
        )->from('pr_bugs', 'b');
    }

    /**
     * Save record.
     *
     * @param array $bug Bug
     * @return bool Result
     * @throws DBALException
     */

    public function save($bug)
    {

        // TODO: implement all of these
        $bug['user_id'] = 1;

        try {
            if (isset($bug['id']) && ctype_digit((string)$bug['id'])) {
                // update record
                $id = $bug['id'];
                unset($bug['id']);

                return $this->db->update('pr_bugs', $bug, ['id' => $bug]);
            } else {
                // add new record
                return $this->db->insert('pr_bugs', $bug);
            }
        } catch (DBALException $e) {
            throw $e;
        }

    }

    /**
     * Remove record.
     *
     * @param array $bug Bug
     *
     * @return boolean Result
     */
    public function delete($bug)
    {
        return $this->db->delete('pr_bugs', ['id' => $bug['id']]);
    }
}