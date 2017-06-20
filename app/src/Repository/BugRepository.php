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
    const NUM_ITEMS = 8;

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
     * @param int $userId User ID
     * @param $sortBy
     * @param $sortOrder
     * @return array Result
     */

    public function findAllPaginated($page = 1, $userId, $sortBy = null, $sortOrder = null)
    {
        $countQueryBuilder = $this->queryAllFromUser($userId)
            ->select('COUNT(DISTINCT b.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAllFromUser($userId, $sortBy, $sortOrder), $countQueryBuilder);
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
     * Find all from the project
     *
     * @param $projectId
     * @param $userId
     * @param $sortBy
     * @param $sortOrder
     * @param null $status
     * @param null $priority
     * @param null $category
     * @return array|mixed Result
     * @internal param string $id Project id
     */

    public function findAllFromProject($projectId, $userId, $sortBy = null, $sortOrder = null, $status = null, $priority = null, $category = null)
    {
        $queryBuilder = $this->db->createQueryBuilder();

        $queryBuilder->select(
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
            ->setParameter(':id', $projectId, \PDO::PARAM_INT)
            ->andWhere('b.user_id = :userId')
            ->setParameter(':userId', $userId, \PDO::PARAM_INT);

        if ($priority) {
            switch ($priority) {
                case 'all':
                    break;
                case 'important':
                    $queryBuilder->andWhere('b.priority_id = 1')->orWhere('b.priority_id = 2');
                    break;
                case 'urgent':
                    $queryBuilder->andWhere('b.priority_id = 1')->orWhere('b.priority_id = 3');
                    break;
            }
        }

        if ($status) {
            switch ($status) {
                case 'all':
                    break;
                case 'open':
                    $queryBuilder->andWhere('b.status_id = 1');
                    break;
                case 'closed':
                    $queryBuilder->andWhere('b.status_id = 2');
                    break;
            }
        }

        if ($category) {
            switch ($category) {
                case 'all':
                    break;
                case 'front-end':
                    $queryBuilder->andWhere('b.type_id = 3')
                    ->orWhere('b.type_id = 4')
                    ->orWhere('b.type_id = 5')
                    ->orWhere('b.type_id = 6');
                    break;
                case 'back-end':
                    $queryBuilder->andWhere('b.type_id = 1')
                        ->orWhere('b.type_id = 2')
                        ->orWhere('b.type_id = 7');
                case 'dyzur':
                    $queryBuilder->andWhere('b.id = 94')
                        ->orWhere('b.id = 104')
                        ->orWhere('b.id = 105')
                        ->orWhere('b.id = 108')
                        ->orWhere('b.id = 111');
                    break;
            }
        }

        if ($sortBy) {
            if ($sortBy != 'name' && $sortBy != 'id') $sortBy .= '_id';
            $queryBuilder->addOrderBy('b.' . $sortBy, $sortOrder);
        } else {
            $queryBuilder->addOrderBy('b.status_id', 'asc');
            $queryBuilder->addOrderBy('b.end_date', 'desc');
            $queryBuilder->addOrderBy('b.priority_id', 'asc');
        }

        return $queryBuilder;

    }

    /**
     * Delete all from the project
     *
     * @param $projectId
     * @return array|mixed Result
     */

    public function deleteAllFromProject($projectId)
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $this->db->delete('pr_bugs', ['project_id' => $projectId]);
    }


    /**
     * Get records paginated.
     *
     * @param int $projectId Project ID
     * @param int $userId User Id
     * @param int $page Current page number
     * @param null $sortBy
     * @param null $sortOrder
     * @param null $status
     * @param null $priority
     * @param null $category
     * @return array Result
     * @internal param null $statusFilter
     */

    public function findAllPaginatedFromProject($projectId, $userId, $page = 1, $sortBy = null, $sortOrder = null, $status = null, $priority = null, $category = null)
    {
        $countQueryBuilder = $this->findAllFromProject($projectId, $userId)
            ->select('COUNT(DISTINCT b.id) AS total_results')
            ->setMaxResults(1);
        $paginator = new Paginator($this->findAllFromProject($projectId, $userId, $sortBy, $sortOrder, $status, $priority, $category), $countQueryBuilder);
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
     * Query all records from chosen user
     *
     * @param $id
     * @param null $sortBy
     * @param null $sortOrder
     * @param null $statusFilter
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAllFromUser($id, $sortBy = null, $sortOrder = null)
    {
        $queryBuilder = $this->db->createQueryBuilder();

        $queryBuilder->select(
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
            ->where('b.user_id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);

        if ($sortBy) {
            if ($sortBy != 'name' && $sortBy != 'id') $sortBy .= '_id';
            $queryBuilder->addOrderBy('b.' . $sortBy, $sortOrder);
        } else {
            $queryBuilder->addOrderBy('b.status_id', 'asc');
            $queryBuilder->addOrderBy('b.end_date', 'desc');
            $queryBuilder->addOrderBy('b.priority_id', 'asc');
        }

        return $queryBuilder;
    }

    /**
     * Bugs to do in a project
     *
     * @param $projectId
     * @param null $type
     * @return
     */

    public function countBugs($userId, $projectId = null, $type = null)
    {
        $queryBuilder = $this->db->createQueryBuilder();

        $queryBuilder->select(
            'COUNT(b.id) AS bugs'
        )->from('pr_bugs', 'b')
            ->where('b.user_id = :userId')
            ->setParameter(':userId', $userId, \PDO::PARAM_INT);;

        if ($projectId) {
            $queryBuilder->andWhere('b.project_id = :id')
                ->setParameter(':id', $projectId, \PDO::PARAM_INT);
        }

        switch ($type) {
            case 'todo':
                $queryBuilder->andWhere('b.status_id = 1');
                break;
            case 'done':
                $queryBuilder->andWhere('b.status_id = 2');
                break;
            default:
                break;
        }

        $result = $queryBuilder->execute()->fetch();

        return $result['bugs'];
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
        if ($bug['status_id'] == 2) {
            $today = new \DateTime();
            $formattedDate = $today->format('Y-m-d');
            $bug['end_date'] = $formattedDate;
        } else {
            $bug['end_date'] = null;
        }

        try {
            if (isset($bug['id']) && ctype_digit((string)$bug['id'])) {
                // update record
                $id = $bug['id'];
                unset($bug['id']);

                return $this->db->update('pr_bugs', $bug, ['id' => $id]);
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

    /**
     * Find for uniqueness.
     *
     * @param string $name Element name
     * @param int|string|null $id Element id
     *
     * @param null $userId
     * @param $projectId
     * @return array Result
     */
    public function findForUniqueness($name, $id = null, $userId, $projectId = null)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('b.name = :name')
            ->orWhere('b.name = :nameUpper')
            ->orWhere('b.name = :nameLower')
            ->andWhere('b.user_id = :userId')
            ->setParameters(
                array(
                    ':name' => $name,
                    ':nameUpper' => strtoupper($name),
                    ':nameLower' => strtolower($name),
                    ':userId' => $userId,
                ),
                array(
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR,
                    \PDO::PARAM_INT)
            );

        if ($id) {
            $queryBuilder->andWhere('b.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        if ($projectId) {
            $queryBuilder->andWhere('b.project_id <> :projectId')
                ->setParameter(':projectId', $projectId, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }
}