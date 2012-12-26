<?php
namespace loader {
  require "../loader.php";
  
  import('test', 'http-router');
  scope('http', __DIR__);
  scope('server', __DIR__);
}
?>