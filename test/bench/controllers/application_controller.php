<?php
namespace controllers;
use http\Response;

class ApplicationController extends \http\action\Controller {
  function index() {
    $this['teaser'] = 'foobar';
    return new Response(200, "Index page");
  }
}
?>