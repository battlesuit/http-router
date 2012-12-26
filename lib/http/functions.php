<?php
namespace http {
  
  /**
   * Opens a routing scope
   * Alias for Router#draw_routes
   *
   * @param mixed $locals
   * @param callable $block
   * @return Router
   */
  function draw_routes($locals, $block = null) {
    $router = new Router();
    $router->draw_routes($locals, $block);
    return $router;
  } 
}
?>