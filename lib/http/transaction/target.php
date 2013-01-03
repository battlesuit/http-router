<?php
namespace http\transaction;
use http\Request;

/**
 * Compiles the given data to a callable transaction processor
 *
 * Example
 *  $r = new Request('http://localhost/path/to/resource');
 * 
 *  $t = new Target('users#index');
 *  $t->compile($r, $processor);
 *
 *  $processor($r); # => http\Response instance
 *
 * PHP Version 5.3+
 * @author Thomas Monzel <tm@apparat-hamburg.de>
 * @version $Revision$
 * @package Battlesuit
 * @subpackage http-router
 */
class Target extends Application {
  
  /**
   * Target components to dispatch
   *
   * @access protected
   * @var array
   */
  protected $target;
  
  /**
   * Constructs a new request dispatcher instance
   * 
   * @access public
   * @param mixed $target
   */
  function __construct($target) {
    if(!is_array($target) or is_callable($target)) $target = array('to' => $target);
    $this->target = $target;
  }
  
  /**
   * Reads the target components
   *
   * @access public
   * @return array
   */
  function target() {
    return $this->target;
  }
  
  /**
   * Returns the 
   * 
   */
  function to() {
    return $this->target['to'];
  }
  
  /**
   * Processes the request and generates a response
   * If the target has a location attribute a redirect is initialized
   *
   * @access public
   * @param Request $request
   * @return Response
   */
  function process(Request $request) {
    if(isset($this->target['location'])) {
      $location = $this->target['location'];
      foreach($request->data as $name => $value) {
        if(strpos($name, '_') === 0) {
          $name = substr($name, 1);
          $location = str_replace("&$name", $value, $location);
        }
      }
      
      $response = new Response(301);
      if(strpos($location, 'http://') !== false) {
        $response->location($location);
      } else $response->location($request->base_url()."/".trim($location, '/'));
      
      return $response;
    }
    
    $this->compile($request, $processor);
    
    if(is_callable($processor)) {
      return parent::process($request);
    } else throw new \ErrorException("Invalid target or target does not exist");
  }
  
  /**
   * Compiles the request and returns a processor callable
   *
   * @access public
   * @param Request $request
   * @param callable &$processor
   * @return callable
   */
  function compile(Request $request, &$processor = null) {
    $to = $action = $base_dir = null;
    extract($this->target);
    
    if(is_string($to)) {
      if(!empty($namespace)) $processor = "$namespace\\$to";
      else $processor = $to;
      
      if(is_callable($processor)) {    
        goto end;
      } elseif(strpos($to, '#') !== false) {
        
        # short callback
        list($controller, $action) = explode('#', $to);
        if(strpos($controller, '.')) {
          
          # read and normalize dotted namespace from controller name
          $namespace = str_replace('.', '\\', substr($controller, 0, strrpos($controller, '.')));
          $controller = substr(strrchr($controller, '.'), 1);
        }
      }
      
    } elseif(is_callable($to)) {
      # closure callback
      $processor = $to;
      goto end;
    }
    
    $controller_class = null;
    if(isset($controller)) {
      if(!empty($alias)) {
        $controller = sprintf($alias, $controller);
      }
      
      # pascalize controller => ClassName
      $controller_class = str_replace(' ', '', ucwords(preg_replace('/(_|-)+/', ' ', $controller)));
    }
    
    if(isset($namespace)) {
      $controller_class = "$namespace\\$controller_class";
    }

    if(!class_exists($controller_class, true)) throw new \ErrorException("Controller class does not exist");

    $processor = "$controller_class::handle_transaction";
    
    end:
    if(!empty($action)) $request->data['_action'] = $action;
    return $this->processor = $processor;
  }
}
?>