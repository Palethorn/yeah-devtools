<?php

class generate_schema {

    public function execute($params) {

        if($this->init($params) === 1) {
            return 1;
        }

        $db_conf = require_once $this->db_conf_path;
        $schema = $this->pickupSchemaFromDb($db_conf);

        $s = '$schema = ' . var_export($schema, true) . ';';
        $fp = fopen($this->schema_path, 'w');
        fwrite($fp, '<?php' . PHP_EOL);
        fwrite($fp, $s);
        fclose($fp);
    }

    private function init($params) {
        if(!isset($params[1])) {
            echo "You didn't specify application name";
            return 1;
        }
        $this->app_name = $params[1];
        
        $work_dir = getcwd();
        $this->db_conf_path = $work_dir . DIRECTORY_SEPARATOR . $this->app_name . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        if(!file_exists($this->db_conf_path)) {
            echo "Database configuration not found. Are you inside project directory?" . PHP_EOL;
            return 1;
        }

        $this->schema_path = $work_dir . DIRECTORY_SEPARATOR . $this->app_name . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $this->app_name . '_schema.php';
    }

    private function pickupSchemaFromDb($db_conf) {
        $tables = $this->getTables($db_conf);
        $schema = array();
        foreach($tables as $t) {
            $table = $t['Tables_in_' . $db_conf['database']];
            $result = $this->describeTable($db_conf, $table);
            $fields = array();
            foreach($result as $field) {
                $field['pdo_type'] = $this->detectType($field['Type']);
                $fields[$field['Field']] = $field;
            }
            $schema[$table] = $fields;
        }
        return $schema;
    }

    private function getTables($db_conf) {
        $db = new PDO($db_conf['dsn'], $db_conf['db_user'], $db_conf['db_password']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare('SHOW TABLES');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function describeTable($db_conf, $table) {
        $db = new PDO($db_conf['dsn'], $db_conf['db_user'], $db_conf['db_password']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare('DESCRIBE ' . $table);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function detectType($type) {
        if(strpos($type, 'int') === 0) {
            return PDO::PARAM_INT;
        }
        if(strpos($type, 'tinyint') === 0) {
            return PDO::PARAM_BOOL;
        }
        return PDO::PARAM_STR;
    }

}
