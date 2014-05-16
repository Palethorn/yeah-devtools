<?php
namespace \Yeah\Fw\Tasks;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of generate_model
 *
 * @author david
 */
class model {
    public function execute($params) {
        $this->model_name = $params[0];
        $model_path = $params[1];
        ob_start();
        require_once 'templates' . DIRECTORY_SEPARATOR . 'model.template';
        $rendered = ob_get_clean();
        $rendered = str_replace('[?php', '<?php', $rendered);
        $rendered = str_replace('?]', '?>', $rendered);
        $fp = fopen($model_path . ucfirst($this->modelname) . 'Model', 'w');
        fwrite($fp, $rendered);
        fclose($fp);
    }
}
