<?php
namespace http;

class TestCase extends \test_case\Unit {
  function bench_dir() {
    return realpath(__DIR__."/../bench");
  }
}
?>