<?php
namespace test_bench {
  require_once '/../../suitcase.php';
  \suitcase\import('test', 'http-router', 'http-action');
  
  require_once 'bench.php';
  
  autoload_in('http', __DIR__."/http");
  
  $bench = new PackageTestBench();
  $bench->run_and_present_as_text();
}
?>