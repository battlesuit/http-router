<?php
namespace http\router;
use http\Request;

class RouteTest extends TestUnit {
  function set_up() {
    $this->load_fixtures('route_targets');
  }
  
  function test_defaults() {
    $route = new Route();
    $this->assert_eq($route->method(), 'get');
    $this->assert_eq($route->pattern(), '/');
    $this->assert_empty($route->target);
    $this->assert_empty($route->requirements);
    $this->assert_false($route->accepted());
  }
  
  function test_construction_with_method() {
    $route = new Route('/', 'get');
    $this->assert_eq($route->method(), 'get');
  }
  
  function test_construction_with_uppercased_method() {
    $route = new Route('/', 'POST');
    $this->assert_eq($route->method(), 'post');
  }
  
  function test_construction_with_pattern() {
    $route = new Route('/foo/bar');
    $this->assert_eq($route->pattern(), '/foo/bar');
  }
  
  function test_construction_with_pattern_without_leading_slash() {
    $route = new Route('foo/bar');
    $this->assert_eq($route->pattern(), '/foo/bar');
  }
  
  function test_construction_with_slash_as_pattern() {
    $route = new Route('/');
    $this->assert_eq($route->pattern(), '/');
  }
  
  function test_construction_with_target() {
    $route = new Route('/', 'get', array('to' => 'function'));
    $this->assert_eq($route->target['to'], 'function');
  }
  
  function test_construction_with_requirements() {
    $route = new Route('/', 'get', array(), array('name' => '/[a-z]+/'));
    $this->assert_eq($route->requirements['name'], '/[a-z]+/');
  }
  
  function test_to_array() {
    $expectation = array('pattern' => '/users/&id/edit', 'method' => 'get', 'target' => array('to' => 'function'), 'requirements' => array('id' => '/\d+/'));
    $route = new Route('/users/&id/edit', 'get', array('to' => 'function'), array('id' => '/\d+/'));
    $this->assert_eq($route->to_array(), $expectation);
  }
  
  function test_to_string() {
    $expectation = 'GET /users/&id</\d+/> => show_user';
    $route = new Route('/users/&id', 'get', array('to' => 'show_user'), array('id' => '/\d+/'));
    $this->assert_eq("$route", $expectation);
  }
  
  function test_to_string_with_closure() {
    $expectation = 'DELETE /users/&id</\d+/>/foo/bar => [closure]';
    $route = new Route('/users/&id/foo/bar', 'delete', array('to' => function() {}), array('id' => '/\d+/'));
    $this->assert_eq("$route", $expectation);
  }
  
  function test_acception() {
    $route = new Route('/');
    $route->accept(new Request());
    $this->assert_true($route->accepted());
  }
}
?>