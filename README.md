bs.http-router
==============

This one allowes you to draw a compositional routemap of http requests => callbacks.  
Run tests under `test/run.php` (91/91 completed, 182 passes)

###Dependencies
This bundle also requires `http` and `str-inflections` to work.

###Basic usage

    namespace http {
      $router = draw_routes(function($domain) {
      
        # route "/path/to/resource" to 'resource#handle' (controller#action)
        $domain->match('/path/to/resource', 'resource#handle');
        
        $domain('/&locale', function($locale) {
        
          # route "/(de|en..)/products(/&id)" to 'products#(index|show|edit|add|create|destroy)'
          $locale->resource('products');
        });
        
        # route "/" to 'application#home'
        $domain->to('application#home');
        
      });
      
      # the $router variable is invocable so we can pass it to transaction\run()
      transaction\run($router)->serve();
    }

##Drawing routemaps
Instanciate a `http\route\Scope` and you're ready to go.  
**At the end of the drawing process you always must call the scopes `finalize()` method!**

######Matching 
First of all I give you some basic targeting examples
    
    namespace http {
      
      # instanciate one
      $scope = new route\Scope();
      
      # match to closured target  
      $scope->match('/path/to/resource', function($request) {
        return new Response(200, 'Found resource');
      });
      
      # or to user-function target
      $scope->match('/path/to/resource', 'my_request_handler');
      
      # or a static class function
      $scope->match('/path/to/resource', 'foo\bar\Baz::handle_request');
      
      # or a special controller action
      $scope->match('/path/to/resource', 'application#index');
    }
    
######Compositional usage
As of PHP 5.3+ we make use of the closure for deeper scoping

    namespace http {
      
      $scope = new route\Scope();
      
      # drawing a sub-scope
      $scope('/my/path', function($scope) {
        $scope->match('/hello/world', 'app#index'); # matches "/my/path/hello/world" to app#index
      });
      
      # drawing a sub-resource scope
      $scope->resource('products', function($products) {
        # inside this scope we got some predefined routes
      });
    }
    
##Accepting a route
Now we want to find out which route matches the request

    namespace http {
      
      # before we can do acception tests we've to finalize
      $scope->finalize();
      
      # pick the route collection
      $routes = $scope->routes();
      
      # accept
      $accepted_route = $routes->accept(new Request('post', '/de/products'));
      
      # test acception
      if($accepted_route !== false) {
        # we got a accepted route
        # lets dump the target
        var_dump($accepted_route->target);
        exit;
      }
      
      echo "No routes accepted";
    }
    
##Compiling the transaction target
The route target compiles the given informations to a callable processor

    namespace http\transaction {
      $t = new Target(array('controller' => 'application', 'action' => 'index'));
      
      # or
      $t = new Target('application#index');
      
      # or
      $t = new Target(function($request) {
        echo "hello world";
      });
      
      # or
      $t = new Target('path\to\user_defined_function');
      
      # our request
      $r = new Request('get', '/have/a/beer');
      
      # compiling it
      $t->compile($r, $processor);
      
      # now $processor should be the callable we are looking for
      $processor($r); # => http\Response
    }