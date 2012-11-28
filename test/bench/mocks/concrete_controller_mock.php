<?php
namespace http;

class ConcreteControllerMock extends transaction\Controller {
  function process_transaction(Request $request) {
    return new Response(200, 'Easy response');
  }
}
?>