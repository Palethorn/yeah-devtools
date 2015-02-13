<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Creating new application template
 *
 * @author david
 */
class create_app {

    public function execute($params) {
        $work_dir = getcwd() . DIRECTORY_SEPARATOR;
        if(!isset($params[1])) {
            throw new Exception('No application name provided', 0, null);
        }
        $this->app_name = $params[1];

        $this->app_dir = $work_dir . $this->app_name;
        $this->web_dir = $work_dir . 'web';
        $this->config_dir = $this->app_dir . DIRECTORY_SEPARATOR . 'config';
        $this->controllers_dir = $this->app_dir . DIRECTORY_SEPARATOR . 'controllers';
        $this->models_dir = $this->app_dir . DIRECTORY_SEPARATOR . 'models';
        $this->views_dir = $this->app_dir . DIRECTORY_SEPARATOR . 'views';
        $this->data_dir = $this->app_dir . DIRECTORY_SEPARATOR . 'data';
        $this->layouts_dir = $this->views_dir . DIRECTORY_SEPARATOR . 'layouts';

        $this->cloneFramework();

        $this->createDirs();
        $this->renderTemplate('index', $this->web_dir . DIRECTORY_SEPARATOR . $this->app_name . '.php');
        $this->renderTemplate('bootstrap', $work_dir . DIRECTORY_SEPARATOR . $this->app_name . '_bootstrap.php');
        $this->renderTemplate('routes', $this->config_dir . DIRECTORY_SEPARATOR . 'routes.php');
        $this->renderTemplate('schema', $this->data_dir . DIRECTORY_SEPARATOR . $this->app_name . '_schema.php');
        $this->renderTemplate('HomeController', $this->controllers_dir . DIRECTORY_SEPARATOR . 'HomeController.php');
        $this->renderTemplate('index_view', $this->views_dir . DIRECTORY_SEPARATOR . 'index.php');
        $this->renderTemplate('default_layout', $this->layouts_dir . DIRECTORY_SEPARATOR . 'default.php');
    }

    public function cloneFramework() {
        $command = 'git clone https://bitbucket.org/palethorn/yeah.git lib';
        $output = array();
        $return_var = 0;
        exec($command, $output, $return_var);
        if($return_var != 0) {
            throw new Exception('Could not clone repository', $return_var, null);
        }
    }

    private function createDirs() {
        if(!is_dir($this->app_dir)) {
            mkdir($this->app_dir);
        }
        if(!is_dir($this->web_dir)) {
            mkdir($this->web_dir);
        }
        if(!is_dir($this->config_dir)) {
            mkdir($this->config_dir, 0775, true);
        }
        if(!is_dir($this->controllers_dir)) {
            mkdir($this->controllers_dir, 0775, true);
        }
        if(!is_dir($this->models_dir)) {
            mkdir($this->models_dir, 0775, true);
        }
        if(!is_dir($this->views_dir)) {
            mkdir($this->views_dir, 0775, true);
        }
        if(!is_dir($this->layouts_dir)) {
            mkdir($this->layouts_dir, 0775, true);
        }
        if(!is_dir($this->data_dir)) {
            mkdir($this->data_dir, 0775, true);
        }
    }

    public function renderTemplate($template_name, $destination, $overwrite = false) {
        if(file_exists($destination) && $overwrite === false) {
            return;
        }
        ob_start();
        require_once 'templates' . DIRECTORY_SEPARATOR . 'create_app' . DIRECTORY_SEPARATOR . $template_name . '.template';
        $rendered = ob_get_clean();
        $rendered = str_replace('[?php', '<?php', $rendered);
        $rendered = str_replace('?]', '?>', $rendered);
        $fp = fopen($destination, 'w');
        fwrite($fp, $rendered);
        fclose($fp);
    }

}
