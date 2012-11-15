<?php
namespace http\route;
use Inflector;
use http\Object;
use http\Route;

/**
 * Compositional route-scoper class
 *
 * PHP Version 5.3+
 * @author Thomas Monzel <tm@apparat-hamburg.de>
 * @version $Revision$
 * @package Suitcase
 * @subpackage Router
 */
class Scope extends Object {
  
  /**
   * Route collection
   *
   * @access protected
   * @var RouteCollection
   */
  protected $routes;
  
  /**
   * Parent scope block
   *
   * @access protected
   * @var callable
   */
  protected $block;
  
  /**
   * Scope locals
   *
   * @access protected
   * @var array
   */
  protected $locals = array();
  
  /**
   * Parents of this scope
   *
   * @access protected
   * @var array
   */
  protected $parents = array();
  
  /**
   * All applied childscopes
   *
   * @access protected
   * @var array
   */
  protected $children = array();
  
  /**
   * Hierarchy depth
   *
   * @access protected
   * @var int
   */
  protected $depth = 0;
  
  /**
   * Full scope path
   *
   * @access private
   * @var string
   */
  private $path;

  /**
   * All scope requirements
   *
   * @access private
   * @var array
   */
  private $requirements = array();
  
  /**
   * Full scope namespace
   *
   * @access private
   * @var string
   */
  private $namespace;
  
  private $alias;
  private $load_dir;
  
  /**
   * Finalization state
   * 
   * @access private
   * @var boolean
   */
  private $finalized = false;
  
  /**
   * Constructs a new route scope instance
   *
   * @access public
   * @param array $locals
   * @param Collection $routes
   * @param callable $block
   */
  function __construct($locals = array(), Collection $routes = null, $block = null) {
    if(!is_array($locals)) $locals = array('path' => $locals);
    $this->routes = !isset($routes) ? new Collection() : $routes;
    $this->block = $block;
    $this->locals = $locals;
    
    if(!empty($locals['requirements'])) {
      $this->requirements = $locals['requirements'];
    }
  }
  
  /**
   * Shortcut for scope()
   *
   * @access public
   * @param mixed $locals
   * @param callable $block
   */
  function __invoke($locals, $block = null) {
    return $this->scope($locals, $block);
  }
  
  function depth() {
    return $this->depth;
  }
  
  function level() {
    return $this->depth+1;
  }
  
  function parent() {
    if(isset($this->parents[$this->depth-1])) return $this->parents[$this->depth-1];
  }
  
  function has_parent() {
    return array_key_exists($this->depth, $this->parents);
  }
  
  /**
   * Calls all blocks and builds the scope
   *
   * @access public
   * @return RouteScope
   */
  function finalize() {
    if($this->finalized) return $this;
    
    if($this->depth === 0 and isset($this->block)) $this->call();
    
    foreach($this->children as $scope) $scope->call()->finalize();
    $this->finalized = true;
    return $this;
  }
  
  /**
   * Calls the given scope-block
   *
   * @access public
   * @return RouteScope
   */
  function call() {
    $parent_path = '';
    $parent_namespace = '';
    foreach($this->parents as $parent) {
      $parent_path .= $parent->local('path');
      $controller = $parent->local('controller');
      $member = $parent->local('member');
      $ns = $parent->local('namespace');
      
      if(empty($parent_namespace) or $ns[0] === '\\') $parent_namespace = $ns;
      elseif(!empty($ns) and !empty($parent_namespace)) $parent_namespace = "$parent_namespace\\$ns";

      
      if(!empty($controller)) {
        $parent_path .= "/$controller";
      }
      
      if($member and $controller) {
        $inflected_name = Inflector::singularize($controller);
        $parent_path .= "/&{$inflected_name}_id";
        $this->requirements[$inflected_name."_id"] = '/\d+/';
      } elseif($parent->is_member()) {
        $inflected_name = Inflector::singularize($parent->parent()->local('controller'));
        $parent_path .= "/&{$inflected_name}_id";
        $this->requirements[$inflected_name."_id"] = '/\d+/';
      }
      
      $parent_alias = $parent->local('alias');
      if(!empty($parent_alias)) $this->alias = $parent_alias;
      
      $parent_load_dir = $parent->local('load_dir');
      if(!empty($parent_load_dir)) $this->load_dir = $parent_load_dir;
    }
    
    $this->path = $parent_path.$this->local('path');
    
    $local_namespace = $this->local('namespace');
    if($local_namespace[0] === '\\') $this->namespace = $local_namespace;
    else $this->namespace = $parent_namespace.(!empty($local_namespace) ? "\\$local_namespace" : '');
    
    $this->namespace = trim($this->namespace, '\\');
    
    if(isset($this->block)) call_user_func($this->block, $this);
    return $this;
  }
  
  /**
   * Returns the collection
   *
   * @access public
   * @return RouteCollection
   */
  function routes() {
    return $this->routes;
  }
  
  function add_scope(Scope $scope) {
    $parents = $this->parents;
    $parents[] = $this;
    
    $scope->parents = $parents;
    $scope->depth = $this->depth+1;
    $scope->requirements = array_merge($this->requirements, $scope->requirements);
    return $this->children[] = $scope;
  }
  
  /**
   * Matches a route at root path => /
   *
   * @access public
   * @param mixed $options
   */
  function to($target) {
    return $this->match('/', $target);
  }
  
  /**
   * Does an attribute is present
   *
   * @access public
   * @param string $name
   * @return boolean
   */
  function local_present($name) {
    return !empty($this->locals[$name]);
  }
  
  /**
   * Pushes a route to the collection
   *
   * @access public
   * @param string $method
   * @param string $pattern
   * @param array $target
   * @param array $requirements
   * @return Route
   */
  function push_route($method, $pattern, array $target = array(), array $requirements = array()) {
    $route = new Route($method, $pattern, $target, array_merge($this->requirements, $requirements));
    $this->routes->push($route);
    return $route;
  }
  
  /**
   * Main match worker function
   *
   * @access public
   * @param mixed $conditions
   * @param mixed $options
   * @param mixed $target
   * @return RouteScope
   */
  function match($conditions, $options, $target = null) {
    if(!isset($target)) {
      $target = $options;
      $options = array();
    }
    
    $requirements = $this->find_requirements_of($options);
    $this->conform_arguments($options, $target, $conditions);
    
    # apply default method => get
    if(empty($conditions['method'])) {
      $conditions['method'] = 'get';
    }
    
    if(!empty($conditions['via'])) {
      $via = $conditions['via'];
      unset($conditions['via']);
      foreach((array)$via as $method) {
        $this->push_route($method, $conditions['path'], $target, $requirements);
      }

      return;
    }

    extract($conditions);

    $this->push_route($method, $path, $target, $requirements);
    return $this;
  }
  
  function redirect($conditions, $url) {
    return $this->match($conditions, array('location' => $url));
  }
  
  protected function conform_arguments(&$options, &$target, &$conditions) {
    $this->conform_conditions($conditions, $options);
    $this->conform_target($target, $options);
  }
  
  protected function find_requirements_of(array $options) {
    $requirements = $this->requirements;
    if(!empty($options['&'])) $requirements = array_merge($requirements, $options['&']);
    
    if((isset($options['member']) and $options['member'] !== false) or $this->is_member()) {
      $requirements['id'] = '/\d+/';
    }
    
    return $requirements;
  }

  /**
   * Match via given method e.g. get, post, put, delete
   *
   * @access public
   * @param mixed $methods
   * @param mixed $paths
   * @param mixed $target
   * @param array $requirements
   * @return RouteScope
   */
  function match_via($methods, $paths, $target, array $options = array()) {
    $methods = (array)$methods;
    $paths = (array)$paths;
    
    $requirements = $this->find_requirements_of($options);
    
    foreach($methods as $method) {
      foreach($paths as $path) {
        $conditions = compact('path', 'method');
        $this->conform_arguments($options, $target, $conditions);
        extract($conditions);
        $this->push_route($method, $path, $target, $requirements);
      }
    }
    
    return $this;
  }
  
  /**
   * Scalar route drawing
   *
   * @access public
   * @param array $routes
   * @return RouteScope
   */
  function match_paths(array $routes) {
    foreach($routes as $path => $target) $this->match($path, $target);
    return $this;
  }
  
  /**
   * Conforms matched conditions to an routable array
   *
   * @access protected
   * @param mixed $conditions
   * @return array conformed conditions
   */ 
  protected function conform_conditions(&$conditions, array $options = array()) {
    if(is_string($conditions)) $conditions = array('path' => $conditions);
    elseif(is_array($conditions) and isset($conditions[0])) {
      $indexed_conditions = $conditions;
      $conditions = array('path' => $indexed_conditions[0]);
      if(isset($indexed_conditions[1])) {
        if(is_array($indexed_conditions[1])) $conditions['via'] = $indexed_conditions[1];
        else $conditions['method'] = $indexed_conditions[1];
      } elseif(isset($indexed_conditions['via'])) {
        $conditions['via'] = $indexed_conditions['via'];
      }
    }
    
    $path = '';
    extract($conditions);
    
    $local_member = (bool)$this->local('member');
    $local_controller = $this->local('controller');
    $local_path = '';
    
    
    if(!empty($local_controller)) {
      $local_path  .= "/$local_controller";
    }
      
    if($local_member or (isset($options['member']) and $options['member'] !== false)) {
      $local_path .= "/&id";
    }
    
    $conditions['path'] = $this->path.$local_path.$path;
    return $conditions;
  }
  
  function is_member() {
    return (bool)$this->local('member');
  }
  
  /**
   * Conforms a target definition to an routable array
   *
   * @access protected
   * @param mixed $target
   * @return array conformed target
   */
  protected function conform_target(&$target, array $options = array()) {
    if(is_string($target) or is_callable($target)) $target = array('to' => $target);
    $namespace = $controller = null;
    extract($target);

    if(!empty($this->namespace)) {
      $target['namespace'] = $this->namespace.(!empty($namespace) ? ".$namespace" : '');
    }
    
    if(empty($controller) and $this->local_present('controller')) {
      $target['controller'] = $this->locals['controller'];
    }
    
    if($this->is_member()) {
      $target['controller'] = $this->parent()->local('controller');
    }
    
    if(!empty($alias)) {
      $target['alias'] = $alias;
    } elseif($this->local_present('alias')) {
      $target['alias'] = $this->local('alias');
    } elseif(!empty($this->alias)) {
      $target['alias'] = $this->alias;
    }
    
    if(!empty($load_dir)) {
      $target['load_dir'] = $load_dir;
    } elseif($this->local_present('load_dir')) {
      $target['load_dir'] = $this->local('load_dir');
    } elseif(!empty($this->load_dir)) {
      $target['load_dir'] = $this->load_dir;
    }
    
    return $target;
  }
  
  /**
   * 
   * 
   */
  function under($namespace, $locals, $block = null) {
    $scope = $this->scope($locals, $block);
    $scope->local('namespace', $namespace);    
    return $scope;
  }
  
  /**
   * Match GET-request
   *
   * @access public
   * @param mixed $paths
   * @param mixed $target
   * @param array $options
   * @return RouteScope
   */
  function get($paths, $target, array $options = array()) {
    return $this->match_via('get', $paths, $target, $options);
  }
  
  /**
   * Match POST-request
   *
   * @access public
   * @param mixed $paths
   * @param mixed $target
   * @param array $options
   * @return RouteScope
   */  
  function post($paths, $target, array $options = array()) {
    return $this->match_via('post', $paths, $target, $options);
  }
  
  /**
   * Match DELETE-request
   *
   * @access public
   * @param mixed $paths
   * @param mixed $target
   * @param array $options
   * @return RouteScope
   */  
  function delete($paths, $target, array $options = array()) {
    return $this->match_via('delete', $paths, $target, $options);
  }
  
  /**
   * Match PUT-request
   *
   * @access public
   * @param mixed $paths
   * @param mixed $target
   * @param array $options
   * @return RouteScope
   */  
  function put($paths, $target, array $options = array()) {
    return $this->match_via('put', $paths, $target, $options);
  }
  
  /**
   * Converts all appended routes to a indexed array
   *
   * @access public
   * @return array
   */
  function to_array() {
    return $this->routes->to_array();
  }
  
  function to_string() {
    return $this->routes->to_string();
  }

  
  /**
   * Reads and writes a local variable
   * 
   */
  function local($name, $value = null) {
    if(isset($value)) return $this->locals[$name] = $value;
    elseif($this->local_present($name)) return $this->locals[$name];
  }
  
  /**
   * Opens a resource scope with its default routes
   *
   * @access public
   * @param string $name
   * @param mixed $options
   * @param callable $block
   * @return RouteScope
   */
  function resource($name, $locals = array(), $block = null) {
    if(is_callable($locals)) {
      $block = $locals;
      $locals = array();
    } elseif(!is_array($locals)) $locals = array('path' => $locals);
    
    $resource = $this->controller($name, $locals, function($resource) {
      $resource->get("(.&format)", array('action' => 'index'));
      $resource->post("(.&format)", array('action' => 'create'));

      $resource->get("/add(.&format)", array('action' => 'add'));
      //$resource->post("/add(.&format)", array('action' => 'create'));
      
      $resource->member(function($member) {
        $member->get("/edit(.&format)", array('action' => 'edit'));
        $member->delete("(.&format)", array('action' => 'destroy'));
      
        $member->get("(.&format)", array('action' => 'show'));
        //$member->put("/edit(.&format)", array('action' => 'update'));
        $member->put("(.&format)", array('action' => 'update'));
      });
    });
    
    if(isset($block)) {
      $resource = $this->controller($name, $locals, $block);
    }
    
    return $resource;
  }
  
  /**
   * Append many resources by args
   *
   * @access public
   */
  function resources() {
    foreach(func_get_args() as $name) $this->resource($name);
  }
  
  /**
   * Opens a controller scope
   * Every scope adds the name attribute as path e.g. "/$name"
   *
   * @access public
   * @param string $name
   * @param mixed $options
   * @param callable $block
   */
  function controller($name, $locals, $block = null) {
    $scope = $this->scope($locals, $block);
    $scope->local('controller', $name);
    return $scope;
  }
  
  /**
   * Opens a member scope
   *
   * @access public
   * @param callable $block
   */
  function member($block) {
    return $this->scope(array('member' => true), $block);
  }
  
  /**
   * Opens a new routing scope
   *
   * @access public
   * @param mixed $attributes
   * @param callable $block
   */
  function scope($locals, $block = null) {
    if(is_callable($locals)) {
      $block = $locals;
      $locals = array();
    } elseif(!is_array($locals)) $locals = array('path' => $locals);

    return $this->add_scope(new self($locals, $this->routes, $block));
  }
  
  # alias for scope()
  function wrap($locals, $block = null) {
    return $this->scope($locals, $block);
  }
}
?>