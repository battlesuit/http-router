<?php
namespace http;

class RouterTest extends TestCase {
  function set_up() {
    $this->router = new Router();
  }
  
  function test_instantiation_argumentless() {
    new Router();
  }
  
  function test_match_and_invocation_with_closure() {

    $this->router->draw_routes(function($root) {
      $root->match('/foo', function() {
        return array(200, array('content_type' => 'text/html'), array('hello world'));
      });
    });
    
    $request = new Request('http://domain.de/foo');
    
    $accepted = $this->router->accept_route($request);
    $this->assert_true($accepted);
    
    $request = new Request('http://domain.de/foo/test.php/foo');
    
    $accepted = $this->router->accept_route($request);
    $this->assert_true($accepted);
  }
  
  function test_match_failure() {
    $this->router->draw_routes(function($root) {
      $root->match('/foo', 'Callbacks::test_response');
    });
    
    
    $request = new Request('http://domain.de/fo');
    
    $accepted = $this->router->accept_route($request);
    $this->assert_false($accepted);
  }
  
  function test_match_by_index_array_conditions() {
    $this->router->draw_routes(function($root) {
      $root->match(array('/foo/bar/&my_param', 'get'), array('to' => 'hello_world'), array('my_param' => '/\d+/'));
    });
    
    $request = new Request('http://domain.de/foo/index.php/foo/bar/12');
    $accepted = $this->router->accept_route($request, $route);
    $this->assert_true($accepted);

    $this->assert_equality($request->data['_my_param'], '12');
  }
}
?>