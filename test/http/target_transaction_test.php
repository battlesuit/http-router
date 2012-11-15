<?php
namespace http;
use http\route\Acceptor as RouteAcceptor;

class TargetTransactionTest extends TestCase {
  function set_up() {
    $this->load_fixtures('route_targets');
  }
  
  function test_construction_with_string() {
    $action = new TargetTransaction('application#index');
    $this->assert_eq($action->targets_to(), 'application#index');
  }
  
  function test_construction_with_array() {
    $action = new TargetTransaction(array('to' => 'users#show'));
    $this->assert_eq($action->targets_to(), 'users#show');
  }
  
  function test_request_process_with_closure() {
    $action = new TargetTransaction(function($request) {
      
    });
    
    $action->process(new Request());
  }
    
  function test_to_with_closure() {
    $action = new TargetTransaction(array('to' => function() {}));
    $this->assert_instanceof($action->compile(new Request()), '\Closure');
  }
  
  function test_to_with_function() {
    $action = new TargetTransaction(array('to' => 'all_users'));
    $this->assert_eq($action->compile(new Request()), 'all_users');
  }
  
  function test_to_with_namespaced_function() {
    $action = new TargetTransaction(array('to' => 'users\accounts\create'));
    $this->assert_eq($action->compile(new Request()), 'users\accounts\create');
  }
  
  function test_to_with_function_and_target_namespace() {
    $action = new TargetTransaction(array('to' => 'all', 'namespace' => 'users'));
    $this->assert_eq($action->compile(new Request()), 'users\all');
  }
  
  function test_target_with_controller() {
    $action = new TargetTransaction(array('controller' => 'users'));
    $this->assert_eq($action->compile(new Request()), 'Users::handle_transaction');
  }
  
  function test_target_with_controller_and_namespace() {
    $action = new TargetTransaction(array('controller' => 'accounts', 'namespace' => 'users'));
    $this->assert_eq($action->compile(new Request()), 'users\Accounts::handle_transaction');
  }
  
  function test_to_with_controller_action_shortcut() {
    $action = new TargetTransaction(array('to' => 'users#index'));
    $request = new Request();
    $action->compile($request, $processor);
    
    $this->assert_eq($processor, 'Users::handle_transaction');
    $this->assert_eq($request->data['_action'], 'index');
  }
  
  function test_to_with_namespaced_controller_action_shortcut() {
    $action = new TargetTransaction(array('to' => 'users.accounts#index'));
    $request = new Request();
    $action->compile($request, $processor);
    
    $this->assert_eq($processor, 'users\Accounts::handle_transaction');
    $this->assert_eq($request->data['_action'], 'index');
  }
  
  function test_to_with_target_alias() {
    $action = new TargetTransaction(array('to' => 'users.accounts#create', 'alias' => '%s_controller'));
    $request = new Request();
    $action->compile($request, $processor);

    
    $this->assert_eq($processor, 'users\AccountsController::handle_transaction');
    $this->assert_eq($request->data['_action'], 'create');
  }
  
  function test_transaction_with_global_function() {
    $action = new TargetTransaction('show_user');
    $response = $action(new Request());
    $this->assert_eq("$response", '@user');
  }
  
  function test_transaction_with_controller_action() {
    $action = new TargetTransaction('users#index');
    $response = $action(new Request('get', 'http://example.de'));
    $this->assert_eq("$response", 'index');
  }
  
  function test_transaction_with_action_param() {
    $action = new TargetTransaction('return_action');
    $response = $action(new Request('get', 'http://example.de?_action=test'));
    $this->assert_eq("$response", 'test');
  }
  
  function test_route_acception_with_action_path_param() {
    $action = new TargetTransaction('return_action');
    $request = new Request('get', 'http://example.de/hello/world');
    
    RouteAcceptor::accept_route(new Route('get', '/hello/&action'), $request);
    
    $response = $action($request);
    $this->assert_eq("$response", 'world');
  }
  
  function test_autoload() {
    TargetTransaction::unregister_autoloaders();
    
    $request = new Request('get', 'http://domain.foo/index.php/foo/bar');
    
    $action = new TargetTransaction(array('to' => 'accounts#create', 'load_dir' => $this->bench_dir(), 'namespace' => 'controllers\admin', 'alias' => '%s_controller'));
    $response = $action->process($request);
    $this->assert_eq("$response", "Account created");
  }
}
?>