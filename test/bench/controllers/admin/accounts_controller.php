<?php
namespace controllers\admin;

class AccountsController extends \controllers\ApplicationController {
  function create() {
    return new \http\Response(201, "Account created");
  }
}
?>