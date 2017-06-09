<?php
/**
 * Base controller.
 */

namespace Controller;

use Repository\UserRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;


/**
 * Class BaseController.
 *
 * @package Controller
 */

abstract class BaseController implements ControllerProviderInterface {

    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     * @internal param int $page Current page number
     *
     */
    public function getUserId(Application $app)
    {
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            $username = $user->getUsername();

            $userRepository = new UserRepository($app['db']);
            $userData = $userRepository->getUserByLogin($username);
            return $userData['id'];
        }
    }
}