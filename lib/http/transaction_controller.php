<?php
namespace http;

/**
 * Topmost transaction evaluation class
 *
 * PHP Version 5.3+
 * @author Thomas Monzel <tm@apparat-hamburg.de>
 * @version $Revision$
 * @package Suitcase
 * @subpackage Net
 */
abstract class TransactionController extends Object {
  
  /**
   * Invoke as transaction application
   *
   * @access public
   * @param Request $request
   * @return Response $response
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
   * @return Response $response
   */
  static function handle_transaction(Request $request) {
    return Transaction::handle(array(new static(), 'process_transaction'), $request);
  }
  
  /**
   * Processor method must be defined by subclasses
   *
   * @access public
   * @param Request $request
   * @return Response $response
   */
  abstract function process_transaction(Request $request);
}
?>