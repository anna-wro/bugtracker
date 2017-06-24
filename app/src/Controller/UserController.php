<?php
/**
 * User controller.
 *
 */
namespace Controller;

use Form\LoginType;
use Form\RegisterType;
use Repository\BugRepository;
use Repository\ProjectRepository;
use Repository\UserRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class AuthController
 *
 * @package Controller
 */
class UserController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->match('/register', [$this, 'registerAction'])
            ->method('GET|POST')
            ->bind('user_register');
        $controller->get('/profile', [$this, 'profileAction'])
            ->bind('user_profile');
        return $controller;
    }

    /**
     * Register action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function registerAction(Application $app, Request $request)
    {
        $user = [];

        $form = $app['form.factory']->createBuilder(
            RegisterType::class,
            $user,
            [
                'user_repository' => new UserRepository($app['db']),
            ]
        )->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository = new UserRepository($app['db']);
            $data = $form->getData();
            $userRepository->save($app, $data);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.user_successfully_added',
                ]
            );

            $token = new UsernamePasswordToken(
                $data['login'],
                $data['password'],
                'main',
                array('ROLE_USER')
            );
            $app['security.token_storage']->setToken($token);
            $app['session']->set('main', serialize($token));
            $app['session']->save();

            return $app->redirect($app['url_generator']->generate('project_index'), 301);
        }

        return $app['twig']->render(
            'user/register.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * View action.
     *
     * @param \Silex\Application $app Silex application
     * @param string $id Element Id
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function profileAction(Application $app)
    {
        $userId = $this->getUserId($app);

        $userRepository = new UserRepository($app['db']);
        $projectRepository = new ProjectRepository($app['db']);
        $bugRepository = new BugRepository($app['db']);

        $user = $userRepository->findOneById($userId);

        return $app['twig']->render(
            'user/view.html.twig',
            ['user' => $user,
                'projects' => $projectRepository->findOptionsForUser($userId),
                'bugsDone' => $bugRepository->countBugs($userId, null, 'done'),
                'bugsAll' => $bugRepository->countBugs($userId)
            ]
        );
    }



}