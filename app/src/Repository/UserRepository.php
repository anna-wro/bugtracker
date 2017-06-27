<?php
/**
 * User repository
 */

namespace Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Utils\Paginator;

/**
 * Class UserRepository.
 *
 * @package Repository
 */
class UserRepository
{
    /**
     * User roles
     *
     * const int ROLE_ADMIN
     * const int ROLE_USER
     */

    CONST ROLE_ADMIN = 1;
    CONST ROLE_USER = 2;

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
     * TagRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Loads user by login.
     *
     * @param string $login User login
     * @throws UsernameNotFoundException
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function loadUserByLogin($login)
    {
        try {
            $user = $this->getUserByLogin($login);

            if (!$user || !count($user)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            $roles = $this->getUserRoles($user['id']);

            if (!$roles || !count($roles)) {
                throw new UsernameNotFoundException(
                    sprintf('Username "%s" does not exist.', $login)
                );
            }

            return [
                'login' => $user['login'],
                'password' => $user['password'],
                'roles' => $roles,
            ];
        } catch (DBALException $exception) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        } catch (UsernameNotFoundException $exception) {
            throw $exception;
        }
    }

    /**
     * Gets user data by login.
     *
     * @param string $login User login
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserByLogin($login)
    {
        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('u.id', 'u.login', 'u.password')
                ->from('pr_users', 'u')
                ->where('u.login = :login')
                ->setParameter(':login', $login, \PDO::PARAM_STR);

            return $queryBuilder->execute()->fetch();
        } catch (DBALException $exception) {
            return [];
        }
    }

    /**
     * Get records paginated.
     *
     * @param int $page Current page number
     * @return array Result
     * @internal param $userId
     */
    public function findAllPaginated($page = 1)
    {
        $countQueryBuilder = $this->queryAll()
            ->select('COUNT(DISTINCT u.id) AS total_results')
            ->setMaxResults(1);

        $paginator = new Paginator($this->queryAll(), $countQueryBuilder);
        $paginator->setCurrentPage($page);
        $paginator->setMaxPerPage(self::NUM_ITEMS);

        return $paginator->getCurrentPageResults();
    }

    /**
     * Gets user by User ID.
     *
     * @param $id string UserId
     * @return array Result
     * @internal param int $userId User ID
     */

    public function findOneById($id)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('u.id = :id')
            ->setParameter(':id', $id);
        $result = $queryBuilder->execute()->fetch();

        return !$result ? [] : $result;
    }

    /**
     * Gets user roles by User ID.
     *
     * @param integer $userId User ID
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array Result
     */
    public function getUserRoles($userId)
    {
        $roles = [];

        try {
            $queryBuilder = $this->db->createQueryBuilder();
            $queryBuilder->select('r.name')
                ->from('pr_users', 'u')
                ->innerJoin('u', 'pr_roles', 'r', 'u.role_id = r.id')
                ->where('u.id = :id')
                ->setParameter(':id', $userId, \PDO::PARAM_INT);
            $result = $queryBuilder->execute()->fetchAll();

            if ($result) {
                $roles = array_column($result, 'name');
            }

            return $roles;
        } catch (DBALException $exception) {
            return $roles;
        }
    }


    /**
     * @param \Silex\Application $app Silex application
     * @param $data array Data to save
     * @return int
     */
    public function save($app, $data)
    {
        unset($data['role_name']);

        if (isset($data['password'])) {
            $data['password'] = $app['security.encoder.bcrypt']->encodePassword($data['password'], '');

        }

        if (isset($data['id']) && ctype_digit((string)$data['id'])) {
            // update record
            $id = $data['id'];
            unset($data['id']);

            return $this->db->update('pr_users', $data, ['id' => $id]);
        } else {
            // add new record
            $data['role_id'] = self::ROLE_USER;
            return $this->db->insert('pr_users', $data);
        }
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function queryAll()
    {
        $queryBuilder = $this->db->createQueryBuilder();

        return $queryBuilder->select('u.id', 'u.login', 'u.role_id', 'r.name AS role_name')
            ->from('pr_users', 'u')
            ->join('u', 'pr_roles', 'r', 'u.role_id = r.id');
    }

    /**
     * @param $login
     * @param null $id
     * @return array
     */
    public function findForUniqueness($login, $id = null)
    {
        $queryBuilder = $this->queryAll();
        $queryBuilder->where('u.login = :login')
            ->orWhere('u.login = :loginUpper')
            ->orWhere('u.login = :loginLower')
            ->setParameters(
                array(
                    ':login' => $login,
                    ':loginUpper' => strtoupper($login),
                    ':loginLower' => strtolower($login),
                ),
                array(
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR,
                    \PDO::PARAM_STR)
            );
        if ($id) {
            $queryBuilder->andWhere('u.id <> :id')
                ->setParameter(':id', $id, \PDO::PARAM_INT);
        }
        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Remove account.
     *
     * @param array $account Account
     *
     * @return boolean Result
     */
    public function delete($account)
    {
        return $this->db->delete('pr_users', ['id' => $account['id']]);
    }
}
