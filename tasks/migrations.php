<?php

class migrations {

    public function execute($params) {
        $this->initProperties($params);
        if(!$this->checkParams()) {
            return 1;
        }

        $this->initDb();
        
        $this->migrations_dir = $this->project .
                DIRECTORY_SEPARATOR .
                'data' .
                DIRECTORY_SEPARATOR .
                'migrations';

        $this->migrations_version_file = $this->migrations_dir . DIRECTORY_SEPARATOR . 'version';
        $action = $this->action;
        return $this->$action($params);
    }

    public function initProperties($params) {
        $this->project = isset($params[1]) ? $params[1] : null;
        $this->action = isset($params[2]) ? $params[2] : null;
        $this->migration_name = isset($params[3]) ? $params[3] : null;
    }

    public function checkParams() {
        $db_conf_path = $this->project .
                DIRECTORY_SEPARATOR .
                'config' .
                DIRECTORY_SEPARATOR .
                'database.php';

        if(!file_exists($this->project) || !file_exists($db_conf_path)) {
            echo "Not in a project directory" . PHP_EOL;
            return false;
        }
        if(!isset($this->action)) {
            echo "Action not specified!" . PHP_EOL;
            return false;
        }
        if(!method_exists($this, $this->action)) {
            echo "Action does not exist!" . PHP_EOL;
            return false;
        }
        return true;
    }

    public function initDb() {
        $db_conf_path = $this->project .
                DIRECTORY_SEPARATOR .
                'config' .
                DIRECTORY_SEPARATOR .
                'database.php';

        $this->app_db_conf = require_once $db_conf_path;
        $this->pdo = new PDO($this->app_db_conf['dsn'], $this->app_db_conf['db_user'], $this->app_db_conf['db_password'], array(
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_STRINGIFY_FETCHES => false
        ));
    }

    public function listMigrations($sorting_order = SCANDIR_SORT_ASCENDING) {
        $files = scandir($this->migrations_dir, $sorting_order);
        $migrations = array();
        foreach($files as $file) {
            if(strpos('.', $file) === 0) {
                continue;
            }
            $parts = explode('.', $file);
            $ext = end($parts);
            if($ext !== 'php') {
                continue;
            }
            echo "Found " . $file . PHP_EOL;
            $migrations[] = $file;
        }
        return $migrations;
    }

    public function create() {
        $this->migration_name = '_' . date('YmdHis');
        $this->renderTemplate('migration', $this->migrations_dir .
                DIRECTORY_SEPARATOR . $this->migration_name . '.php');
        echo "Created " . $this->migration_name . PHP_EOL;
        return 0;
    }

    public function upgrade() {
        $version = $this->getCurrentVersion();

        $last_migration_time = strtotime($version);
        $migrations = $this->listMigrations();

        foreach($migrations as $migration) {
            $migration_time = $this->getMigrationTime($migration);

            if($last_migration_time >= $migration_time) {
                continue;
            }
            $this->executeMigration($migration);
            $last_executed = $migration;
        }

        $last_migration_time = $this->getMigrationTime($last_executed);

        $this->setCurrentVersion(date('Y-m-d H:i:s', $last_migration_time));
        return 0;
    }

    public function downgrade() {
        if($this->migration_name === null) {
            echo "Please specify version." . PHP_EOL;
            return 1;
        }
        $version = $this->getCurrentVersion();

        $last_migration_time = strtotime($version);
        $migrations = $this->listMigrations(SCANDIR_SORT_DESCENDING);

        $target_time = $this->getMigrationTime($this->migration_name);
        foreach($migrations as $migration) {
            $migration_time = $this->getMigrationTime($migration);
            if($target_time >= $migration_time) {
                $last_executed = $migration;
                break;
            }
            $this->executeMigration($migration, 'down');
        }

        $last_migration_time = $this->getMigrationTime($last_executed);

        $this->setCurrentVersion(date('Y-m-d H:i:s', $last_migration_time));
        return 0;
    }

    public function getMigrationTime($migration) {
        return strtotime(substr(explode('.', $migration)[0], 1));
    }

    public function getClass($migration) {
        return explode('.', $migration)[0];
    }

    public function executeMigration($migration, $action = 'up') {
        $file_path = $this->migrations_dir . DIRECTORY_SEPARATOR . $migration;

        if(!file_exists($file_path)) {
            return 1;
        }

        $class = $this->getClass($migration);

        echo 'Running: ' . $class . PHP_EOL;

        require_once $file_path;

        $object = new $class();

        if($this->pdo->exec($object->$action()) === false) {
            echo "Error running migration " . $class . PHP_EOL;
            return 1;
        }
        return 0;
    }

    public function getCurrentVersion() {
        $fp = fopen($this->migrations_version_file, 'r');
        $version = fread($fp, 19);
        fclose($fp);
        return $version;
    }

    public function setCurrentVersion($version) {
        $fp = fopen($this->migrations_version_file, 'w');
        fwrite($fp, $version, 19);
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
