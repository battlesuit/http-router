<?php
namespace http\action;
use http\Request;
use http\Response;
use http\transaction\Controller as TransactionController;

/**
 * Handler for request actions
 *
 * PHP Version 5.3+
 * @author Thomas Monzel <tm@apparat-hamburg.de>
 * @version $Revision$
 * @package Battlesuit
 * @subpackage http-router
 */
abstract class Controller extends TransactionController implements \ArrayAccess {
  
  /**
   * Cached controller name created in name()
   *
   * @access private
   * @var string
   */
  private $name;
  
  /**
   * Sent request
   *
   * @access protected
   * @var Request
   */
  protected $request;
  
  /**
   * Response to send
   *
   * @access protected
   * @var Response
   */
  protected $response;
  
  /**
   * Sent action name
   * Comes over _action data param
   *
   * @access protected
   * @var string
   */
  protected $action;
  
  /**
   * Action assignments
   *
   * @access protected
   * @var array
   */
  protected $assignments = array();
  
  /**
   * Request data
   *
   * @access protected
   * @var array
   */
  protected $data = array();
  
  /**
   * Redirect initialized?
   *
   * @access protected
   * @var boolean
   */
  protected $redirected = false;
  
  /**
   * Requested format (xml|js|html|txt etc.)
   * Comes over _format data param
   *
   * @access protected
   * @var string
   */
  protected $format;
  
  /**
   * Returns the underscored controller name
   *
   * @static
   * @access public
   * @return string
   */
  function name() {
    if(isset($this->name)) return $this->name;
    return $this->name = $this->compose_name();
  }
  
  /**
   * Composes the controllers name
   *
   * @access public
   * @return string
   */
  protected function compose_name() {
    $class = get_class($this);
    if(($last_backslash_pos = strrpos($class, '\\')) !== false) {
      $class = substr($class, $last_backslash_pos+1);
    }
    
    # lowerscore
    $class = strtolower(preg_replace('/(\p{Ll})(\p{Lu})/', '$1_$2', $class));
    
    return preg_replace('/(_controller|_presenter)$/', '', $class);
  }
  
  function process_transaction(Request $request) {
    $this->request = $request;
    $response = $this->response = new Response();
    
    if(isset($request->data['_action'])) {
      $this->action = $request->data['_action'];
    } else trigger_error("No action given: Please set the request data _action parameter");
    
    if(isset($request->data['_format'])) {
      $this->format = $request->data['_format'];
    }
    
    # set default content-type
    $response->content_type('text/html');
    
    $this->data = $request->data;
    $response = $this->process_action($this->action);
    if(!$response) return $this->response;
    else return $response;
  }
  
  protected function process_action($action) {
    $before_action = array('before_action');
    $except_before = array();
    
    if(!empty($this->before_action)) {
      $before_action = array_merge($before_action, (array)$this->before_action);
      
      if(isset($before_action['except'])) {
        $except_before = (array)$before_action['except'];
        unset($before_action['except']);
      }
    }
    
    foreach($before_action as $method) {
      if(array_search($action, $except_before) === false and method_exists($this, $method)) call_user_func(array($this, $method));
    }
    
    $returned_result = $this->invoke_action_method($action);
    
    $after_action = array('after_action');
    $except_after = array();
    
    if(!empty($this->after_action)) {
      $after_action = array_merge($after_action, (array)$this->after_action);
      
      if(isset($after_action['except'])) {
        $except_after = (array)$after_action['except'];
        unset($after_action['except']);
      }
    }
    
    foreach($after_action as $method) {
      if(array_search($action, $except_after) === false and method_exists($this, $method)) call_user_func(array($this, $method));
    }
    
    return $returned_result;
  }
  
  /**
   * Invokes the action method
   * 
   * @access protected
   * @param string $method
   * @return Response or Null
   */
  protected function invoke_action_method($method) {
    
    # suffix method name on no existance 
    if(!method_exists($this, $method)) $method = $method."_action";
    
    # call if method exists
    if(method_exists($this, $method)) return call_user_func(array($this, $method));
  }
  
  /**
   * Returns the current action request
   *
   * @access public
   * @return Request
   */
  function request() {
    return $this->request;
  }
  
  function assignments() {
    return $this->assignments;
  }
  
  /**
   * Assigns a controller variable
   *
   * @access public
   * @param string $name
   * @param mixed $value
   * @return mixed
   */
  function assign($name, $value) {
    return $this->assignments[$name] = $value;
  }
  
  /**
   * Obtains a controller variable
   *
   * @access public
   * @param string $name
   * @return mixed 
   */
  function obtain($name) {
    if(isset($this->assignments[$name])) return $this->assignments[$name];
  }
  
  /**
   * Returns the request environments session instance
   *
   * @access public
   * @return Session
   */
  function session() {
    return $this->request->env['session'];
  }
  
  /**
   * Redirects to location or action
   *
   * @access public
   * @param mixed $location
   * @param mixed $options
   */
  function redirect_to($location, $options = array()) {
    $url = $location;

    if(is_object($location)) {
      $url = $this->url_for($location, $options);
    } elseif(strpos($location, '/') === false) {
      $url = $this->request->base_url()."/$this";
      
      if($location !== 'index') $url .= "/$location";
      
    } elseif(strpos($location, '://') === false) {
      $url = $this->request->base_url()."/".trim($location, '/');
    }
    
    $this->response->location($url);
    $this->redirected = true;
  }
  
  /**
   * Call block on specific request format
   * 
   * @access public
   * @param mixed $formats
   * @param callable $block
   * @return boolean|mixed
   */
  function respond_to($formats, $block = null) {
    $callback = $block;
    if(is_array($formats)) {
      foreach($formats as $format => $callback) {       
        if($this->accept_format($format)) goto accepted;
      }
    } elseif(is_string($formats)) {
      if(strpos($formats, '|') !== false) {
        $formats = explode('|', $formats);
        foreach($formats as $format => $callback) {
          $callback = $block;
          if($this->accept_format($format)) goto accepted;
        }
      } else {
        if($this->accept_format($formats)) goto accepted;
      }
    }
    
    return false;
    
    accepted:
    return call_user_func($callback);
  }
  
  /**
   * 
   * 
   */
  function accept_format($format) {
    if(isset($this->format) and $this->format == $format) return true;
    elseif($this->request->accepts_format($format)) return true;
    
    return false;
  }
  
  function base_path() {
    
  }
  
  function url_for($to, $options = array()) {
    $url = $this->request->base_url().$this->base_path();
    
    if(is_object($to) and method_exists($to, 'to_path')) {
      $url .= $to->to_path();
    } elseif(is_string($to)) {
      if($to[0] !== '/') $to = "/$to";
      $url .= $to;
    }
    
    return $url;
  }
  
  /**
   * To-string conversion returns the controllers name
   *
   * @access public
   * @return string
   */
  function __toString() {
    return $this->name();
  }
  
  /**
   * ArrayAccess::offsetSet() implementation
   *
   * @access public
   * @param string $name
   * @param mixed $value
   */
  function offsetSet($name, $value) {
    return $this->assign($name, $value);
  }

  /**
   * ArrayAccess::offsetUnset() implementation
   *
   * @access public
   * @param string $name
   */
  function offsetUnset($name) {
    unset($this->assignments[$name]);
  }

  /**
   * ArrayAccess::offsetGet() implementation
   *
   * @access public
   * @param string $name
   * @return mixed
   */
  function offsetGet($name) {
    return $this->obtain($name);
  }

  /**
   * ArrayAccess::offsetExists() implementation
   *
   * @access public
   * @param string $name
   * @return boolean
   */
  function offsetExists($name) {
    return array_key_exists($name, $this->assignments);
  }
}
?>