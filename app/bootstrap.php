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

$app['gs'] = 'C:\Program Files (x86)\gs\gs9.15\bin\gswin32.exe';

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

return $app;