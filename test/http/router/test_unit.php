<?php
namespace http\router;

class TestUnit extends \test_case\Unit {  
  function bench_dir() {
    return realpath(__DIR__."/../../bench");
  }
}
?>