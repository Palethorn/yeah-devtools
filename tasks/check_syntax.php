<?php

class check_syntax {
    public function execute($params) {
        $work_dir = getcwd();
        exec('find ' . $work_dir . ' -type f -name \*.php -exec php -l {} \;', $output, $return);
        foreach($output as $line) {
            echo $line . PHP_EOL;
        }
    }
}