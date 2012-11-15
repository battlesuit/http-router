<?php
namespace test_bench;

class PackageTestBench extends Base {
  function initialize() {
    $this->add_test(new \http\RouteTest());
    $this->add_test(new \http\route\AcceptorTest());
    $this->add_test(new \http\route\CollectionTest());
    $this->add_test(new \http\route\ScopeTest());
    $this->add_test(new \http\TargetTransactionTest());
    $this->add_test(new \http\TransactionControllerTest());
  }
}
?>