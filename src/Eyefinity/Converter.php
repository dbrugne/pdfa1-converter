<?php

namespace Eyefinity;

class Converter {

    public $app;
    public $key;

    function __construct($app, $key) {
        $this->app = $app;
        $this->key = $key;
    }

    protected function _cmd($options, $from, $to) {
        if (PHP_OS == 'WINNT') {
            // Windows
            $cmd = '"'.'C:\Program Files (x86)\gs\gs9.15\bin\gswin32c.exe'.'"';
            $cmd .= ' ' . $options;
            $cmd .= " -sOutputFile={$to} {$from}";
        } else {
            // Linux
            $cmd = 'gs';
            $cmd .= ' ' . $options;
            $cmd .= " -sOutputFile=".escapeshellcmd($to)." ".escapeshellcmd($from);
        }
        return $cmd;
    }

    protected function _exec($cmd) {
        $output = shell_exec($cmd);
        // $output?
    }

    /**
     * @doc: http://svn.ghostscript.com/ghostscript/trunk/gs/doc/Ps2pdf.htm#PDFA
     */
    public function toPDFA1() {
        $from = $this->app['input'] . $this->key . '.pdf';
        $to = $this->app['output'] . $this->key . '.pdf';
        $cmd = $this->_cmd(" -dPDFA -dBATCH -dNOPAUSE -dNOOUTERSAVE -sDEVICE=pdfwrite -sColorConversionStrategy=Mono -sColorConversionStrategyForImages=Mono -dUseCIEColor -sProcessColorModel=DeviceGray", $from, $to); // -dUseCIEColor -sProcessColorModel=DeviceGray
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
