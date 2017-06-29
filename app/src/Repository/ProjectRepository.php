<?php
/**
 * Project repository.
 *
 * @package Repository
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
    const NUM_ITEMS = 5;

    /**
     * Number of items per page for admin view.
     *
     * const int NUM_ITEMS
     */
    const NUM_ITEMS_ADMIN = 10;

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
     * @param int $page
     * @param null $userId
     *
     * @return array
     */
    public function findAllPaginated($page = 1, $userId = null)
    {
        $countQueryBuilder = $this->queryAll($userId)
            ->select('COUNT(DISTINCT p.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll($userId), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        if ($userId) {
            $paginator->setMaxPerPage(self::NUM_ITEMS);
        } else {
            $paginator->setMaxPerPage(self::NUM_ITEMS_ADMIN);
        }

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
     * Find one record.
     * @param $id
     * @param $isAdmin
     * @return array|mixed Result
     * @internal param string $id Element id
     */
    public function findOptionsForUser($id, $isAdmin = null)
    {

        $queryBuilder = $this->db->createQueryBuilder();

        $queryBuilder->select(
            'p.id',
            'p.name',
            'p.description',
            'p.start_date',
            'p.end_date',
            'p.user_id'
        )->from('pr_projects', 'p');

        if (!$isAdmin) {
            $queryBuilder->where('p.user_id = :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();

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
        if ($project['start_date'] == 0000 - 00 - 00) $project['start_date'] = null;
        if ($project['end_date'] == 0000 - 00 - 00) $project['end_date'] = null;

        if (isset($project['id']) && ctype_digit((string)$project['id'])) {
            // update record
            $id = $project['id'];
            unset($project['id']);
            unset($project['user_name']);

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
     * @param null $id User id
     * @return \Doctrine\DBAL\Query\QueryBuilder Result
     */
    protected function queryAll($id = null)
    {
        $queryBuilder = $this->db->createQueryBuilder();

        $queryBuilder->select(
            'p.id',
            'p.name',
            'p.description',
            'p.start_date',
            'p.end_date',
            'p.user_id',
            'u.login AS user_name'
        )->from('pr_projects', 'p')
            ->join('p', 'pr_users', 'u', 'p.user_id = u.id');

        if ($id) {
            $queryBuilder->where('p.user_id = :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder;
    }


    /**
     * Find for uniqueness.
     *
     * @param string $name Element name
     * @param int|string|null $id Element id
     *
     * @param null $userId
     * @return array Result
     */
    public function findForUniqueness($name, $id = null, $userId)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('LOWER(p.name) = :name')
            ->andWhere('p.user_id = :userId')
            ->setParameters(
                array(
                    ':name' => strtolower($name),
                    ':userId' => $userId,
                ),
                array(
                    \PDO::PARAM_STR,
                    \PDO::PARAM_INT)
            );

        if ($id) {
            $queryBuilder->andWhere('p.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Projects created by a user
     *
     * @param $userId
     * @return
     * @internal param null $type
     */

    public function countProjects($userId)
    {
        $queryBuilder = $this->db->createQueryBuilder();

        $queryBuilder->select(
            'COUNT(p.id) AS projects'
        )->from('pr_projects', 'p')
            ->where('p.user_id = :userId')
            ->setParameter(':userId', $userId, \PDO::PARAM_INT);;

        $result = $queryBuilder->execute()->fetch();

        return $result['projects'];
    }
}