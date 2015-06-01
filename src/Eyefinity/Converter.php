<?php

namespace Eyefinity;

class Converter {

    public $app;
    public $key;

    function __construct($app, $key) {
        $this->app = $app;
        $this->key = $key;
    }

    protected function _exec($cmd) {
        $output = array();
        $return_var = 0;
        exec($cmd, $output, $return_var);
        if ($return_var != 0) {
            $this->app['monolog']->addError(sprintf("Error while executing: %s", $cmd));
            $this->app['monolog']->addError(sprintf(implode('\n', $output)));
            throw new \Exception('Error while executing conversion, see logs');
        }

        return;
    }

    /**
     * @doc: http://svn.ghostscript.com/ghostscript/trunk/gs/doc/Ps2pdf.htm#PDFA
     */
    public function toPDFA1() {
        $from = $this->app['input'] . $this->key . '.pdf';
        $to = $this->app['output'] . $this->key . '.pdf';

        // prepare commande (Linux only)
        $profile = $this->app['lib'] . $this->app['config']['icc'];
        $cmd = 'gs -r200 -dPDFA -dBATCH -dNOPAUSE -dNOOUTERSAVE -sDEVICE=pdfwrite -c "/osetcolor {/setcolor} bind def /setcolor {pop [0 0 0] osetcolor} def"';
        $cmd .= " -sOutputFile=".escapeshellarg($to)." ".escapeshellarg($profile)." ".escapeshellarg($from);

        $this->_exec($cmd);
    }

    public function toBase64() {
        $from = $this->app['output'] . $this->key . '.pdf';
        $base64 = base64_encode(file_get_contents($from));
        if ($base64 === false) {
            throw new Exception('Error while base64 encoding');
        }
        return $base64;
    }

}
