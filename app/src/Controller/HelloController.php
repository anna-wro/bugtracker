<?php
/**
 * Hello controller.
 *
 * @copyright (c) 2016 Tomasz Chojna
 * @link http://epi.chojna.info.pl
 */
namespace Controller;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class HelloController.
 *
 * @package Controller
 */
class HelloController implements ControllerProviderInterface //wymuszenie ze obiekt ma byc konkrtengo typu (intefejs)
{
    /**
     * Routing settings.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return \Silex\ControllerCollection Result
     */
    public function connect(Application $app)  //w kazdym kontrolerze mamy metode connect
    {
        $controller = $app['controllers_factory'];
        $controller->get('/{name}', [$this, 'indexAction']); //zmienna name -> this indexAction

        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request Request object
     *
     * @return string Response
     */
    public function indexAction(Application $app, Request $request) //pierwszy parametr = obiekt aplikacji
    {
        $name = $request->get('name', ''); //('name' = nazwa zmiennej ; '' -> co sie ma stac jak nie znajdzie

        return $app['twig']->render('hello/index.html.twig', ['name' => $name]); //1szy param. nazwa ; 2gi do tablicy
    }
}
