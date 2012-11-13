<?php
namespace http;
use Inflector;

/**
 * Route class
 *
 * PHP Version 5.3+
 * @author Thomas Monzel <tm@apparat-hamburg.de>
 * @version $Revision$
 * @package Suitcase
 * @subpackage Router
 */
class Route extends Object {
  
  /**
   * Http method for acception
   *
   * @access public
   * @var array
   */
  private $method;
  
  /**
   * Path pattern for acception
   *
   * @access public
   * @var string
   */
  private $pattern;
  
  /**
   * Acception status
   *
   * @access private
   * @var boolean
   */
  private $accepted = false;
  
  /**
   * Acception requirements
   *
   * @access public
   * @var array
   */  
  public $requirements;
  
  /**
   * Passed attributes
   *
   * @access public
   * @var array
   */
  public $target;
  
  /**
   * Constructs a new route instance
   *
   * @access public
   * @param string $method
   * @param string $pattern
   * @param mixed $target
   * @param array $requirements
   */
  function __construct($method = null, $pattern = null, array $target = array(), array $requirements = array()) {
    $this->method = !empty($method) ? strtolower($method) : 'get';
    
    # add leading slash if missing
    if($pattern !== '/' and $pattern[0] !== '/') $pattern = "/$pattern";
    
    $this->target = $target;
    $this->pattern = $pattern;
    $this->requirements = $requirements;
  }
  
  /**
   * Reads the route method
   *
   * @access public
   * @return string
   */
  function method() {
    return $this->method;
  }
  
  /**
   * Reads the route pattern
   *
   * @access public
   * @return string
   */  
  function pattern() {
    return $this->pattern;
  }
  
  /**
   *
   * 
   * @access public
   * @param Request $request
   * @return boolean
   */
  function accept(Request $request) {
    return $this->accepted = true;
  }
  
  /**
   * Returns the acception status
   *
   * @access public
   * @return boolean
   */
  function accepted() {
    return $this->accepted;
  }
  
  /**
   * Array representation
   * Includes following keys: method, pattern, target, requirements
   *
   * @access public
   * @return array
   */
  function to_array() {
    return array(
      'method' => $this->method,
      'pattern' => $this->pattern,
      'target' => $this->target,
      'requirements' => $this->requirements
    );
  }
  
  /**
   * String representation
   * e.g. GET /users/&id[/\d+/] => show_user
   *
   * @access public
   * @return string
   */
  function to_string() {
    $pattern = $this->pattern;
    
    if(!empty($this->requirements)) {
      foreach($this->requirements as $param => $expression) {
        if(strpos($pattern, "&$param") !== false) {
          $pattern = str_replace("&$param", "&{$param}<$expression>", $pattern);
        }
      }
    }
    
    $to = '';
    if(isset($this->target['to'])) {
      $to = $this->target['to'];
    } elseif(isset($this->target['controller']) and $this->target['action']) {
      $to = $this->target['controller']."#".$this->target['action'];
    }
    

    if(!empty($this->target['namespace'])) {
      $to = str_replace('\\', '.', trim($this->target['namespace'], '\\')).".".$to;
    }
    
    if($to instanceof \Closure) {
      $to = '[closure]';
    }
    
    return strtoupper($this->method)." $pattern => $to";
  }
}
?>