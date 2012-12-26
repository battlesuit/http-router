<?php
namespace http {
  require __DIR__."/../../../../loader.php";
  use loader;
  loader\load('http-router');
  
  class Application extends transaction\Controller {
    function process_transaction(Request $request) {   
      return new Response(200, "Action: " . $request->data['_action']);
    }
  }
  
  class Products extends transaction\Controller {
    function process_transaction(Request $request) {
      $method = $request->data['_action'];
      if(method_exists($this, $method)) $this->$method($request);
    }
    
    function show($request) {
      echo "Showing one product with id: ".$request->data['_id'];
    }
  }
  
  $router = draw_routes(function($domain) {
  
    # route "/path/to/resource" to 'resource#handle' (controller#action)
    $domain->match('/path/to/resource', 'resource#handle');
    
    $domain(array('path' => '/&locale', 'namespace' => 'http'), function($locale) {
    
      # route "/(de|en..)/products(/&id)" to 'products#(index|show|edit|add|create|destroy)'
      $locale->resource('products');
    });
    
    # route "/" to 'application#home'
    $domain->to('http.application#home');
    
  });

  
  if(php_sapi_name() == 'cli') {
    $request = new Request('http://localhost/de/products/12');
  } else $request = transaction\request();
  
  # the $router variable is invocable so we can pass it to transaction\run()
  transaction\run($router, $request)->serve();
}
?>