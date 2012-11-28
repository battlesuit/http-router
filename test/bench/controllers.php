<?php
namespace {
  use http\action\Controller as ActionController;
  
  class UnderscoredControllerName extends ActionController {
    
  }
  
  class ControllerWithSuffixController extends ActionController {
    
  }
  
  class VeryLongControllerNameWithSuffixController extends ActionController {
    
  }
  
  class BeforeAndAfterActionController extends ActionController {
    function before_action() {
      $this->response->body('before');
    }
    
    function trigger_action() {
      $this->response->body('trigger');
    }
    
    function after_action() {
      $this->response->body('after');
    }
  }
  
  class BeforeAndAfterActionViaDelegation extends ActionController {
    public $before_action = 'custom_before_action';
    public $after_action = 'custom_after_action';
    
    protected function custom_before_action() {
      $this->response->body('before');
    }
    
    function trigger_action() {
      $this->response->body('trigger');
    }
    
    protected function custom_after_action() {
      $this->response->body('after');
    }
  }
  
  class BeforeAndAfterActionViaDelegationExcept extends ActionController {
    public $before_action = array('custom_before_action', 'except' => 'trigger');
    public $after_action = array('custom_after_action', 'except' => array('trigger', 'activate'));
    
    protected function custom_before_action() {
      $this->response->body('before');
    }
    
    function trigger_action() {
      $this->response->body('trigger');
    }
    
    function activate() {
      $this->response->body('activate');
    }
    
    protected function custom_after_action() {
      $this->response->body('after');
    }
  }
  
  class RedirectionsController extends ActionController {
    function index() {
      
    }
    
    function redirect_to_index() {
      $this->redirect_to('index');
    }
    
    function redirect_to_add() {
      $this->redirect_to('add');
    }
    
    function redirect_to_url() {
      $this->redirect_to('http://google.de');
    }
  }
  
  class RespondingController extends ActionController {
    function index() {
      return $this->respond_to('xml', function() {
        return new http\Response(200, "<data></data>", array('content_type' => 'text/xml'));
      });
    }
    
    function respond_many() {
      return $this->respond_to(array(
        'xml' => function() {
          return new http\Response(200, "<nodes></nodes>", array('content_type' => 'text/xml'));
        },
        'js' => function() {
          return new http\Response(200, "function alertSomehting() { alert('something'); }", array('content_type' => 'text/javascript'));
        }
      ));
    }
  }
}
?>