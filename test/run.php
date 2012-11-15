<?php
namespace test_bench {
  require_once '/../../suitcase.php';
  \suitcase\import('test', 'http-router', 'http-action');
  
  require_once 'bench.php';
  
  # register default autoload functionality
  spl_autoload_register(function($class) {
    return spl_autoload(preg_replace('/(\p{Ll})(\p{Lu})/', '$1_$2', $class), '.php');
  });
  
  $bench = new PackageTestBench();
  $bench->run_and_present_as_text();
}
?>