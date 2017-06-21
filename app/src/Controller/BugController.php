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
use Symfony\Component\HttpFoundation\Response;


/**
 * Class BugController.
 *
 * @package Controller
 */
class BugController extends BaseController
{

    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])->bind('bug_index');
        $controller->get('/{sortBy}/{sortOrder}', [$this, 'indexAction'])
            ->assert('sortBy', '[a-zA-Z]+')
            ->assert('sortOrder', '[ascde]{3,4}')
            ->bind('bug_index_sorted');
        $controller->get('/{sortBy}/{sortOrder}/page/{page}', [$this, 'indexAction'])
            ->assert('sortBy', '[a-zA-Z]+')
            ->assert('sortOrder', '[ascde]{3,4}')
            ->value('page', 1)
            ->bind('bug_index_sorted_paginated');
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
        $controller->post('/{id}/{type}/change', [$this, 'changeStatusAction'])
            ->method('POST')
            ->assert('type', '[a-zA-Z_]*')
            ->assert('id', '[1-9]\d*')
            ->bind('bug_change_status');
        $controller->post('/{id}/{type}/{sortBy}/{sortOrder}/change', [$this, 'changeStatusAction'])
            ->method('POST')
            ->assert('sortBy', '[a-zA-Z]+')
            ->assert('sortOrder', '[ascde]{3,4}')
            ->assert('type', '[a-zA-Z_]*')
            ->assert('id', '[1-9]\d*');
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
     * @param Request $request
     * @param int $page Current page number
     *
     * @param null $sortBy
     * @param null $sortOrder
     * @return Response HTTP Response
     */
    public function indexAction(Application $app, Request $request, $page = 1, $sortBy = null, $sortOrder = null)
    {
        $priority = $request->get('priority', null);
        $status = $request->get('status', null);
        $category = $request->get('category', null);
        $id = $this->getUserId($app);
        $bugRepository = new BugRepository($app['db']);

        list($sortOrder, $sortBy) = $this->checkOrderOptions($sortOrder, $sortBy);

        return $app['twig']->render(
            'bug/index.html.twig',
            ['paginator' => $bugRepository->findAllPaginated($page, $id, $sortBy, $sortOrder, $status, $priority, $category),
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
                'priority' => $priority,
                'status' => $status,
                'category' => $category,
                'bugsAll' => $bugRepository->countBugs($id),
                'bugsDone' => $bugRepository->countBugs($id, null, 'done')]
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
        return $app['twig']->render(
            'bug/view.html.twig',
            ['bug' => $bugRepository->findOneById($id),
                'bugId' => $id,
            ]
        );
    }


    /**
     * Change status action
     *
     * @param \Silex\Application $app Silex application
     * @param Request $request
     * @param int $id Record id
     * @param $type
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @internal param null $sortBy
     * @internal param null $sortOrder
     * @internal param Response $response
     */

    public function changeStatusAction(Application $app, Request $request, $id, $type)
    {
        $priority = $request->get('priority', null);
        $status = $request->get('status', null);
        $category = $request->get('category', null);
        $sortBy = $request->get('sortBy', null);
        $sortOrder = $request->get('sortOrder', null);

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

            if ($type == 'project_bugs') {
                if ($sortBy) {
                    return $app->redirect($app['url_generator']->generate('project_bugs_sorted',
                        ['id' => $bugToChange['project_id'],
                            'sortBy' => $sortBy,
                            'sortOrder' => $sortOrder,
                            'priority' => $priority,
                            'status' => $status,
                            'category' => $category
                        ]), 301);
                }
                return $app->redirect($app['url_generator']->generate('project_bugs',
                    ['id' => $bugToChange['project_id'],
                        'priority' => $priority,
                        'status' => $status,
                        'category' => $category
                    ]), 301);
            }

            if ($sortBy) {
                return $app->redirect($app['url_generator']->generate('bug_index_sorted',
                    [
                        'sortBy' => $sortBy,
                        'sortOrder' => $sortOrder,
                        'priority' => $priority,
                        'status' => $status,
                        'category' => $category
                    ]));
            }
            return $app->redirect($app['url_generator']->generate('bug_index',
                [
                    'priority' => $priority,
                    'status' => $status,
                    'category' => $category
                ]));
        }

        $app['session']->getFlashBag()->add(
            'messages',
            [
                'type' => 'success',
                'message' => 'message.status_changed',
            ]
        );

        $bugToChange['status_id'] = ($bugToChange['status_id'] == 1 ? 2 : 1);
        $bugRepository->save($bugToChange);

        if ($type == 'project_bugs') {
            if ($sortBy) {
                return $app->redirect($app['url_generator']->generate('project_bugs_sorted',
                    [
                        'id' => $bugToChange['project_id'],
                        'sortBy' => $sortBy,
                        'sortOrder' => $sortOrder,
                        'priority' => $priority,
                        'status' => $status,
                        'category' => $category]), 301);
            }
            return $app->redirect($app['url_generator']->generate('project_bugs',
                [
                    'id' => $bugToChange['project_id'],
                    'priority' => $priority,
                    'status' => $status,
                    'category' => $category]), 301);

        }
        if ($sortBy) {
            return $app->redirect($app['url_generator']->generate('bug_index_sorted',
                [
                    'sortBy' => $sortBy,
                    'sortOrder' => $sortOrder,
                    'priority' => $priority,
                    'status' => $status,
                    'category' => $category
                ]), 301);
        }
        return $app->redirect($app['url_generator']->generate('bug_index',
            [
                'priority' => $priority,
                'status' => $status,
                'category' => $category
            ]), 301);

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
                'bug_repository' => new BugRepository($app['db']),
                'projects_repository' => new ProjectRepository($app['db']),
                'statuses_repository' => new StatusRepository($app['db']),
                'priorities_repository' => new PriorityRepository($app['db']),
                'user_id' => $this->getUserId($app),
                'locale' => $request->getLocale(),
            ]
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
                'bug_repository' => new BugRepository($app['db']),
                'projects_repository' => new ProjectRepository($app['db']),
                'statuses_repository' => new StatusRepository($app['db']),
                'priorities_repository' => new PriorityRepository($app['db']),
                'user_id' => $this->getUserId($app),
                'locale' => $request->getLocale(),]
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
                'bugId' => $id,
            ]
        );
    }
}