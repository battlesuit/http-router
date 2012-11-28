<?php
namespace http\transaction;
use http\Request;

/**
 * Topmost transaction evaluation controller
 *
 * PHP Version 5.3+
 * @author Thomas Monzel <tm@apparat-hamburg.de>
 * @version $Revision$
 * @package Battlesuit
 * @subpackage http-router
 */
abstract class Controller {
  
  /**
   * Invoke as transaction application
   *
   * @access public
   * @param Request $request
   * @return Response
   */
  function __invoke(Request $request) {
    return $this->process_transaction($request);
  }
  
  /**
   * Constructs a controller instance and calls the process_transaction() method
   *
   * @static
   * @access public
   * @param Request $request
   * @return Base
   */
  static function handle_transaction(Request $request) {
    return Base::run(array(new static(), 'process_transaction'), $request)->response();
  }
  
  /**
   * Processor method must be defined by subclasses
   *
   * @access public
   * @param Request $request
   * @return Response
   */
  abstract function process_transaction(Request $request);
}
?>