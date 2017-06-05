<?php
/**
 * User provider.
 */
namespace Provider;

use Doctrine\DBAL\Connection;
use Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * Class UserProvider.
 *
 * @package Provider
 */
class UserProvider implements UserProviderInterface
{
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
     * Load user by username.
     *
     * @param string $login User login
     *
     * @return User Result
     */
    public function loadUserByUsername($login)
    {
        $userRepository = new UserRepository($this->db);
        $user = $userRepository->loadUserByLogin($login);

        return new User(
            $user['login'],
            $user['password'],
            $user['roles'],
            true,
            true,
            true,
            true
        );
    }

    /**
     * Refresh user.
     *
     * @param UserInterface $user User
     *
     * @return User Result
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    get_class($user)
                )
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * Check if supports selected class.
     *
     * @param string $class Class name
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}