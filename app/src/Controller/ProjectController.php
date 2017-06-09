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
use Symfony\Component\HttpFoundation\Request;
use Form\ProjectType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


/**
 * Class ProjectController.
 *
 * @package Controller
 */
class ProjectController extends BaseController {
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
        $controller->get('/', [$this, 'indexAction'])->bind('project_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('project_index_paginated');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('project_add');
        $controller->get('/{id}/bugs', [$this, 'bugsAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('project_bugs');
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

        return $app['twig']->render(
            'project/index.html.twig',
            ['paginator' => $projectRepository->findAllPaginated($page, $id)]
        );
    }

    /**
     * Bugs action.
     *
     * @param \Silex\Application $app Silex application
     * @param $id
     * @param int $page
     * @return
     */
    public function bugsAction(Application $app, $id, $page = 1)
    {
        $projectId = $id;
        $userId = $this->getUserId($app);
        $bugRepository = new BugRepository($app['db']);
        $typeRepository = new TypeRepository($app['db']);
        $statusRepository = new StatusRepository($app['db']);
        $priorityRepository = new PriorityRepository($app['db']);
        $projectRepository = new ProjectRepository($app['db']);

        return $app['twig']->render(
            'project/bugs.html.twig',
            ['bug' => $bugRepository->findAllFromProject($projectId, $userId),
                'projectId' => $projectId,
                'paginator' => $bugRepository->findAllPaginatedFromProject($projectId, $userId, $page),
                'types' => $typeRepository->findAll(),
                'statuses' => $statusRepository->findAll(),
                'priorities' => $priorityRepository->findAll(),
                'projects' => $projectRepository->findAll(),
                'project' => $projectRepository->findOneById($projectId)]
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
            ['project_repository' => new ProjectRepository($app['db'])])->getForm();
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

        $form = $app['form.factory']->createBuilder(ProjectType::class, $project,
            ['project_repository' => new ProjectRepository($app['db'])])->getForm();
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