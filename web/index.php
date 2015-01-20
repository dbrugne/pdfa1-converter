<?php

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Eyefinity\Converter,
    Eyefinity\Validator;

$app = include __DIR__.'/../app/bootstrap.php';

/**
 * Client
 */
$app->get('/', function() use($app) {
    return $app['twig']->render('client.twig', array());
});
$app->get('/list', function() use($app) {
    $raw = $app['cm']->findBy(array(), array(
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
$app->get('/convert', function(Request $request) use ($app) {
    return $app->json(array('errorCode' => 1, 'errorMessage' => 'GET request are not allowed (use POST instead)'), 405);
});
// POST request handler
$before = function(Request $request) use ($app) {
    $key = $request->request->get('key');
    if (!$key)
        return $app->json(array('errorCode' => 1, 'errorMessage' => 'Security key not found'), 403);
    if ($app['key'] !== $key)
        return $app->json(array('errorCode' => 1, 'errorMessage' => 'Security key mismatch'), 403);
    if (!$request->files->has('source'))
        return $app->json(array('errorCode' => 1, 'errorMessage' => 'POST request should contains a "source" param'), 400);

    // generate unique name for this request
    $app['request_local'] = uniqid();

    // create request log
    $file = $request->files->get('source');
    try {
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
    } catch (Exception $e) {
        return $app->json(array('errorCode' => 1, 'errorMessage' => 'Error while saving in database: '.$e), 400);
    }

    // duration
    $app['start'] = microtime(true);
};
$post = function(Request $request) use ($app) {
    $file = $request->files->get('source');

    try {
        $localFile = $file->move($app['input'],  $app['request_local'] . '.pdf');
    } catch (Exception $e) {
        return $app->json(array('errorCode' => 1, 'errorMessage' => 'Unable to move uploaded file on server'), 500);
    }

    try {
        $converter = new Converter($app, $app['request_local']);
        $converter->toPDFA1(); // to PDF/A-1b
        $string = $converter->toBase64();
        return new Response($string, 200);
    } catch(Exception $e) {
        return $app->json(array('errorCode' => 1, 'errorMessage' => $e->getMessage()), 500);
    }
};
$after = function(Request $request, Response $response) use ($app) {
    if ($response->getStatusCode() == 200) {
        // duration
        $duration = (isset($app['start'])) ? (microtime(true) - $app['start']): null;

        // log in database
        $data = array(
            'duration' => round($duration, 4), // in ms
            'success'  => 1,
        );
        $app['cm']->update($data, array( 'id' => $app['request_id'] ));
    } else {
        if (isset($app['request_id']))
            $app['cm']->update(array( 'error' => $response->getContent() ), array( 'id' => $app['request_id'] ));
    }
};

$app->post('/convert', $post)
->before($before)
->after($after);

$app->run();