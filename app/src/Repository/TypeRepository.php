<?php

/**
 * Types Repository.
 *
 * @package Repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;

/**
 * Class TypeRepository.
 *
 * @package Repository
 */
class TypeRepository
{
    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * TypeRepository constructor.
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
        $queryBuilder->where('t.id = :id')
            ->setParameter(':id', $id, \PDO::PARAM_INT);
        $result = $queryBuilder->execute()->fetch();
        return !$result ? [] : $result;
    }

    /**
     * Save record.
     *
     * @param array $type Type
     *
     * @return boolean Result
     */
    public function save($type)
    {
        if (isset($type['id']) && ctype_digit((string)$type['id'])) {
            // update record
            $id = $type['id'];
            unset($type['id']);
            return $this->db->update('pr_types', $type, ['id' => $id]);
        } else {
            // add new record
            return $this->db->insert('pr_types', $type);
        }
    }

    /**
     * Remove record.
     *
     * @param array $type Type
     *
     * @return boolean Result
     */
    public function delete($type)
    {
        return $this->db->delete('pr_types', ['id' => $type['id']]);
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
            't.id',
            't.name'
        )->from('pr_types', 't');
    }

}