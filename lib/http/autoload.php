<?php
namespace loader {
  
  /**
   * All the autoloading is done here
   * This function is getting called by the loader\Bundles::autoload
   * 
   */
  
  scope('http\router', __DIR__);
  scope('http\transaction', __DIR__);
  import('http', 'str-inflections');
}
?>