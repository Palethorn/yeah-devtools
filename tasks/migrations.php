<?php

class migrations {

    public function execute($params) {
        $this->project = isset($params[1]) ? $params[1] : null;
        $this->action = isset($params[2]) ? $params[2] : null;
        if(!file_exists($this->project) ||
                !file_exists($this->project .
                        DIRECTORY_SEPARATOR .
                        'config' .
                        DIRECTORY_SEPARATOR .
                        'database.php')) {
            echo "Not in a project directory" . PHP_EOL;
            return 1;
        }
        if(!isset($this->action)) {
            echo "Action not specified!" . PHP_EOL;
            return 1;
        }
        if(!method_exists($this, $this->action)) {
            echo "Action does not exist!" . PHP_EOL;
            return 1;
        }
        $this->app_db_conf = require_once $this->project .
                DIRECTORY_SEPARATOR .
                'config' .
                DIRECTORY_SEPARATOR .
                'database.php';
        $this->pdo = new PDO($this->app_db_conf['dsn'], $this->app_db_conf['db_user'], $this->app_db_conf['db_password'], array(
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_STRINGIFY_FETCHES => false
        ));

        $this->migrations_dir = $this->project .
                DIRECTORY_SEPARATOR .
                'data' .
                DIRECTORY_SEPARATOR .
                'migrations';
        $this->migrations_version_file = $this->migrations_dir . DIRECTORY_SEPARATOR . 'version';
        $action = $this->action;
        return $this->$action($params);
    }

    public function install() {
        echo "Installing..." . PHP_EOL;
        $q = 'create table if not exists migrations(' .
                'id int(11) auto_increment, ' .
                'created_at datetime, ' .
                'name varchar(255), ' .
                'primary key(id)' .
                ')';
        if($this->pdo->exec($q)) {
            echo "Failed" . PHP_EOL;
            return 1;
        }
        echo "Migrations installed." . PHP_EOL;
        return 0;
    }

    public function create() {
        $this->migration_name = '_' . uniqid();
        $this->renderTemplate('migration', $this->migrations_dir .
                DIRECTORY_SEPARATOR . $this->migration_name . '.php');
        $q = 'insert into migrations(created_at, name) values(\'' .
                date('Y-m-d H:i:s') . '\', \'' . $this->migration_name . '\')';
        $this->pdo->exec($q);
    }

    public function update() {
        $fp = fopen($this->migrations_version_file, 'r');
        $version = fread($fp, 19);
        fclose($fp);
        if(strlen($version) > 0) {
            $q = 'select * from migrations where created_at > \'' . $version . '\'';
        } else {
            $q = 'select * from migrations';
        }
        $r = $this->pdo->query($q);
        $r = $r->fetchAll();
        if(count($r) === 0) {
            echo "No migrations" . PHP_EOL;
            return 0;
        }
        foreach ($r as $migration) {
            $class = $migration['name'];
            echo 'Running: ' . $class . PHP_EOL;
            require_once $this->migrations_dir . DIRECTORY_SEPARATOR . $class . '.php';
            $mig = new $class();
            $r = $this->pdo->query($mig->up());
        }
        $fp = fopen($this->migrations_version_file, 'w');
        fwrite($fp, $migration['created_at'], 19);
        fclose($fp);
    }

    public function renderTemplate($template_name, $destination) {
        ob_start();
        require_once 'templates' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . $template_name . '.template';
        $rendered = ob_get_clean();
        $rendered = str_replace('[?php', '<?php', $rendered);
        $rendered = str_replace('?]', '?>', $rendered);
        $fp = fopen($destination, 'w');
        fwrite($fp, $rendered);
        fclose($fp);
    }

}
