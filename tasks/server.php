<?php

class server {

    public function start($params) {
        if(count($params) < 4) {
            throw new Exception('Invalid syntax' . PHP_EOL . 'Syntax yeah server:start [ip]:[port] [app_name]', 0, null);
        }

        if(!file_exists('web')) {
            throw new Exception('Not in project root directory', 0, null);
        }

        $tmp_dir = sys_get_temp_dir();
        if(file_exists($tmp_dir . DS . 'php_server_pid')) {
            throw new Exception('Server already running', 0, null);
        }

        $null_output = '/dev/null';
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $null_output = 'NUL';
        }

        chdir(getcwd() . DS . 'web');
        $command = 'php -S ' . $params[1] . ':' . $params[2] . ' ' . $params[3] . '.php';
        $output = array();
        $return_var = 0;
        exec($command . ' > ' . $null_output . ' 2>&1 &', $output, $return_var);
        if($return_var != 0) {
            throw new Exception('Could not start server', 0, null);
        }

        exec('ps aux | grep "' . $command . '"', $output, $return_var);
        $out = explode(' ', preg_replace('/( )+/', ' ', $output[0]));
        file_put_contents('/tmp/php_server_pid', $out[1]);
        return 0;
    }

    public function stop($params) {
        if(!file_exists('/tmp/php_server_pid')) {
            throw new Exception('Server not started', 0, null);
        }
        $pid = file_get_contents('/tmp/php_server_pid');
        $command = 'kill -9 ' . $pid;
        exec($command);
        unlink('/tmp/php_server_pid');
        return 0;
    }

}
