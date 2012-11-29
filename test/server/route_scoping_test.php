<?php
namespace server;

class RouteScopingTest extends \test_case\Unit {
  public $script = 'http://localhost/route_scoping.php';
  
  function get_url($url) {
    return array(get_headers($url, 1), file_get_contents($url));
  }
  
  function test_not_found() {
    $headers = get_headers($this->script . "/path/to/nowhere", 1);
    $this->assert_eq($headers[0], 'HTTP/1.1 404 Not Found');
  }
  
  function test_invalid_target() {
    $headers = get_headers($this->script . "/path/to/resource", 1);
    $this->assert_eq($headers[0], 'HTTP/1.1 500 Internal Server Error');
  }
  
  function test_root_call() {
    $info = $this->get_url($this->script);
    $this->assert_eq($info[0][0], 'HTTP/1.1 200 OK');
    $this->assert_eq($info[1], 'Action: home');
  }
  
  function test_locale_products_call() {
    $info = $this->get_url($this->script . "/de/products/12");
    $this->assert_eq($info[0][0], 'HTTP/1.1 200 OK');
    $this->assert_eq($info[1], 'Showing one product with id: 12');
  }

}
?>