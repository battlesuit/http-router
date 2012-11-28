<?php
namespace test_bench {
  require __DIR__.'/../init/test.php';
  set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);
  
  autoload_in('http', __DIR__."/http");
  
  class PackageTestBench extends Base {
    function initialize() {
      $this->add_test(new \http\route\ObjectTest());
      $this->add_test(new \http\route\AcceptorTest());
      $this->add_test(new \http\route\CollectionTest());
      $this->add_test(new \http\route\ScopeTest());
      $this->add_test(new \http\transaction\TargetTest());
      $this->add_test(new \http\transaction\ControllerTest());
      $this->add_test(new \http\action\ControllerTest());
    }
  }
  
  $bench = new PackageTestBench();
  $bench->run_and_present_as_text();
}
?>