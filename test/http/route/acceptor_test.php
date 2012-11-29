<?php
namespace http\route;
use http\Request;
use http\TestCase;

class AcceptorTest extends TestCase {
  function set_up() {
    $this->acceptor = new Acceptor();
  }
  
  function assert_true_acception($route, $request) {
    $accepted = $this->acceptor->accept($route, $request);
    $this->assert_true($accepted);
  }
  
  function assert_false_acception($route, $request) {
    $accepted = $this->acceptor->accept($route, $request);
    $this->assert_false($accepted);
  }
  
  function assert_target_data_eq($request_data, $name, $expectation) {
    $this->assert_key_exists($name, $request_data);
    $this->assert_eq($request_data[$name], $expectation);
  }
  
  function test_default_acception_success() {
    $this->assert_true_acception(new Object(), new Request('http://example.de'));
  }
  
  function test_default_acception_failure() {
    $this->assert_false_acception(new Object(), new Request('http://example.de', 'delete'));
  }
  
  function test_method_acception() {
    $this->assert_true_acception(new Object('delete'), new Request('http://example.de', 'delete'));
  }
  
  function test_root_pattern_acception_with_domain_url() {
    $this->assert_true_acception(new Object('delete', '/'), new Request('http://example.de', 'delete'));
  }
  
  function test_root_pattern_acception_with_domain_path_url() {
    $this->assert_true_acception(new Object('post', '/foo/bar'), new Request('http://example.de/foo/bar', 'post'));
  }
  
  function test_root_pattern_acception_with_path_info_url() {
    $this->assert_true_acception(new Object('post', '/foo/bar'), new Request('http://example.de/file.php/foo/bar', 'post'));
  }
  
  function test_glob_acception_without_param_name() { 
    $this->assert_true_acception(new Object('get', '/foo*'), new Request('http://example.de/file.php/foo/bar/hello_a/asd-dad'));
  }
  
  function test_many_globs_acception_without_variable() {
    $route = new Object('get', '/*/between/*/hello_world/*');
    $this->assert_true_acception($route, new Request('http://example.de/file.php/foo/BAR/tom123/between/my/ass/hello_world/hello_baby/users/add'));
    $this->assert_false_acception($route, new Request('http://example.de/file.php/foo/BAR/tom123/betwe/my/ass/hello_world/hello_baby/users/add'));
    $this->assert_true_acception($route, new Request('http://example.de/foo/BAR/tom123/between/my/ass/hello_world/hello_baby/users/add'));
  }
  
  function test_glob_acception_with_param_name() {
    $route = new Object('get', '/foo/*page');
    $request = new Request('http://example.de/file.php/foo/bar/hello_a/asd-dad');
    $this->assert_true_acception($route, $request);

    $this->assert_target_data_eq($request->data, '_page', 'bar/hello_a/asd-dad');
  }
  
  function test_glob_between_acception_with_param_name() {
    $route = new Object('get', '/foo/*bar/&tom');
    $request = new Request('http://example.de/foo/BAR/hello-super/tom123');
    $this->assert_true_acception($route, $request);
    $this->assert_target_data_eq($request->data, '_bar', 'BAR/hello-super');
    $this->assert_target_data_eq($request->data, '_tom', 'tom123');
  }
  
  function test_many_globs_acception_with_params() {
    $route = new Object('get', '/foo/*bar/&tom/*mel');
    $request = new Request('http://example.de/foo/BAR/hello-super/tom123/globbed/path');
    $this->assert_true_acception($route, $request);
    $this->assert_target_data_eq($request->data, '_bar', 'BAR/hello-super/tom123');
    $this->assert_target_data_eq($request->data, '_tom', 'globbed');
    $this->assert_target_data_eq($request->data, '_mel', 'path');
  }
  
  function test_many_path_globs_acception_again() {
    $route = new Object('get', '/foo/*bar/tom/*mel');
    $request = new Request('http://example.de/index.php/foo/BAR/hello-super/tom/glob-bed/path_underscored');
    $this->assert_true_acception($route, $request);
    
    $this->assert_target_data_eq($request->data, '_bar', 'BAR/hello-super');
    $this->assert_target_data_eq($request->data, '_mel', 'glob-bed/path_underscored');
  }
  
  function test_acception_with_optionals() {
    $route = new Object('get', '/foo(/&bar/&tom)');
    $request = new Request('http://example.de/index.php/foo/BAR/tom123');
    $this->assert_true_acception($route, $request);
    
    $this->assert_target_data_eq($request->data, '_bar', 'BAR');
    $this->assert_target_data_eq($request->data, '_tom', 'tom123');
    
    $this->assert_false_acception($route, new Request('http://example.de/foo/BAR'));
    $this->assert_true_acception($route, new Request('http://example.de/foo'));
  }
  
  function test_acception_with_optionals_in_between() {
    $route = new Object('get', '/foo(/&bar)/&tom');
    $request = new Request('http://example.de/index.php/foo/BAR/tom123');
    $this->assert_true_acception($route, $request);
    
    $this->assert_target_data_eq($request->data, '_bar', 'BAR');
    $this->assert_target_data_eq($request->data, '_tom', 'tom123');
    
    $route->target['data'] = array();
    $request = new Request('http://example.de/index.php/foo/BAR');
    $this->assert_true_acception($route, $request);
    $this->assert_false(array_key_exists('_bar', $request->data));
    
    $this->assert_target_data_eq($request->data, '_tom', 'BAR');
    $this->assert_false_acception($route, new Request('http://example.de/index.php/foo'));
  }
  
  function test_acception_with_requirements() {
    $route = new Object('get', '/foo/&id', array(), array('id' => '/\d+/'));
    $this->assert_true_acception($route, new Request('http://example.de/index.php/foo/123'));
    $this->assert_false_acception($route, new Request('http://example.de/index.php/foo/asfag'));
  }
}
?>