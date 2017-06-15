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
     * @param $sortOrder
     * @param $sortBy
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
     * @return array|mixed Result
     * @internal param string $id Project id
     */

    public function findAllFromProject($projectId, $userId)
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
            ->setParameter(':id', $projectId, \PDO::PARAM_INT)
            ->andWhere('b.user_id = :userId')
            ->setParameter(':userId', $userId, \PDO::PARAM_INT);
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
     * @return array Result
     */

    public function findAllPaginatedFromProject($projectId, $userId, $page = 1)
    {
        $countQueryBuilder = $this->findAllFromProject($projectId, $userId)
            ->select('COUNT(DISTINCT b.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->findAllFromProject($projectId, $userId), $countQueryBuilder);
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
            if ($sortBy != 'name') $sortBy .= '_id';
            $queryBuilder->addOrderBy('b.'.$sortBy, $sortOrder);
//
//            TODO: Fix or remove
//            $queryBuilder->addOrderBy('b.:sortBy, :sortOrder')
//                ->setParameter(':sortBy', $sortBy, \PDO::PARAM_STR)
//                ->setParameter(':sortOrder', $sortOrder, \PDO::PARAM_STR);
//            dump($queryBuilder);
        }

        return $queryBuilder;
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

        dump($projectId);

        return $queryBuilder->execute()->fetchAll();
    }
}