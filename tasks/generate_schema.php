<?php

class generate_schema {

    public function execute($params) {
	$work_dir = getcwd();
        $db_conf_path = $work_dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        if(!file_exists($db_conf_path)) {
            echo "Database configuration not found. Are you inside application directory?" . PHP_EOL;
            return 1;
        }
        $db_conf = require_once $work_dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        $db = new PDO($db_conf['dsn'], $db_conf['db_user'], $db_conf['db_password']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare('SHOW TABLES');
        $stmt->execute();
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fp = fopen($work_dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'schema.php', 'w');
        $schema = array();
        foreach ($tables as $t) {
            $table = $t['Tables_in_' . $db_conf['database']];
            $stmt = $db->prepare('DESCRIBE ' . $table);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $fields = array();
            foreach ($result as $field) {
                $field['pdo_type'] = $this->detectType($field['Type']);
                $fields[$field['Field']] = $field;
            }
            $schema[$table] = $fields;
        }
        $s = '$schema = ' . var_export($schema, true) . ';';
        fwrite($fp, '<?php' . PHP_EOL);
        fwrite($fp, $s);
        fclose($fp);
    }

    function detectType($type) {
        if (strpos($type, 'int') === 0) {
            return PDO::PARAM_INT;
        }
        if(strpos($type, 'tinyint') === 0) {
            return PDO::PARAM_BOOL;
        }
        return PDO::PARAM_STR;
    }

}
