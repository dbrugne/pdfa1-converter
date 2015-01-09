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
        if (PHP_OS == 'WINNT') {
            // Windows
            $cmd = '"'.'C:\www\jhove\jhove'.'"';
            $cmd .= ' ' . $options;
            $cmd .= " {$from}";
        } else {
            // Linux
            $cmd = 'gs';
            $cmd .= ' ' . $options;
            $cmd .= ' '.escapeshellcmd($from);
        }
        return $cmd;
    }

    protected function _exec($cmd) {
        return shell_exec($cmd);
    }

    /**
     * @doc: http://jhove.sourceforge.net/using.html
     */
    public function validatePDFA1() {
        if (!file_exists($this->file)) {
            return array();
        }

        $cmd = $this->_cmd(" -l OFF -h xml ", $this->file);
        $xml = $this->_exec($cmd);
        if ($xml === null) {
            throw new Exception('Unable to execute JHOVE validation or to obtain an output result');
        }
        $validation = new \SimpleXMLElement($xml);
        $output = array(
            'status' => (string)$validation->repInfo->status,
            'profile' => (string)$validation->repInfo->profiles->profile,
            'result' => false
        );
        if ($output['status'] == 'Well-Formed and valid' && $output['profile'] == 'ISO PDF/A-1, Level B') {
            $output['result'] = true;
        }
        return $output;
    }

}
