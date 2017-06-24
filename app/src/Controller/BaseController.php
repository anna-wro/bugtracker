<?php
/**
 * Base controller.
 */

namespace Controller;

use Repository\UserRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Security\Core\User\User;


/**
 * Class BaseController.
 *
 * @package Controller
 */
abstract class BaseController implements ControllerProviderInterface
{

    /**
     * Get user ID
     *
     * @param \Silex\Application $app Silex application
     * @return String $userData['id'] User ID
     */

    public function getUserId(Application $app)
    {
        $token = $app['security.token_storage']->getToken();

        if (null !== $token) {
            $user = $token->getUser();

            if ($user instanceof User) {
                $username = $user->getUsername();
            } elseif ($user !== null && $user !== 'anon.') {
                $username = $user;
            }
            $userRepository = new UserRepository($app['db']);
            $userData = $userRepository->getUserByLogin($username);
            return $userData['id'];
        }
    }

    public function checkOrderOptions($sortOrder = null, $sortBy = null)
    {
        $sortOptions = array('id', 'name', 'type', 'priority', 'status');
        if (!($sortOrder == 'asc' || $sortOrder == 'desc')) {
            $sortOrder = 'asc';
        }
        if (!in_array($sortBy, $sortOptions)) {
            $sortOrder = null;
            $sortBy = null;
        }
        return array($sortOrder, $sortBy);
    }
}