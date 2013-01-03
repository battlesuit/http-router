<?php
namespace http;
use http\router\RouteCollection;
use http\router\Scope;
if(defined('loader\available')) require __DIR__."/autoload.php";
require __DIR__."/functions.php";

/**
 * Base routing application
 *
 * PHP Version 5.3+
 * @author Thomas Monzel <tm@apparat-hamburg.de>
 * @version $Revision$
 * @package Battlesuit
 * @subpackage http-router
 */
class Router {
  
  /**
   * Collection of all routes
   *
   * @access protected
   * @var RouteCollection
   */
  protected $routes;
  
  /**
   * Constructs a new router instance
   *
   * @access public
   * @param RouteCollection $routes
   */
  function __construct(RouteCollection $routes = null) {
    $this->routes = isset($routes) ? $routes : new RouteCollection();
  }
  
  /**
   * Invoke router as transaction application
   *
   * @access public
   * @param Request $request
   * @return Response
   */
  function __invoke(Request $request) {
    return $this->route_request($request);
  }
  
  /**
   * Returns the route collection
   *
   * @access public
   * @return RouteCollection
   */
  function routes() {
    return $this->routes;
  }

  /**
   * Runs a route transaction if a route was accepted
   * otherwise it will return a 404 response
   * 
   * @access public
   * @param Request $request
   * @return Response
   */
  function route_request(Request $request) {    
    if($this->accept_route($request, $route)) {
      try {
        $response = transaction\Target::run($route->target, $request)->response();
      } catch(router\Error $e) {
        return new Response(404, "Routing Error: ".$e->getMessage());
      }
      
      return $response;
    }
    
    return new Response(404, "No routes matched ".strtoupper($request->method())." ".$request->resource_path());
  }
  
  /**
   * Opens a new route scope and finalizes it
   *
   * @access public
   * @param mixed $locals
   * @param callable $block
   */
  function draw_routes($locals, $block = null) {
    if(is_callable($locals)) {
      $block = $locals;
      $locals = array();
    }
    
    $scope = new Scope($locals, $this->routes, $block);
    return $scope->finalize();
  }
  
  /**
   * Accepting a http-request
   *
   * @access public
   * @param Request $request
   * @param Route $accepted_route
   */
  function accept_route(Request $request, &$accepted_route = null) {
    $route = $this->routes->accept($request);

    if($route !== false) {
      $accepted_route = $route;
      return true;
    } else return false;
  }
}
?>