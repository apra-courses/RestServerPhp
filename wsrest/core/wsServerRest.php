<?php

error_reporting(E_ERROR);

class wsServerRest {

    private $routes;
    private $routeKey;
    private $lastErrorCode;
    private $lastErrorDescription;

    public function __construct() {
        $this->loadRoutes();
    }

    public function dispatch() {
        $this->setLastErrorCode(0);
        $this->setLastErrorDescription("");

        // controlla se la richiesta è valida
        $requestInfo = $this->extractRequestInfo();
        if (!$this->isRequestValid($requestInfo)) {
            $this->handleNotFound();
            die();
        }
        try {
            // istanzia controller e chiama il metodo
            $result = $this->call($requestInfo);
            if ($result === false) {
                $this->handleInternalError();
                echo $this->safeUtf8Encode($this->getLastErrorDescription());
                die();
            }

            // formatta risposta, in funzione della configurazione impostata sulla route
            $this->formatOutput($result, $requestInfo);
        } catch (Exception $ex) {
            $this->handleInternalError();
        }
    }

    /*
     * Estrae le seguenti informazioni dalla request:
     * - VERB [GET/POST]
     * - CONTROLLER
     * - ACTION
     */

    private function extractRequestInfo() {
        $info['VERB'] = $_SERVER['REQUEST_METHOD'];
        list($controller, $action) = explode('/', substr($_SERVER['PATH_INFO'], 1));
        $info['CONTROLLER'] = $controller . 'Controller';
        $info['ACTION'] = $action;
        $info['PARAMS'] = $this->extractRequestParams();

        // Richiesta Multipart: se presenti dei files, li aggiunge ai parametri
        $info['PARAMS']['FILES'] = (count($_FILES) > 0 ? $_FILES : array());

        return $info;
    }

    /*
     * Estrae parametri dalla request
     */

    private function extractRequestParams() {
        $params = array();
        $sp = $_SERVER['QUERY_STRING'];
        parse_str($sp, $params);
        switch (strtolower($_SERVER['REQUEST_METHOD'])) {
            case 'post':
                $params = array_merge($_POST, $params);
                break;
        }
        parse_str(file_get_contents("php://input"), $inputParams);
        $params = array_merge($inputParams, $params);
        if ($params == null) {
            $params = array();
        }
        return $params;
    }

    /*
     * Invoca l'azione del controller, in funzione della request
     */

    private function call($requestInfo) {
        $controller = $requestInfo['CONTROLLER'];
        $action = $requestInfo['ACTION'];
        require_once(__DIR__ . '/../controller/' . $controller . '.php');
        $instance = new $controller;
        $instance->setLastAction($requestInfo["VERB"]);
        $response = $instance->$action($requestInfo['PARAMS']);
        $this->setLastErrorCode($instance->getLastErrorCode());
        $this->setLastErrorDescription($instance->getLastErrorDescription());

        return $response;
    }

    /*
     * Formatta output
     * Se specificato il formato nei parametri, utilizza quello,
     * altrimenti va in fallback nella definizione delle route
     */

    private function formatOutput($result, $requestInfo) {
        if (array_key_exists("PARAMS", $requestInfo) && array_key_exists("format", $requestInfo['PARAMS'])) {
            $format = $requestInfo['PARAMS']['format'];
        } else {
            $routes = $this->getRoutes();
            $format = $routes[$this->getRouteKey()]['OUTPUT'];
        }

        switch (strtolower($format)) {
            case 'download':
                $out = $this->formatOutputDownload($result);
                return;
            case 'octectstream':
                $out = $this->formatOutputOctectStream($result);
                return;
            case 'text':
                $out = $this->formatOutputText($result);
                break;
            case 'xml':
                $out = $this->formatOutputXml($result);
                break;
            case 'json':
            default:
                $out = $this->formatOutputJson($result);
        }
        ob_end_clean();
        header($out['header']);
        echo $out['encoded'];
    }

    private function formatOutputDownload($result) {
        require_once ITA_LIB_PATH . '/itaPHPCore/itaMimeTypeUtils.class.php';

        set_time_limit(0);

        $mime = itaMimeTypeUtils::estraiEstensione($result['filepath']);

        header('Content-Type:' . $mime);
        if ($mime !== 'text/html' && $result['forcedownload'] !== false) {
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        }
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($result['filepath']));

        $handler = @fopen($result['filepath'], 'rb');
        if ($handler) {
            while (!feof($handler)) {
                print @fread($handler, 1024 * 1024);
            }

            fclose($handler);
        }
        if ($result['deletefile']) {
            unlink($result['filepath']);
        }
    }

    private function formatOutputText($result) {
        $out = array();
        $out['header'] = 'Content-Type: text/plain';
        $out['encoded'] = $result;

        return $out;
    }

    private function formatOutputOctectStream($result) {
        set_time_limit(0);
        header('Content-Type: application/octect-stream; charset=utf-8');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($result));


        print $result;
    }

    private function formatOutputJson($result) {
        $out = array();
        $out['header'] = 'Content-Type: application/json';
        $out['encoded'] = json_encode($result);

        return $out;
    }

    private function formatOutputXml($result) {
        $out = array();
        $out['header'] = 'Content-Type: application/xml';
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><RESULT/>');
        $this->arrayToXml($result, $xml);
        $xml->addAttribute('encoding', 'UTF-8');
        $out['encoded'] = $xml->asXML();

        return $out;
    }

    private function arrayToXml($data, &$xml_data) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item' . $key; //dealing with <0/>..<n/> issues
                }
                $subnode = $xml_data->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml_data->addChild("$key", $this->safeUtf8Encode($value));
            }
        }
    }

    /*
     * Controlla se la richiesta è valida (se censita nelle routes)
     */

    private function isRequestValid($requestInfo) {
        $this->setRouteKey('');
        foreach ($this->getRoutes() as $k => $r) {
            if (($r['VERB'] === $requestInfo['VERB']) &&
                    ($r['CONTROLLER'] === $requestInfo['CONTROLLER']) &&
                    ($r['ACTION'] === $requestInfo['ACTION'])) {
                $this->setRouteKey($k);
                return true;
            }
        }
        return false;
    }

    private function loadRoutes() {
        $this->routes = array();
        $files = glob(__DIR__ . '/../routes/*.ini');
        foreach ($files as $file) {
            $routes = parse_ini_file($file, true);
            if ($routes) {
                $this->routes = array_merge($this->routes, $routes);
            }
        }
    }

    private function handleNotFound() {
        header("HTTP/1.1 404 Not Found");
    }

    private function handleInternalError() {
        header("HTTP/1.1 500 Internal Server Error");
    }

    private function safeUtf8Encode($toEncode) {
        if (is_array($toEncode)) {
            return $toEncode;
        } else {
            return utf8_encode($toEncode);
        }
    }

    public function getRoutes() {
        return $this->routes;
    }

    public function setRoutes($routes) {
        $this->routes = $routes;
    }

    public function getRouteKey() {
        return $this->routeKey;
    }

    public function setRouteKey($routeKey) {
        $this->routeKey = $routeKey;
    }

    function getLastErrorCode() {
        return $this->lastErrorCode;
    }

    function getLastErrorDescription() {
        return $this->lastErrorDescription;
    }

    function setLastErrorCode($lastErrorCode) {
        $this->lastErrorCode = $lastErrorCode;
    }

    function setLastErrorDescription($lastErrorDescription) {
        $this->lastErrorDescription = $lastErrorDescription;
    }

}

?>
