<?php

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
        'order_by' => array('date', 'DESC'),
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
// GET request handler
$error = function($msg, $code = 500) use ($app) {
    $app['cm']->update(array( 'error' => $msg ), array( 'id' => $app['request_id'] ));
    return $app->json(array('errorCode' => 1, 'errorMessage' => $msg), $code);
};
$app->get('/convert', function(Request $request) use ($app, $error) {
    return $error('GET request are not allowed (use POST instead)', 405);
});
// POST request handler
$before = function(Request $request) use ($app, $error) {
    $key = $request->request->get('key');
    if (!$key || $app['key'] !== $key)
        return $error('Security key not found or mismatch', 403);

    if (!$request->files->has('source'))
        return $error('POST request should contains a "source" param', 400);

    // generate unique name for this request
    $app['request_local'] = uniqid();

    // create request log
    $file = $request->files->get('source');
    $app['cm'] = new ConversionManager($app);
    // @doc: http://api.symfony.com/2.0/Symfony/Component/HttpFoundation/File/UploadedFile.html
    $app['request_id'] = $app['cm']->insert(array(
        'local_name'     => $app['request_local'],
        'date'           => date('Y-m-d H:i:s'),
        'from_store_id'  => ($request->request->has('store_id')) ? $request->request->get('store_id') : null,
        'from_ip'        => $request->server->get('REMOTE_ADDR'),
        'original_name'  => $file->getClientOriginalName(),
        'original_type'  => $file->getClientMimeType(),
        'original_size'  => $file->getClientSize(), // in octets
    ));

    // duration
    $app['start'] = microtime(true);
};
$post = function(Request $request) use ($app, $error) {
    $file = $request->files->get('source');

    try {
        $localFile = $file->move($app['input'],  $app['request_local'] . '.pdf');
    } catch (Exception $e) {
        return $error('Unable to move uploaded file on server', 500);
    }

    try {
        $converter = new Converter($app, $app['request_local']);
        $converter->toPDFA1(); // to PDF/A-1b
        $string = $converter->toBase64();
        return new Response($string, 200);
    } catch(Exception $e) {
        return $error($e->getMessage(), 500);
    }
};
$after = function(Request $request, Response $response) use ($app, $error) {
    // duration
    $duration = (microtime(true) - $app['start']);

    // log in database
    $data = array(
        'duration' => round($duration, 4), // in ms
        'success'  => 1,
    );
    $app['cm']->update($data, array( 'id' => $app['request_id'] ));
};

$app->post('/convert', $post)
->before($before)
->after($after);

$app->run();