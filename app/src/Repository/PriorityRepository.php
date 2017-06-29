<?php

/**
 * Priority Repository.
 *
 * @package Repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Utils\Paginator;

/**
 * Class PriorityRepository.
 *
 * @package Repository
 */
class PriorityRepository
{
    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * PriorityRepository constructor.
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
     * @param array $priority Type
     *
     * @return boolean Result
     */
    public function save($priority)
    {
        if (isset($priority['id']) && ctype_digit((string)$priority['id'])) {
            // update record
            $id = $priority['id'];
            unset($priority['id']);
            return $this->db->update('pr_priorities', $priority, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('pr_priorities', $priority);
        }
    }

    /**
     * Remove record.
     *
     * @param array $priority Type
     *
     * @return boolean Result
     */
    public function delete($priority)
    {
        return $this->db->delete('pr_priorities', ['id' => $priority['id']]);
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
            'p.name'
        )->from('pr_priorities', 'p');
    }

}