<?php
namespace controllers;
use http\Response;

class ApplicationController extends \action\Controller {
  function index() {
    $this['teaser'] = 'foobar';
    return new Response(200, "Index page");
  }
}
?>