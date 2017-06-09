<?php
/**
 * Bug controller.
 */

namespace Controller;

use Repository\BugRepository;
use Repository\PriorityRepository;
use Repository\ProjectRepository;
use Repository\StatusRepository;
use Repository\TypeRepository;
use Silex\Application;
use Form\BugType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


/**
 * Class BugController.
 *
 * @package Controller
 */

class BugController extends BaseController{

    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])->bind('bug_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('bug_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('bug_view');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('bug_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('bug_edit');
        $controller->get('/{id}/change', [$this, 'changeStatusAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('bug_change_status');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('bug_delete');
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
        $bugRepository = new BugRepository($app['db']);
        $typeRepository = new TypeRepository($app['db']);
        $statusRepository = new StatusRepository($app['db']);
        $priorityRepository = new PriorityRepository($app['db']);
        $projectRepository = new ProjectRepository($app['db']);

        return $app['twig']->render(
            'bug/index.html.twig',
            ['paginator' => $bugRepository->findAllPaginated($page, $id),
                'types' => $typeRepository->findAll(),
                'statuses' => $statusRepository->findAll(),
                'priorities' => $priorityRepository->findAll(),
                'projects' => $projectRepository->findAll()]
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
    public function viewAction(Application $app, $id)
    {
        $bugRepository = new BugRepository($app['db']);
        $typeRepository = new TypeRepository($app['db']);
        $statusRepository = new StatusRepository($app['db']);
        $priorityRepository = new PriorityRepository($app['db']);
        $projectRepository = new ProjectRepository($app['db']);

        $bug = $bugRepository->findOneById($id);
        $projectId = $bug['project_id'];


        return $app['twig']->render(
            'bug/view.html.twig',
            ['bug' => $bugRepository->findOneById($id),
                'bugId' => $id,
                'types' => $typeRepository->findAll(),
                'statuses' => $statusRepository->findAll(),
                'priorities' => $priorityRepository->findAll(),
                'project' => $projectRepository->findOneById($projectId)]
        );
    }


    /**
     * Change status action
     *
     * @param \Silex\Application $app Silex application
     * @param int $id Record id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */

    public function changeStatusAction(Application $app, $id)
    {
        $bugRepository = new BugRepository($app['db']);
        $bugToChange = $bugRepository->findOneById($id);

        if (!$bugToChange) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('bug_index'));
        }

//        dump($bugToChange);
        $bugToChange['status_id'] = ($bugToChange['status_id'] == 1 ? 2 : 1);
//        dump($bugToChange);
        $bugRepository->save($bugToChange);

        return $app->redirect($app['url_generator']->generate('bug_index'), 301);
    }


    /**
     * Add action.
     *
     * @param \Silex\Application $app Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function addAction(Application $app, Request $request)
    {
        $bug = [];

        $form = $app['form.factory']->createBuilder(
            BugType::class,
            $bug,
            ['types_repository' => new TypeRepository($app['db']),
                'projects_repository' => new ProjectRepository($app['db']),
                'statuses_repository' => new StatusRepository($app['db']),
                'priorities_repository' => new PriorityRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bug = $form->getData();
            $id = $this->getUserId($app);
            $bug['user_id'] = $id;
            $bugRepository = new BugRepository($app['db']);
            $bugRepository->save($bug);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('project_bugs', ['id' => $bug['project_id']]), 301);
        }

        return $app['twig']->render(
            'bug/add.html.twig',
            [
                'bug' => $bug,
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
        $bugRepository = new BugRepository($app['db']);
        $bug = $bugRepository->findOneById($id);

        if (!$bug) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('bug_index'));
        }

        $form = $app['form.factory']->createBuilder(
            BugType::class,
            $bug,
            ['types_repository' => new TypeRepository($app['db']),
                'projects_repository' => new ProjectRepository($app['db']),
                'statuses_repository' => new StatusRepository($app['db']),
                'priorities_repository' => new PriorityRepository($app['db'])]
        )->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bugRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('project_bugs', ['id' => $bug['project_id']]), 301);
        }

        return $app['twig']->render(
            'bug/edit.html.twig',
            [
                'bug' => $bug,
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
        $bugRepository = new BugRepository($app['db']);
        $bug = $bugRepository->findOneById($id);

        if (!$bug) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('bug_index'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $bug)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bugRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('bug_index'),
                301
            );
        }

        return $app['twig']->render(
            'bug/delete.html.twig',
            [
                'bug' => $bug,
                'form' => $form->createView(),
            ]
        );
    }
}