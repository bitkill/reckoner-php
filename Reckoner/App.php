<?php
/* Reckoner, a High Performance, no-overhead HTTP router
 * by Rui Fernandes <ruifernandes@crossinganswers.com>
 */

namespace Reckoner;

\set_exception_handler('\Reckoner\App::exception'); // bootstrap

class App {
    protected $_server = [];
    private $base_dir = "";
    private $group_dir = "";
    private $exception_file = "exception.php";

    public function __construct(Array $options = []) {
        $this->_server = &$_SERVER;
        if (isset($options['base_dir'])) {
            $this->base_dir = $options['base_dir'];
        }
        if (isset($options['name'])) {
            cli_set_process_title($options['name']);
        } else {
            cli_set_process_title('Reckoner Router');
        }
        $this->group_dir = $this->base_dir;
    }
    
    public function is_secure() {
        return
            (!empty($this->_server['HTTPS']) && $this->_server['HTTPS'] !== 'off')
            || $this->_server['SERVER_PORT'] == 443;
    }
    
    public function is_ajax() {
        if(!empty($this->_server['HTTP_X_REQUESTED_WITH']) && strtolower($this->_server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return TRUE;
        }
        return false;
    }

    public function get($pattern, $callback) {
        $this->_route('GET', $pattern, $callback);
    }

    public function get_p($pattern, $callback) {
        $this->_route_p('GET', $pattern, $callback);
    }
    
    public function put($pattern, $callback) {
        $this->_route('PUT', $pattern, $callback);
    }

    public function post($pattern, $callback) {
        $this->_route('POST', $pattern, $callback);
    }

    public function delete($pattern, $callback) {
        $this->_route('DELETE', $pattern, $callback);
    }
    
    public function group($pattern, $callback) {
        $regex = $this->group_dir . $pattern . ".+";
        if (!preg_match('#^'.$regex.'$#', $this->_server['REQUEST_URI'])) {
            return;
        }
        $old_group_dir = $this->group_dir;
        $this->group_dir = $this->group_dir . $pattern;
        foreach ((array)$callback as $cb) { call_user_func($cb); }
        $this->group_dir = $old_group_dir;
    }
    
    /**
     * custom methods, easy!
     * @param string $name
     * @param array $arguments
     */
    public function __call($name, $arguments) {
        $this->route(strtoupper($name), $arguments[0], $arguments[1]);
    }

    protected function _route($method, $pattern, $callback) {
        if ($this->_server['REQUEST_METHOD'] != $method) { return; }

        // convert URL parameter (e.g. ":id", "*") to regular expression
        $regex = preg_replace('#:([\w]+)#', '(?<\\1>[^/]+)',
            str_replace(['*', ')'], ['[^/]+', ')?'], $this->group_dir . $pattern));
        if (substr($pattern,-1)==='/') {
            $regex .= '?';
        }

        // extract parameter values from URL if route matches the current request
        $values = [];
        if (!preg_match('#^'.$regex.'$#', $this->_server['REQUEST_URI'], $values)) {
            return;
        }
        // extract parameter names from URL
        preg_match_all('#:([\w]+)#', $pattern, $params, PREG_PATTERN_ORDER);
        $args = [];
        foreach ($params[1] as $param) {
            if (isset($values[$param])) { $args[] = urldecode($values[$param]); }
        }
        $this->_exec($callback, $args);
    }

    protected function _route_p($method, $pattern, $callback) {
        if ($this->_server['REQUEST_METHOD']!=$method) {
            return;
        }

        // convert URL parameters (":p", "*") to regular expression
        $regex = str_replace(['*','(',')',':p'], ['[^/]+','(?:',')?','([^/]+)'],
            $this->group_dir . $pattern);
        if (substr($pattern,-1)==='/') { $regex .= '?'; }

        // extract parameter values from URL if route matches the current request
        $values = [];
        if (!preg_match('#^'.$regex.'$#', $this->_server['REQUEST_URI'], $values)) {
            return;
        }
        // decode URL parameters
        array_shift($values);
        foreach ($values as $key=>$value) {
            $values[$key] = urldecode($value);
        }
        $this->_exec($callback, $values);
    }

    protected function _exec(&$callback, &$args) {
        foreach ((array)$callback as $cb) { call_user_func_array($cb, $args); }        
        throw new Halt();
    }

    // Stop execution on exception and log as E_USER_WARNING
    public function exception($e) {
        if ($e instanceof Halt) { return; }
        trigger_error($e->getMessage()."\n".$e->getTraceAsString(), E_USER_WARNING);
        $app = new App();
        $app->display($this->exception_file, 500);
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
        if ($status) {
            header('HTTP/1.1 '.$status);
        }
        include($template);
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