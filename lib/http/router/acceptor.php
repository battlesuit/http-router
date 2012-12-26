<?php
namespace http\router;
use http\Request;

/**
 * Accepts route conditions by comparing to acceptable params
 * Paths are matched by preg_match
 *
 * PHP Version 5.3+
 * @author Thomas Monzel <tm@apparat-hamburg.de>
 * @version $Revision$
 * @package Battlesuit
 * @subpackage http-router
 */
class Acceptor {
  
  /**
   * Conditions to accept
   *
   * @access protected
   * @var array
   */
  protected $conditions = array('method', 'pattern');
  
  /**
   * Stored created acceptors
   *
   * @static
   * @access private
   * @var array
   */
  private static $acceptors = array();
  
  /**
   * Creates and stores an acceptor class
   * Executes the acception comparison
   *
   * @static
   * @access public
   * @param Route $route
   * @param Request $request
   */
  static function accept_route(Route $route, Request $request) {
    $class = get_called_class();
    if(isset(self::$acceptors[$class])) goto acception;
    
    $acceptor = new static();
    self::$acceptors[$class] = $acceptor;
    
    acception:
    return self::$acceptors[$class]->accept($route, $request);
  }
  
  /**
   * Tests route against the request by iterating through all accept_* methods
   *
   * @access public
   * @param Route $route
   * @return boolean
   */
  function accept(Route $route, Request $request) {   
    foreach($this->conditions as $condition) {
      if(!call_user_func(array($this, "accept_$condition"), $route, $request)) return false;
    }
    
    return true;
  }
  
  /**
   * Compares the request method against the route method condition
   *
   * @access protected
   * @param Route $route
   * @param Request $request;
   * @return boolean
   */
  protected function accept_method(Route $route, Request $request) {
    return $route->method() === $request->method();
  }
  
  /**
   * Compares the requests path_info against the route path condition
   *
   * @access protected
   * @param Route $route
   * @param Request $request
   * @return boolean
   */
  protected function accept_pattern(Route $route, Request $request) {
    $path = $request->resource_path();
    
    if(strlen($path) > 0) {
      if(strlen($path) !== 1) $path = rtrim($path, '/');
    } elseif(empty($request_path)) {       
      $path = (string)$request->path();
    }
    
    $pattern = $route->pattern();
    
    if($pattern === $path or $pattern === '/' and empty($path)) return true;
    
    $regex = $this->pattern_to_regex($pattern);
    
    
    if(preg_match($regex, $path, $path_params) === 1) {
      $data = array();
      $requirements = $route->requirements;
      
      foreach($path_params as $key => $value) {
        if(is_string($key) and !empty($value)) {      
          if(isset($requirements[$key])) {
            $regex = $requirements[$key];
            if(preg_match($regex, $value) === 0) return false;
          }
          
          $data["_$key"] = $value;
        }
      }
      
      $request->data = array_merge($data, $request->data);      
      return true;
    }
    
    return false;
  }
  
  /**
   * Converts the routes path into a matchable regular expression
   *
   * @access protected
   * @param string $path
   * @return string
   */
  protected function pattern_to_regex($path) {
    if(!isset($path)) return "#^.*$#";

    $path = str_replace('.', '\.', $path);
    $path = str_replace(')', ')?', $path);   
    $regex = preg_replace_callback('#(?:\&|\*|\+)([a-zA-Z0-9_]+)?#', array($this, "every_param_segment"), $path);
    return "#^$regex$#";
  }
  
  /**
   * Matches every path param-segment called in path_to_regex() method and
   * does regex replacements
   *
   * @access protected
   * @param array $segment
   * @return string
   */
  protected function every_param_segment(array $segment) {
    $attribute_symbol = $segment[0][0];
    $attribute_name = isset($segment[1]) ? $segment[1] : null;

    switch($attribute_symbol) {
      case '&': return "(?<$attribute_name>[a-zA-Z0-9_-]+)"; break;
      case '*': case '+':
        $name = !empty($attribute_name) ? "?<$attribute_name>" : null;
        return "($name.$attribute_symbol)";
    }
  }
}
?>