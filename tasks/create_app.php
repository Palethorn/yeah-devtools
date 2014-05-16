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
        $this->app_name = $params[1];
        $this->app_dir = $work_dir . $this->app_name;
        $this->web_dir = $work_dir . 'web';
        $this->config_dir = $this->app_dir . DIRECTORY_SEPARATOR . 'config';
        $this->controllers_dir = $this->app_dir . DIRECTORY_SEPARATOR . 'controllers';
        $this->models_dir = $this->app_dir . DIRECTORY_SEPARATOR . 'models';
        $this->views_dir = $this->app_dir . DIRECTORY_SEPARATOR . 'views';
        $this->data_dir = $this->app_dir . DIRECTORY_SEPARATOR . 'data';

        $this->createDirs();
        $this->renderTemplate('index', $this->web_dir . DIRECTORY_SEPARATOR . $this->app_name . '.php');
        $this->renderTemplate('bootstrap', $work_dir . DIRECTORY_SEPARATOR . $this->app_name . '_bootstrap.php');
        $this->renderTemplate('AppConfiguration', $this->config_dir . DIRECTORY_SEPARATOR . 'AppConfiguration.php');
        $this->renderTemplate('database', $this->config_dir . DIRECTORY_SEPARATOR . 'database.php');
        $this->renderTemplate('factories', $this->config_dir . DIRECTORY_SEPARATOR . 'factories.php');
        $this->renderTemplate('paths', $this->config_dir . DIRECTORY_SEPARATOR . 'paths.php');
        $this->renderTemplate('routes', $this->config_dir . DIRECTORY_SEPARATOR . 'routes.php');
        $this->renderTemplate('schema', $this->data_dir . DIRECTORY_SEPARATOR . $this->app_name . '_schema.php');
    }

    private function createDirs() {
        mkdir($this->app_dir);
        mkdir($this->web_dir);
        mkdir($this->config_dir, 0775, true);
        mkdir($this->controllers_dir, 0775, true);
        mkdir($this->models_dir, 0775, true);
        mkdir($this->views_dir, 0775, true);
        mkdir($this->data_dir, 0775, true);
    }
    
    public function renderTemplate($template_name, $destination) {
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
