<?php

namespace Eyefinity;

class Validator {

    public $app;
    public $file;

    function __construct($app, $file) {
        $this->app = $app;
        $this->file = $file;
    }

    protected function _cmd($options, $from) {


        return $cmd;
    }

    protected function _jhove() {
        // Linux only
        $cmd = '/www/jhove/jhove';
        $cmd .= ' -l OFF -h XML ';
        $cmd .= ' '.escapeshellcmd($this->file);
        $result = shell_exec($cmd);
        if ($result === null) {
            throw new \Exception('Unable to execute JHOVE validation or to obtain an output result');
        }

        return new \SimpleXMLElement($result);
    }

    /**
     * @doc: https://bitbucket.org/jhove2/main/wiki/Home
     */
    public function validatePDFA1() {
        if (PHP_OS == 'WINNT') {
            return array();
        }
        if (!file_exists($this->file)) {
            return array();
        }

        $jhove = $this->_jhove();
        $status = ($jhove->repInfo->status == 'Well-Formed and valid')
            ? true
            : false;

        $isPDFA1 = ($jhove->repInfo->profiles && $jhove->repInfo->profiles->profile && $jhove->repInfo->profiles->profile == 'ISO PDF/A-1, Level B')
            ? true
            : false;

        return ($status && $isPDFA1) ? true : false;
    }

}
