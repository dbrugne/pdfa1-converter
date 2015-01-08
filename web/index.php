<?php

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

use Symfony\Component\HttpFoundation\Request;
use Eyefinity\Model\ConversionManager;

$app = include __DIR__.'/../app/bootstrap.php';

$before = function(Request $request) use ($app) {
    // @todo : security
};
$after = function(Request $request) use ($app) {
    // @todo : logs?
};

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
        $clean[] = $item->getData();
    }
    return $app->json($clean, 200);
});

/**
 * Webservice
 */
$app->get('/convert', function(Request $request) use ($app) {
    return $app->json(array('errorCode' => 1, 'errorMessage' => 'GET request are not allowed (use POST instead)'), 405);
});
$app->post('/convert', function(Request $request) use ($app) {
    if (!$_FILES || !array_key_exists('source', $_FILES) || !$_FILES['source']['tmp_name'])
        return $app->json(array('errorCode' => 1, 'errorMessage' => 'POST request should contains a "source" param'), 400);

    /**
     * array(
     *  'name' => '',
     *  'type' => '',
     *  'tmp_name' => '',
     *  'error' => '',
     *  'size' => '',
     * )
     * $_SERVER['REMOTE_ADDR']
     */
    // file
    $file = $_FILES['source'];
    $uniqName = uniqid() . '_' . sha1($file['name']).'.pdf';
    $inputPath = __DIR__.'/../var/input/'.$uniqName;
    $outputPath = __DIR__.'/../var/output/'.$uniqName;
    if (!move_uploaded_file($file['tmp_name'], $inputPath)) {
        return $app->json(array('errorCode' => '1', 'errorMessage' => 'Unable to move uploaded file on server'), 500);
    }

    // database
    $conversionManager = new ConversionManager($app);
    $id = $conversionManager->insert(array(
        'original_name' => $file['name'],
        'original_size' => $file['size'],
        'original_type' => $file['type'],
        'from_ip'       => $_SERVER['REMOTE_ADDR'],
        'created_at'    => date('Y-m-d H:i:s'),
        'current_name'  => $uniqName
    ));

    // batch conversion
    // http://svn.ghostscript.com/ghostscript/trunk/gs/doc/Ps2pdf.htm#PDFA
    // noir et blanc : http://superuser.com/questions/200378/converting-a-pdf-to-black-white-with-ghostscript
    if (PHP_OS == 'WINNT') {
        // Windows
        $cmd = '"'.$app['gs'].'"';
        $cmd .= " -dPDFA -dBATCH -dNOPAUSE -dNOOUTERSAVE -dUseCIEColor -sProcessColorModel=DeviceGray -sDEVICE=pdfwrite";
        $cmd .= " -sOutputFile={$outputPath} {$inputPath}";
    } else {
        // Linux
        $cmd = "gs \\
        -dPDFA \\
        -dBATCH \\
        -dNOPAUSE \\
        -dNOOUTERSAVE \\
        -dUseCIEColor \\
        -sProcessColorModel=DeviceGray \\
        -sDEVICE=pdfwrite \\
        -sOutputFile=".escapeshellcmd($outputPath)." \\"
            . escapeshellcmd($inputPath);
    }
    $output = shell_exec($cmd);

    return $app->json(array('errorCode' => 0), 200); // @todo add base64encoded file
})
->before($before)
->after($after);

$app->run();