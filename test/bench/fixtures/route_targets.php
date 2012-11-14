<?php
namespace {
  function hello_world() {
    return "hello world";
  }
  
  function all_users() {
    
  }
  
  function show_user() {
    return '@user';
  }
  
  function return_action($request) {
    return $request->data['_action'];
  }
  
  class Users extends http\TransactionController {
    function process_transaction(http\Request $request) {
      echo $request->data['_action'];
    }
  }
  
  class Products extends http\TransactionController {
    function process_transaction(http\Request $request) {
      //echo $request->data['_action'];
    }
  }
  
  class Tests extends http\TransactionController {
    function process_transaction(http\Request $request) {
      //echo $request->data['_action'];
    }
  }
  
  class Invoices extends http\TransactionController {
    function process_transaction(http\Request $request) {
      //echo $request->data['_action'];
    }
  }
}

namespace users {
  function all() {
    
  }
  
  class Accounts {
    static function handle_transaction($request) {
      
    }
  }
  
  class AccountsController {
    static function handle_transaction($request) {
      
    }
  }
  
  class ActionReturners {
    static function handle_transaction($request) {
      return $request->data['_action'];
    }
  }
}

namespace users\accounts {
  function create() {
    
  }
}
?>