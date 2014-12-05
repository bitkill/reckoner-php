<?php
/* Reckoner, a High Performance, no-overhead HTTP router
 * by Rui Fernandes
 */

namespace Reckoner;

set_exception_handler('\Reckoner\App::exception'); // bootstrap

class App {
    protected $_server = [];

    public function __construct() {
        $this->_server = &$_SERVER;
    }

    public function get($pattern, $callback) {
        $this->_route('GET', $pattern, $callback);
    }

    public function get_p($pattern, $callback) {
        $this->_route_p('GET', $pattern, $callback);
    }

    public function post($pattern, $callback) {
        $this->_route('POST', $pattern, $callback);
    }

    public function delete($pattern, $callback) {
        $this->_route('DELETE', $pattern, $callback);
    }

    protected function _route($method, $pattern, $callback) {
        if ($this->_server['REQUEST_METHOD']!=$method) return;

        // convert URL parameter (e.g. ":id", "*") to regular expression
        $regex = preg_replace('#:([\w]+)#', '(?<\\1>[^/]+)',
            str_replace(['*', ')'], ['[^/]+', ')?'], $pattern));
        if (substr($pattern,-1)==='/') $regex .= '?';

        // extract parameter values from URL if route matches the current request
        if (!preg_match('#^'.$regex.'$#', $this->_server['REQUEST_URI'], $values)) {
            return;
        }
        // extract parameter names from URL
        preg_match_all('#:([\w]+)#', $pattern, $params, PREG_PATTERN_ORDER);
        $args = [];
        foreach ($params[1] as $param) {
            if (isset($values[$param])) $args[] = urldecode($values[$param]);
        }
        $this->_exec($callback, $args);
    }

    protected function _route_p($method, $pattern, $callback) {
        if ($this->_server['REQUEST_METHOD']!=$method) return;

        // convert URL parameters (":p", "*") to regular expression
        $regex = str_replace(['*','(',')',':p'], ['[^/]+','(?:',')?','([^/]+)'],
            $pattern);
        if (substr($pattern,-1)==='/') $regex .= '?';

        // extract parameter values from URL if route matches the current request
        if (!preg_match('#^'.$regex.'$#', $this->_server['REQUEST_URI'], $values)) {
            return;
        }
        // decode URL parameters
        array_shift($values);
        foreach ($values as $key=>$value) $values[$key] = urldecode($value);
        $this->_exec($callback, $values);
    }

    protected function _exec(&$callback, &$args) {
        foreach ((array)$callback as $cb) call_user_func_array($cb, $args);
        //throw new Halt(); // Exception instead of exit;
        exit;
    }

    // Stop execution on exception and log as E_USER_WARNING
    public static function exception($e) {
        if ($e instanceof Halt) return;
        trigger_error($e->getMessage()."\n".$e->getTraceAsString(), E_USER_WARNING);
        $app = new App();
        $app->display('error.php', 500);
    }

    public function quote($str) {
        return htmlspecialchars($str, ENT_QUOTES);
    }

    public function render($template) {
        ob_start();
        include($template);
        return ob_get_clean();
    }

    public function display($template, $status=null) {
        if ($status) header('HTTP/1.1 '.$status);
        include($template);
    }

    public function __get($name) {
        if (isset($_REQUEST[$name])) return $_REQUEST[$name];
        return '';
    }
}

class AppJson extends App {
    protected function _exec(&$callback, &$args) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(call_user_func_array($callback, $args));
        throw new Halt(); // Exception instead of exit;
    }
}

// use Halt-Exception instead of exit;
class Halt extends \Exception {}