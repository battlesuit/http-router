<?php
namespace http\transaction;
use http\ConcreteControllerMock;
use http\Request;

class ControllerTest extends \http\TestCase {
  function set_up() {
    $this->load_mocks('concrete_controller');
    $this->controller = new ConcreteControllerMock();
  }
  
  function test_process_transaction() {
    $this->assert_eq($this->controller->process_transaction(new Request())->flat_body(), 'Easy response');
  }
  
  function test_invocation() {
    $controller = $this->controller;
    $this->assert_eq($controller(new Request())->flat_body(), 'Easy response');
  }
  
  function test_static_handle() {
    $this->assert_eq(ConcreteControllerMock::handle_transaction(new Request())->flat_body(), 'Easy response');
  }
}
?>