<?php

require_once __DIR__ . '/../vendor/autoload.php';

use
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use
    Eyefinity\Application,
    Eyefinity\Model\ConversionManager;

use Knp\Provider\ConsoleServiceProvider;

$app = new Application;
$app['debug'] = true;

/**
 * Configuration provider
 */
$app->register(new DerAlex\Silex\YamlConfigServiceProvider(__DIR__.'/../etc/config.yml')); // should be linked (ln) on each environment
$app['input'] = __DIR__.'/../var/input/';
$app['output'] = __DIR__.'/../var/output/';
$app['lib'] = __DIR__.'/../lib/';
if (!file_exists($app['input'])) {
    mkdir($app['input'], 0777, true);
}
if (!file_exists($app['output'])) {
    mkdir($app['output'], 0777, true);
}

/**
 * Monolog provider
 */
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.name'    => 'pdfa1-converter',
    'monolog.logfile' => realpath(__DIR__.'/../log').'/conversions.log',
));

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

/**
 * Console provider
 */
$app->register(new ConsoleServiceProvider(), array(
    'console.name' => 'ScorApp',
    'console.version' => '1.0.0',
    'console.project_directory' => __DIR__ . '/..'
));

return $app;