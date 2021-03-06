<?php
/**
 * Project controller.
 */

namespace Controller;

use Repository\BugRepository;
use Repository\PriorityRepository;
use Repository\ProjectRepository;
use Repository\StatusRepository;
use Repository\TypeRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Form\ProjectType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


/**
 * Class ProjectController.
 *
 * @package Controller
 */
class ProjectController extends BaseController
{
    /**
     * Routing settings.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return \Silex\ControllerCollection Result
     */

    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])
            ->bind('project_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('project_index_paginated');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('project_add');
        $controller->get('/{id}/bugs', [$this, 'bugsAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('project_bugs');
        $controller->get('/{id}/bugs/page/{page}', [$this, 'bugsAction'])
            ->value('page', 1)
            ->bind('project_bugs_paginated');
        $controller->get('/{id}/bugs/{sortBy}/{sortOrder}', [$this, 'bugsAction'])
            ->assert('id', '[1-9]\d*')
            ->assert('sortBy', '[a-zA-Z]+')
            ->assert('sortOrder', '[ascde]{3,4}')
            ->bind('project_bugs_sorted');
        $controller->get('/{id}/bugs/{sortBy}/{sortOrder}/page/{page}', [$this, 'bugsAction'])
            ->assert('id', '[1-9]\d*')
            ->assert('sortBy', '[a-zA-Z]+')
            ->assert('sortOrder', '[ascde]{3,4}')
            ->bind('project_bugs_sorted_paginated');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('project_edit');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('project_delete');
        return $controller;
    }


    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     * @param int $page Current page number
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function indexAction(Application $app, $page = 1)
    {
        $id = $this->getUserId($app);
        $projectRepository = new ProjectRepository($app['db']);
        $isAdmin = $this->checkIfAdmin($app, $id);
        if($isAdmin) $id = null;

        return $app['twig']->render(
            'project/index.html.twig',
            ['paginator' => $projectRepository->findAllPaginated($page, $id),
                'sortBy' => null,
                'sortOrder' => null,
                'priority' => null,
                'status' => null,
                'category' => null,]
        );
    }

    /**
     * Bugs action.
     *
     * @param \Silex\Application $app Silex application
     * @param Request $request
     * @param $id
     * @param int $page
     * @param null $sortBy
     * @param null $sortOrder
     * @return
     * @internal param null $statusFilter
     */
    public function bugsAction(Application $app, Request $request, $id, $page = 1, $sortBy = null, $sortOrder = null)
    {
        $priority = $request->get('priority', null);
        $status = $request->get('status', null);
        $category = $request->get('category', null);

        $userId = $this->getUserId($app);
        $bugRepository = new BugRepository($app['db']);
        $projectRepository = new ProjectRepository($app['db']);
        $isAdmin = $this->checkIfAdmin($app, $userId);

        $project = $projectRepository->findOneById($id);

        if (!$project) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.project_not_found',
                ]
            );
            return $app->redirect($app['url_generator']->generate('project_index'));
        }

        if ($project['user_id'] != $userId && !$isAdmin) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'danger',
                    'message' => 'message.not_your_project',
                ]
            );
            return $app->redirect($app['url_generator']->generate('project_index'));
        }

        list($sortOrder, $sortBy) = $this->checkOrderOptions($sortOrder, $sortBy);
        $userId = $project['user_id'];

        return $app['twig']->render(
            'project/bugs.html.twig',
            ['bug' => $bugRepository->findAllFromProject($id),
                'projectId' => $id,
                'paginator' => $bugRepository->findAllPaginatedFromProject($id, $page, $sortBy, $sortOrder, $status, $priority, $category),
                'project' => $project,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
                'user' => $userId,
                'priority' => $priority,
                'status' => $status,
                'category' => $category,
                'bugsAll' => $bugRepository->countBugs($userId, $id),
                'bugsDone' => $bugRepository->countBugs($userId, $id, 'done')]
        );
    }

    /**
     * Add action.
     *
     * @param \Silex\Application $app Silex application
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     * @internal param string $id Element Id
     *
     * @internal param int $page
     */

    public function addAction(Application $app, Request $request)
    {
        $project = [];

        $form = $app['form.factory']->createBuilder(ProjectType::class, $project,
            ['project_repository' => new ProjectRepository($app['db']),
                'user_id' => $this->getUserId($app),
                'locale' => $request->getLocale(),])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $projectRepository = new ProjectRepository($app['db']);
            $project = $form->getData();
            $id = $this->getUserId($app);
            $project['user_id'] = $id;
            $projectRepository->save($project);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.project_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('project_index'), 301);
        }

        return $app['twig']->render(
            'project/add.html.twig',
            [
                'project' => $project,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Edit action.
     *
     * @param \Silex\Application $app Silex application
     * @param int $id Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function editAction(Application $app, $id, Request $request)
    {
        $projectRepository = new ProjectRepository($app['db']);
        $project = $projectRepository->findOneById($id);
        $userId = $this->getUserId($app);
        $isAdmin = $this->checkIfAdmin($app, $userId);

        if (!$project) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.project_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('project_index'));
        }

        $userId = $this->getUserId($app);

        if ($project['user_id'] != $userId && !$isAdmin) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'danger',
                    'message' => 'message.not_your_project',
                ]
            );
            return $app->redirect($app['url_generator']->generate('project_index'));
        }

        $form = $app['form.factory']->createBuilder(ProjectType::class, $project,
            ['project_repository' => new ProjectRepository($app['db']),
                'locale' => $request->getLocale(),])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $projectRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.project_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('project_index'), 301);
        }

        return $app['twig']->render(
            'project/edit.html.twig',
            [
                'project' => $project,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param \Silex\Application $app Silex application
     * @param int $id Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function deleteAction(Application $app, $id, Request $request)
    {
        $projectRepository = new ProjectRepository($app['db']);
        $project = $projectRepository->findOneById($id);

        if (!$project) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.project_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('project_index'));
        }

        $userId = $this->getUserId($app);
        $isAdmin = $this->checkIfAdmin($app, $userId);

        if ($project['user_id'] != $userId && !$isAdmin) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'danger',
                    'message' => 'message.not_your_project',
                ]
            );
            return $app->redirect($app['url_generator']->generate('project_index'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $project)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $projectRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.project_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('project_index'),
                301
            );
        }

        return $app['twig']->render(
            'project/delete.html.twig',
            [
                'project' => $project,
                'form' => $form->createView(),
            ]
        );
    }
}