<?php

/**
 * @todo :
 * - add file download link on client
 * - example CURL
 */

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Eyefinity\Model\ConversionManager,
    Eyefinity\Converter,
    Eyefinity\Validator;

$app = include __DIR__.'/../app/bootstrap.php';

/**
 * Client
 */
$app->get('/', function() use($app) {
    return $app['twig']->render('client.twig', array());
});
$app->get('/list', function() use($app) {
    $conversionManager = new ConversionManager($app);
    $raw = $conversionManager->findBy(array(), array(
        'order_by' => array('created_at', 'DESC'),
        'limit' => array(0, 10)
    ));
    $clean = array();
    foreach($raw as $item) {
        $newItem = $item->getData();
        $newItem['current_path'] = $app['output'].$item->getCurrentName().'.pdf';
        $clean[] = $newItem;
    }
    return $app->json($clean, 200);
});
$app->get('/validate/{file}', function($file) use($app) {
    $filepath = $app['output'].$file.'.pdf';
    $validator = new Validator($app, $filepath);
    return $app->json(array('file' => $filepath, 'validation' => $validator->validatePDFA1()), 200);
});

/**
 * Webservice
 */
$before = function(Request $request) use ($app) {
    $key = $request->request->get('key');
    if (!$key || $app['key'] !== $key)
        return $app->json(array('errorCode' => 1, 'errorMessage' => "Security key not found or mismatch"), 403);

    if (!$request->files->has('source'))
        return $app->json(array('errorCode' => 1, 'errorMessage' => 'POST request should contains a "source" param'), 400);

    $app['start'] = microtime(true);
};
$after = function(Request $request, Response $response) use ($app) {
    // duration
    $duration = (microtime(true) - $app['start']); // in ms
    $app['duration'] = round($duration, 2);

    // log in database
    $file = $request->files->get('source');
    $conversionManager = new ConversionManager($app);
    // @doc: http://api.symfony.com/2.0/Symfony/Component/HttpFoundation/File/UploadedFile.html
    $id = $conversionManager->insert(array(
        'original_name' => $file->getClientOriginalName(),
        'original_size' => $file->getClientSize(),
        'original_type' => $file->getClientMimeType(),
        'from_ip'       => $request->server->get('REMOTE_ADDR'),
        'store_id'      => ($request->request->has('store_id')) ? $request->request->get('store_id') : null,
        'duration'    => $duration,
        'created_at'    => date('Y-m-d H:i:s'),
        'current_name'  => $app['unique']
    ));
};
// GET request handler
$app->get('/convert', function(Request $request) use ($app) {
    return $app->json(array('errorCode' => 1, 'errorMessage' => 'GET request are not allowed (use POST instead)'), 405);
});
// POST request handler
$app->post('/convert', function(Request $request) use ($app) {
    $file = $request->files->get('source');
    $app['unique'] = uniqid();

    try {
        $localFile = $file->move($app['input'],  $app['unique'] . '.pdf');
    } catch (Exception $e) {
        return $app->json(array('errorCode' => '1', 'errorMessage' => 'Unable to move uploaded file on server'), 500);
    }

    try {
        $converter = new Converter($app, $app['unique']);
        $converter->toPDFA1(); // to PDF/A-1b
        $string = $converter->toBase64();
        return new Response($string, 200);
    } catch(Exception $e) {
        return $app->json(array('errorCode' => 1, 'errorMessage' => $e->getMessage()), 500);
    }
})
->before($before)
->after($after);

$app->run();