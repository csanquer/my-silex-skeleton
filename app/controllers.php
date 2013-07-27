<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Exception Error page
$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
//        if ($e instanceof Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
//            $content = vsprintf('<h1>%d - %s (%s)</h1>', array(
//                $e->getStatusCode(),
//                Response::$statusTexts[$e->getStatusCode()],
//                $app['request']->getRequestUri()
//            ));
//            $code = $e->getStatusCode();
//        } elseif ($e instanceof Symfony\Component\HttpKernel\Exception\HttpException) {
//            $content = '<h1>An error occured!</h1>';
//            $code = $e->getStatusCode();
//        } else {
//            $content = '<h1>An error occured!</h1>';
//            $code = 200;
//        }
        return;
    }

    $page = 404 == $code ? '404.html.twig' : '500.html.twig';

    return new Response($app['twig']->render($page, array('code' => $code)), $code);
});

/*
 * mount or define custom controllers
 */

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})
->bind('homepage')
;

//$app->mount('/', );
