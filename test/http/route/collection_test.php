<?php
namespace http\route;
use http\Route;
use http\TestCase;

class CollectionTest extends TestCase {
  function set_up() {
    $this->routes = new Collection();
  }
  
  function fill_with_routes() {
    for($num = 1; $num < 11; $num++) $this->routes->push(new Route());
  }
  
  function test_count() {
    $this->fill_with_routes();
    
    $counted = count($this->routes);
    $this->assert_equality($counted, 10);
  }
  
  function test_route_exists() {
    $this->fill_with_routes();
    
    $exists = $this->routes->route_exists(10);
    $this->assert_false($exists);
    
    $exists = $this->routes->route_exists(9);
    $this->assert_true($exists);
  }
  
  function test_route_at() {
    $this->fill_with_routes();
    
    $route = $this->routes->route_at(10);
    $this->assert_null($route);
    
    $route = $this->routes->route_at(9);
    $this->assert_instanceof($route, 'http\Route');
  }
  
  function test_iteration() {
    $this->fill_with_routes();
    
    $iteration_count = 0;
    foreach($this->routes as $index => $route) {
      $this->assert_instanceof($route, 'http\Route');
      $iteration_count++;
    }
    
    $this->assert_equality($iteration_count, 10);
  }
  
  function test_array_access() {
    $this->fill_with_routes();
    
    $this->assert_true(isset($this->routes[0]));
    $this->assert_true(isset($this->routes[2]));
    $this->assert_false(isset($this->routes[10]));
    
    $this->assert_instanceof($this->routes[7], 'http\Route');
    
    $this->routes[5] = null;
    $this->assert_null($this->routes[5]);
    
    unset($this->routes[5]);
    $this->assert_false($this->routes->route_exists(5));

    $this->assert_equality(count($this->routes), 9);
  }
}
?>