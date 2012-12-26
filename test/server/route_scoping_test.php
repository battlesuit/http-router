<?php
namespace server;
use http\Connection;

class RouteScopingTest extends \test_case\Unit {
  function set_up() {
    $this->con = new Connection('localhost');
  }
  
  function tear_down() {
    $this->con->close();
  }
  
  /*function test_not_found() {
    $response = $this->con->get("/route_scoping.php/path/to/nowhere");
    $this->assert_eq($response->status_line(), 'HTTP/1.1 404 Not Found');
  }*/
  
  function test_invalid_target() {
    $response = $this->con->get("/route_scoping.php/path/to/resource");
    $this->assert_eq($response->status_line(), 'HTTP/1.1 404 Not Found');
    $this->assert_eq("$response", 'Routing Error: Invalid target or target does not exist');
  }
  
  /*function test_root_call() {
    $response = $this->con->get("/route_scoping.php");
    $this->assert_eq($response->status_line(), 'HTTP/1.1 200 OK');
    $this->assert_eq($response->flat_body(), 'Action: home');
  }
  
  function test_locale_products_call() {
    $response = $this->con->get("/route_scoping.php/de/products/12");
    $this->assert_eq($response->status_line(), 'HTTP/1.1 200 OK');
    $this->assert_eq($response->flat_body(), 'Showing one product with id: 12');
  }*/

}
?>