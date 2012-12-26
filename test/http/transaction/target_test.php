<?php
namespace http\transaction;
use http\router\Acceptor as Acceptor;
use http\Request;
use http\router\Route;

class TargetTest extends \http\router\TestUnit {
  function set_up() {
    $this->load_fixtures('route_targets');
  }
  
  function test_construction_with_string() {
    $t = new Target('application#index');
    $this->assert_eq($t->to(), 'application#index');
  }
  
  function test_construction_with_array() {
    $t = new Target(array('to' => 'users#show'));
    $this->assert_eq($t->to(), 'users#show');
  }
  
  function test_request_process_with_closure() {
    $t = new Target(function($request) {
      
    });
    
    $t->process(new Request());
  }
    
  function test_to_with_closure() {
    $t = new Target(array('to' => function() {}));
    $this->assert_instanceof($t->compile(new Request()), '\Closure');
  }
  
  function test_to_with_function() {
    $t = new Target(array('to' => 'all_users'));
    $this->assert_eq($t->compile(new Request()), 'all_users');
  }
  
  function test_to_with_namespaced_function() {
    $t = new Target(array('to' => 'users\accounts\create'));
    $this->assert_eq($t->compile(new Request()), 'users\accounts\create');
  }
  
  function test_to_with_function_and_target_namespace() {
    $t = new Target(array('to' => 'all', 'namespace' => 'users'));
    $this->assert_eq($t->compile(new Request()), 'users\all');
  }
  
  function test_target_with_controller() {
    $t = new Target(array('controller' => 'users'));
    $this->assert_eq($t->compile(new Request()), 'Users::handle_transaction');
  }
  
  function test_target_with_controller_and_namespace() {
    $t = new Target(array('controller' => 'accounts', 'namespace' => 'users'));
    $this->assert_eq($t->compile(new Request()), 'users\Accounts::handle_transaction');
  }
  
  function test_to_with_controller_action_shortcut() {
    $t = new Target(array('to' => 'users#index'));
    $request = new Request();
    $t->compile($request, $processor);
    
    $this->assert_eq($processor, 'Users::handle_transaction');
    $this->assert_eq($request->data['_action'], 'index');
  }
  
  function test_to_with_namespaced_controller_action_shortcut() {
    $t = new Target(array('to' => 'users.accounts#index'));
    $request = new Request();
    $t->compile($request, $processor);
    
    $this->assert_eq($processor, 'users\Accounts::handle_transaction');
    $this->assert_eq($request->data['_action'], 'index');
  }
  
  function test_to_with_target_alias() {
    $t = new Target(array('to' => 'users.accounts#create', 'alias' => '%s_controller'));
    $request = new Request();
    $t->compile($request, $processor);

    
    $this->assert_eq($processor, 'users\AccountsController::handle_transaction');
    $this->assert_eq($request->data['_action'], 'create');
  }
  
  function test_transaction_with_global_function() {
    $t = new Target('show_user');
    $response = $t(new Request());
    $this->assert_eq("$response", '@user');
  }
  
  function test_transaction_with_controller_action() {
    $t = new Target('users#index');
    $response = $t(new Request('http://example.de'));
    $this->assert_eq("$response", 'index');
  }
  
  function test_transaction_with_action_param() {
    $t = new Target('return_action');
    $response = $t(new Request('http://example.de?_action=test'));
    $this->assert_eq("$response", 'test');
  }
  
  function test_route_acception_with_action_path_param() {
    $t = new Target('return_action');
    $request = new Request('http://example.de/hello/world');
    
    Acceptor::accept_route(new Route('/hello/&action'), $request);
    
    $response = $t($request);
    $this->assert_eq("$response", 'world');
  }
  /*
  function test_autoload() {
    Target::unregister_autoloaders();
    
    $request = new Request('get', 'http://domain.foo/index.php/foo/bar');
    
    $action = new Target(array('to' => 'accounts#create', 'load_dir' => $this->bench_dir(), 'namespace' => 'controllers\admin', 'alias' => '%s_controller'));
    $response = $action->process($request);
    $this->assert_eq("$response", "Account created");
  }*/
}
?>