<?php
namespace test_bench {
  require "loader.php";
  
  set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);
  error_reporting(-1);
  
  class PackageTestBench extends Base {
    function initialize() {
      $this->add_test(new \http\router\RouteTest());
      $this->add_test(new \http\router\AcceptorTest());
      $this->add_test(new \http\router\RouteCollectionTest());
      $this->add_test(new \http\router\ScopeTest());
      $this->add_test(new \http\RouterTest());
      $this->add_test(new \http\transaction\TargetTest());
      $this->add_test(new \http\transaction\ControllerTest());
      
      # server tests
      //$this->add_test(new \server\RouteScopingTest());
    }
  }
  
  $bench = new PackageTestBench();
  $bench->run_and_present_as_text();
}
?>