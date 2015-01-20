<?php

require_once __DIR__ . '/../vendor/autoload.php';

use
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use
    Eyefinity\Application;

$app = new Application;
$app['debug'] = true;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'dbname'    => 'pdfa1',
        'user'      => 'root',
        'password'  => 'root',
        'host'      => 'localhost',
        'driver'    => 'pdo_mysql',
    ),
));

// configuration
$app['input'] = __DIR__.'/../var/input/';
$app['output'] = __DIR__.'/../var/output/';
$app['key'] = 'cledetestsecurite';

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

return $app;