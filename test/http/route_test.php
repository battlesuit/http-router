<?php
namespace http;

class RouteTest extends TestCase {
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
    $this->assert_eq((new Route('get'))->method(), 'get');
  }
  
  function test_construction_with_uppercased_method() {
    $this->assert_eq((new Route('POST'))->method(), 'post');
  }
  
  function test_construction_with_pattern() {
    $this->assert_eq((new Route(null, '/foo/bar'))->pattern(), '/foo/bar');
  }
  
  function test_construction_with_pattern_without_leading_slash() {
    $this->assert_eq((new Route(null, 'foo/bar'))->pattern(), '/foo/bar');
  }
  
  function test_construction_with_slash_as_pattern() {
    $this->assert_eq((new Route(null, '/'))->pattern(), '/');
  }
  
  function test_construction_with_target() {
    $this->assert_eq((new Route(null, null, ['to' => 'function']))->target['to'], 'function');
  }
  
  function test_construction_with_requirements() {
    $this->assert_eq((new Route(null, null, [], ['name' => '/[a-z]+/']))->requirements['name'], '/[a-z]+/');
  }
  
  function test_to_array() {
    $expectation = ['method' => 'get', 'pattern' => '/users/&id/edit', 'target' => ['to' => 'function'], 'requirements' => ['id' => '/\d+/']];
    $route = new Route('get', '/users/&id/edit', ['to' => 'function'], ['id' => '/\d+/']);
    $this->assert_eq($route->to_array(), $expectation);
  }
  
  function test_to_string() {
    $expectation = 'GET /users/&id</\d+/> => show_user';
    $route = new Route('get', '/users/&id', ['to' => 'show_user'], ['id' => '/\d+/']);
    $this->assert_eq("$route", $expectation);
  }
  
  function test_to_string_with_closure() {
    $expectation = 'DELETE /users/&id</\d+/>/foo/bar => [closure]';
    $route = new Route('delete', '/users/&id/foo/bar', ['to' => function() {}], ['id' => '/\d+/']);
    $this->assert_eq("$route", $expectation);
  }
  
  function test_acception() {
    $route = new Route('get', '/');
    $route->accept(new Request());
    $this->assert_true($route->accepted());
  }
}
?>