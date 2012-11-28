<?php
namespace http\route;
use http\Request;

/**
 * Main holder of routes
 * Sorts and prepares the routestack for acception
 *
 * PHP Version 5.3+
 * @author Thomas Monzel <tm@apparat-hamburg.de>
 * @version $Revision$
 * @package Battlesuit
 * @subpackage http-router
 */
class Collection implements \ArrayAccess, \Iterator, \Countable {
  
  /**
   * Route stack containing all applied route instances
   *
   * @access private
   * @var array
   */
  private $stack = array();
  
  /**
   * Sorted indexes for quick-stack-access
   * Defined and appended in push() method
   * 
   * @access private
   * @var array
   */
  private $sorted_indexes = array();
  
  /**
   * Pushing a route to the stack
   *
   * @access public
   * @param Object $route
   * @return int
   */
  function push(Object $route) {
    $count = array_push($this->stack, $route);
    $path_length = substr_count($route->pattern(), '/');
    $this->sorted_indexes[$route->method()][$path_length][] = $count-1;
    return $count;
  }
  
  /**
   * Resets the stack
   *
   * @access public
   */
  function reset() {
    $this->stack = array();
  }
  
  /**
   * Accept a route to acceptable params
   *
   * @access public
   * @param Request $request
   * @param callable $accept_by
   * @return boolean
   */
  function accept(Request $request, $accept_by = 'http\route\Acceptor::accept_route') {
    $method = $request->method();
    $path_lengths = array();
    
    if(!empty($this->sorted_indexes[$method])) {
      $path_lengths = $this->sorted_indexes[$method];
    }
    
    krsort($path_lengths);
    
    foreach($path_lengths as $indexes) {
      foreach($indexes as $index) {
        $route = $this->stack[$index];
        
        if(call_user_func($accept_by, $route, $request)) {
          $route->accept($request);
          return $route;
        }      
      }
    }
    
    return false;
  }
  
  /**
   * Picks a route at index position
   *
   * @access public
   * @param int $index
   * @return Route
   */
  function route_at($index) {
    if($this->route_exists($index)) return $this->stack[$index];
  }
  
  # alias function for route_at
  function pick($index) { return $this->route_at($index); }
  
  /**
   * Does a route exists at index?
   *
   * @access public
   * @param int $index
   * @return boolean
   */
  function route_exists($index) {
    return array_key_exists($index, $this->stack);
  }
  
  /**
   * Counting routes
   *
   * @access public
   * @return int
   */
  function count() {
    return count($this->stack);
  }
  
  /**
   * Convert collection into an array
   *
   * @access public
   * @return array
   */
  function to_array() {
    $routes = array();
    foreach($this as $route) $routes[] = $route->to_array();
    return $routes;
  }
  
  /**
   * List all routes line by line as strings
   * 
   * @access public
   * @return string
   */
  function __toString() {
    $routes = '';
    foreach($this as $route) $routes .= "$route\n";
    return $routes;
  }
 
  /**
   * Iterator::rewind() implementation
   * Initializes the iteration process
   *
   * @access public
   */
  function rewind() {
    reset($this->stack);
  }

  /**
   * Iterator::current() implementation
   * Returns the current pointers value
   *
   * @access public
   * @return string
   */
  function current() {
    return current($this->stack);
  }

  /**
   * Iterator::key() implementation
   * Returns the current pointers key
   *
   * @access public
   * @return string
   */
  function key() {
    return key($this->stack);
  }

  /**
   * Iterator::next() implementation
   * After the loop body of each iteration is processed this method is called
   * Afterwards the process jumps to valid() etc. etc.
   *
   * @access public
   */
  function next() {
    next($this->stack);
  }

  /**
   * Iterator::valid() implementation
   * Called before each iteration
   * If false is returned the loop instantly breaks
   * If true is returned current() and key() gets called afterwards
   *
   * @access public
   * @return boolean
   */
  function valid() {
    return key($this->stack) !== null;
  }
  
  /**
   * ArrayAccess::offsetSet() implementation
   *
   * @access public
   * @param string $field_name
   * @param mixed $value
   */
  function offsetSet($index, $route) {
    $this->stack[$index] = $route;
  }

  /**
   * ArrayAccess::offsetUnset() implementation
   *
   * @access public
   * @param int $index
   */
  function offsetUnset($index) {
    unset($this->stack[$index]);
  }

  /**
   * ArrayAccess::offsetGet() implementation
   *
   * @access public
   * @param int $index
   * @return string
   */
  function offsetGet($index) {
    return $this->route_at($index);
  }

  /**
   * ArrayAccess::offsetExists() implementation
   *
   * @access public
   * @param int $index
   * @return boolean
   */
  function offsetExists($index) {
    return $this->route_exists($index);
  }
}
?>