<?php
/**
 * Bookmark controller.
 *
 * @copyright (c) 2016 Tomasz Chojna
 * @link http://epi.chojna.info.pl
 */

namespace Controller;

use Repository\BookmarkRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Form\BookmarkType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


/**
 * Class BookmarkController.
 *
 * @package Controller
 */
class BookmarkController implements ControllerProviderInterface
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
        $controller->get('/', [$this, 'indexAction'])->bind('bookmark_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('bookmark_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('bookmark_view');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('bookmark_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('bookmark_edit');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('bookmark_delete');
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
        $bookmarkRepository = new BookmarkRepository($app['db']);

        return $app['twig']->render(
            'bookmark/index.html.twig',
            ['paginator' => $bookmarkRepository->findAllPaginated($page)]
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
        $bookmarkRepository = new BookmarkRepository($app['db']);

        return $app['twig']->render(
            'bookmark/view.html.twig',
            ['bookmark' => $bookmarkRepository->findOneById($id),
                'bookmarkId' => $id]
        );
    }

    public function addAction(Application $app, Request $request)
    {
        $bookmark = [];

        $form = $app['form.factory']->createBuilder(BookmarkType::class, $bookmark)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bookmarkRepository = new BookmarkRepository($app['db']);
            $bookmarkRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('bookmark_index'), 301);
        }

        return $app['twig']->render(
            'bookmark/add.html.twig',
            [
                'bookmark' => $bookmark,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Edit action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function editAction(Application $app, $id, Request $request)
    {
        $bookmarkRepository = new BookmarkRepository($app['db']);
        $bookmark = $bookmarkRepository->findOneById($id);

        if (!$bookmark) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('bookmark_index'));
        }

        $form = $app['form.factory']->createBuilder(BookmarkType::class, $bookmark)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bookmarkRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('bookmark_index'), 301);
        }

        return $app['twig']->render(
            'bookmark/edit.html.twig',
            [
                'bookmark' => $bookmark,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function deleteAction(Application $app, $id, Request $request)
    {
        $bookmarkRepository = new BookmarkRepository($app['db']);
        $bookmark = $bookmarkRepository->findOneById($id);

        if (!$bookmark) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('bookmark_index'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $bookmark)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bookmarkRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('bookmark_index'),
                301
            );
        }

        return $app['twig']->render(
            'bookmark/delete.html.twig',
            [
                'bookmark' => $bookmark,
                'form' => $form->createView(),
            ]
        );
    }
}