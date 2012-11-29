<?php
/**
 * Initializes the http-router bundle which depends on `http` and `str-inflections`
 *
 * PHP Version 5.3+
 * @author Thomas Monzel <tm@apparat-hamburg.de>
 * @version $Revision$
 * @package Battlesuit
 * @subpackage http-router
 */
namespace {
  $LIB_DIR = dirname(__DIR__)."/lib";
  
  autoload_in('http', $LIB_DIR."/http");
  
  # import dependencies
  import('http', 'str-inflections');
  
  # requirements
  require_once $LIB_DIR."/http/functions.php";
}
?>