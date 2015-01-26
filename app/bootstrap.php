<?php

require_once __DIR__ . '/../vendor/autoload.php';

use
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use
    Eyefinity\Application,
    Eyefinity\Model\ConversionManager;

$app = new Application;
$app['debug'] = true;

/**
 * Configuration provider
 */
$app->register(new DerAlex\Silex\YamlConfigServiceProvider(__DIR__.'/../etc/config.yml')); // should be linked (ln) on each environment
$app['input'] = __DIR__.'/../var/input/';
$app['output'] = __DIR__.'/../var/output/';

/**
 * Doctrine provider
 */
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'dbname'    => $app['config']['database']['dbname'],
        'user'      => $app['config']['database']['user'],
        'password'  => $app['config']['database']['password'],
        'host'      => $app['config']['database']['host'],
        'driver'    => 'pdo_mysql',
    ),
));
$app['cm'] = new ConversionManager($app);

/**
 * Twig provider
 */
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

return $app;