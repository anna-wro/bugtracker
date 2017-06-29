<?php

/**
 * Status Repository
 *
 * @package Repository
 */

namespace Repository;
use Doctrine\DBAL\Connection;

/**
 * Class StatusRepository.
 *
 * @package Repository
 */
class StatusRepository
{
    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * StatusRepository constructor.
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
        $queryBuilder->where('s.id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();
        return !$result ? [] : $result;
    }

    /**
     * Save record.
     *
     * @param array $status Type
     *
     * @return boolean Result
     */
    public function save($status)
    {
        if (isset($status['id']) && ctype_digit((string)$status['id'])) {
            // update record
            $id = $status['id'];
            unset($status['id']);
            return $this->db->update('pr_statuses', $status, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('pr_statuses', $status);
        }
    }

    /**
     * Remove record.
     *
     * @param array $status Type
     *
     * @return boolean Result
     */
    public function delete($status)
    {
        return $this->db->delete('pr_priorities', ['id' => $status['id']]);
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
            's.id',
            's.name'
        )->from('pr_statuses', 's');
    }

}