<?php
namespace http\route;
use http\Request;
use http\TestCase;
use http\transaction\Target as TargetTransaction;

class ScopeTest extends TestCase {
  function accept_route($request, &$route = null) {
    $this->scope->finalize();
    $route = $this->scope->routes()->accept($request);
    return ($route !== false);
  }
  
  function assert_accept($request, &$route = null) {
    $this->assert_true($this->accept_route($request, $route));
  }
  
  function assert_accept_and_dispatch($request, &$action = null) {
    $this->assert_accept($request, $route);
    $action = new TargetTransaction($route->target);
    
    return $action($request);
  }
  
  function assert_eq_action_processor($index, $processor) {
    $routes = $this->scope->routes();
    $action = new TargetTransaction($routes[$index]->target);
    $action->compile(new Request());
    $this->assert_eq($action->processor(), $processor);
  }
  
  function assert_no_match($request, &$dispatcher = null) {
    $this->assert_false($this->accept_route($request, $dispatcher));
  }
  
  function set_up() {
    $this->load_fixtures('route_targets');
    $this->scope = new Scope();
  }
  
  function test_defaults() {
    $scope = $this->scope;
    $this->assert_eq($scope->depth(), 0);
    $this->assert_eq($scope->level(), 1);
    $this->assert_null($scope->parent());
    $this->assert_false($scope->has_parent());
    $this->assert_false($scope->is_member());
    $this->assert_instanceof($scope->routes(), 'http\route\Collection');
  }
  
  function test_to_array(){
    $this->scope->match('/users', 'users#index');
    $this->scope->match('/users/&id/edit', 'users#edit');
    $this->scope->match('/users/&id', 'users#destroy');
    $this->scope->match('/users/&id', 'users#update');
    
    $this->assert_array($this->scope->to_array());
    $this->assert_equality(count($this->scope->routes()), 4);
  }
  
  function test_to_string() {
    $this->scope->push_route('get', '/foo/bar');
    $this->assert_present("$this->scope");
  }
  
  function test_match() {
    $this->scope->match('/foo/bar', 'bla');
    $this->assert_accept(new Request('http://bla.de/foo/bar'), $route);
    $this->assert_eq($route->target['to'], 'bla');
  }
  
  
  function test_match_path_without_leading_slash() {
    $this->scope->match('foo/bar', 'Callbacks::test_response');
  }
  
  function test_match_via() {
    $this->scope->match(array('path' => '/users/&id', 'via' => 'post'), 'callback');
    
    $this->assert_accept(new Request('http://example.de/users/12', 'post'));

    $this->scope->match(array('/projects/&id', array('post', 'delete')), 'callback');
    $this->assert_accept(new Request('http://example.de/projects/12', 'delete'));
    $this->assert_accept(new Request('http://example.de/projects/12', 'post'));
  }
  
  function test_via_helpers() {
    $this->scope->get('/users', 'users#index');
    $this->assert_accept(new Request('http://example.de/users'));
    
    $this->scope->post('/accounts', array('controller' => 'accounts', 'action' => 'create'));
    $this->assert_accept(new Request('http://example.de/accounts', 'post'));
    
    $this->scope->delete('/users/&id', 'users#destroy');
    $this->assert_accept(new Request('http://example.de/users/12', 'delete'));
    
    $this->scope->put('/users/&id/edit', 'users#update');
    $this->assert_accept(new Request('http://example.de/users/12/edit', 'put'));
  }
  
  function test_match_controller_via_array() {
    $this->scope->match(array('method' => 'post', 'path' => '/products'), array('controller' => 'products', 'action' => 'create', 'namespace' => 'app_controllers'));
  }
  
  function test_match_with_targets_dir() {
    $this->scope->match('/products/lamp', array('to' => 'products#view_lamp', 'targets_dir' => __DIR__.'/fixtures'));
  }
  
  function test_match_target_callback() {
    $this->scope->match('/foo/bar', 'hello_world');
  }
  
  function test_match_target_callback_via_to_param() {
    $this->scope->match('/foo/bar', array('to' => 'hello_world'));
  }
  
  function test_match_by_index_array_conditions() {
    $this->scope->match(array('/foo/bar/&my_param', 'get', array('my_param' => '/\d+/')), array('to' => 'hello_world'));
  }

  
  function test_scope() {
    $this->scope->scope('/foo', function($inner) {
      $inner->get('/bar/&param', 'hello_world');
    });
    
    $request = new Request('http://example.de/foo/bar/my_param');
    $this->assert_accept_and_dispatch($request);
    
    $this->assert_key_exists('_param', $request->data);
    $this->assert_equality($request->data['_param'], 'my_param');
  }
  
  function test_controller() {
    $this->scope->controller('products', function($products) {
      $products->get('/add', array('action' => 'index'));
      $products->post('/add', array('action' => 'create'));
    });
    
    $request = new Request('http://example.de/products/add', 'post');
    $this->assert_accept_and_dispatch($request);
    $this->assert_equality($request->data['_action'], 'create');
    
    $request = new Request('http://example.de/products/add');
    $this->assert_accept_and_dispatch($request);
    $this->assert_equality($request->data['_action'], 'index');
  }
  
  function test_resource() {
    $this->scope->resource('users', function($users) {
      $users->put('/update_all', 'users#update_all_users');
    });
    
    $this->assert_no_match(new Request('http://example.de/users/update_all', 'post')); 
    $this->assert_accept(new Request('http://example.de/users/update_all', 'put'));
    $this->assert_accept(new Request('http://example.de/users'));
  }
  
  function test_many_resources() {
    $this->scope->resources('users', 'products', 'fruits');
    $this->assert_accept(new Request('http://example.de/fruits/23/edit'));
  }
  
  function test_many_nested_resources() {
    $this->scope->scope('/&locale', function($locale) {
      $locale->scope('/foo/bar-nested', function($nested) {
        $nested->resources('tests', 'friends');
        
        $nested->put('/update_all', 'users#update_all_users');
      });
    });
    
    $request = new Request('http://example.de/de/foo/bar-nested/tests');
    $this->assert_accept_and_dispatch($request);
    
    $this->assert_equality($request->data['_action'], 'index');
    $this->assert_equality($request->data['_locale'], 'de');
    
    $this->assert_no_match(new Request('http://example.de/de/foo/bar-nested/update_all'));
    $this->assert_accept(new Request('http://example.de/de/foo/bar-nested/update_all', 'put'));
  }
  
  function test_under() {
    $this->scope->under('\users\accounts', array('path' => '/tom'), function($scope) {
      $scope->get('/foo', 'create');
    });
    
    $this->assert_accept_and_dispatch(new Request('http://example.de/tom/foo'), $action);
    
    
    $target = $action->target();
    $this->assert_equality($target['namespace'], 'users\accounts');
    $this->assert_equality($action->processor(), 'users\accounts\create');
  }
  
  function test_member() {
    $this->scope->scope(array('controller' => 'users'), function($scope) {
      $scope->member(function($member) {
        $member->post('/action', 'target_function');
      });
    });
    
    $this->assert_accept(new Request('/users/23/action', 'post'));
    $this->assert_no_match(new Request('/aa/action', 'post'));
  }
  
  function test_nested_member_controllers() {
     $this->scope->controller('clients', function($clients) {
      $clients->member(function($member) {
        $member->controller('invoices', function($invoices) {
          $invoices->member(function($invoices_member) {
            $invoices_member->get('/nested/path', array('action' => 'index'));
          });
        });
      });
    });
    
    $request = new Request('http://example.de/tom/foo/index.php/clients/15/invoices/23/nested/path');
    $this->assert_accept_and_dispatch($request, $delegator);
    $this->assert_eq($request->data['_client_id'], '15');
    $this->assert_eq($request->data['_id'], '23');
  }
  
  function test_alias() {
    $this->scope->scope(array('alias' => '%s_controller'), function($scope) {
      $scope->get('/foo/bar', 'users.accounts#index');
    });
    
    $this->scope->finalize();
    $routes = $this->scope->routes();
    $this->assert_eq($routes[0]->target['alias'], '%s_controller');
    
    $this->assert_eq_action_processor(0, 'users\AccountsController::handle_transaction');
  }
  
  function test_alias_on_resource() {
    $this->scope->scope(array('alias' => '%s_controller'), function($scope) {
      $scope->resource('users');
    });
    
    $this->scope->finalize();
    $routes = $this->scope->routes();
    $this->assert_eq($routes[0]->target['alias'], '%s_controller');
    $this->assert_eq_action_processor(0, 'UsersController::handle_transaction');
  }
  
  function test_alias_on_nested_resource() {
    $this->scope->scope(array('alias' => '%s_controller'), function($scope) {
      $scope('/path/to', function($nested) {
        $nested->resource('users');
      });
      
    });
    
    $this->scope->finalize();
    $routes = $this->scope->routes();
    $this->assert_eq($routes[0]->target['alias'], '%s_controller');
  }
  
  /*function test_load_dir() {
    $this->scope->scope(array('alias' => '%s_controller', 'load_dir' => __DIR__.'/controllers/dir'), function($scope) {
      $scope->get('/foo/bar', 'users.accounts#index');
    });
    
    $this->scope->finalize();
    $routes = $this->scope->routes();
    $this->assert_eq($routes[0]->target['load_dir'], __DIR__.'/controllers/dir');
    $this->assert_eq_action_processor(0, 'users\AccountsController::handle_transaction');
  }
  
  function test_scope_autoload() {
    $this->scope->scope(array('alias' => '%s_controller', 'load_dir' => $this->bench_dir(), 'namespace' => 'controllers'), function($scope) {
      $scope->get('/foo/bar', 'application#index');
    });
    
    $this->scope->finalize();
    $request = new Request('get', 'http://domain.foo/index.php/foo/bar');
    $route = $this->scope->routes()->accept($request);
    $this->assert_true(($route !== false));
    
    TargetTransaction::unregister_autoloaders();
    $action = new TargetTransaction($route->target);
    $response = $action->process($request);
    $this->assert_eq("$response", "Index page");
  }
  
  function test_scope_with_requirements() {
    $this->scope->scope(array('requirements' => array('bar' => '/(de|en)/')), function($scope) {
      $scope->get('/foo/&bar', 'application#index');
    });
    
    $this->scope->finalize();
    $routes = $this->scope->routes();

    $request = new Request('get', 'http://domain.foo/index.php/foo/asdasd');
    $route = $this->scope->routes()->accept($request);
    $this->assert_false($route);
    
    $request = new Request('get', 'http://domain.foo/index.php/foo/en');
    $route = $this->scope->routes()->accept($request);
    $this->assert_true(($route !== false));
  }*/
}
?>