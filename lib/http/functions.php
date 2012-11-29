<?php
namespace http {
  function draw_routes($locals, $block = null) {
    $router = new Router();
    $router->draw_routes($locals, $block);
    return $router;
  } 
}
?>