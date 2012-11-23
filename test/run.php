<?php
namespace test_bench {
  require '../init/test.php';
  require 'bench.php';
  
  autoload_in('http', __DIR__."/http");
  
  $bench = new PackageTestBench();
  $bench->run_and_present_as_text();
}
?>