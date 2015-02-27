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
        return shell_exec($cmd);
    }

    /**
     * @doc: http://svn.ghostscript.com/ghostscript/trunk/gs/doc/Ps2pdf.htm#PDFA
     */
    public function toPDFA1() {
        $from = $this->app['input'] . $this->key . '.pdf';
        $to = $this->app['output'] . $this->key . '.pdf';

        // prepare commande (Linux only)
        $profile = $this->app['lib']."PDFA_def.ps";
        $cmd = "gs -dPDFA -dBATCH -dNOPAUSE -dNOOUTERSAVE -dUseCIEColor -sProcessColorModel=DeviceGray -sColorConversionStrategy=Mono -sColorConversionStrategyForImages=Mono -sDEVICE=pdfwrite ";
        $cmd .= " -sOutputFile=".escapeshellarg($to)." ".escapeshellarg($profile)." ".escapeshellarg($from);

        return $this->_exec($cmd);
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
