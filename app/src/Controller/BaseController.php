<?php
/**
 * Base controller.
 */

namespace Controller;

use Repository\BugRepository;
use Repository\PriorityRepository;
use Repository\ProjectRepository;
use Repository\StatusRepository;
use Repository\TypeRepository;
use Repository\UserRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Form\BugType;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


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