bs.http-router
==============

Easy routing http requests to callbacks. This bundle also requires `http` and `str-inflections` to work.  
Run tests under `test/run.php` (91/91 completed, 182 passes)

###Drawing the compositional routemap
The tool for this purpose is `http\route\Scope`.  
**At the end of the drawing process you always must call the scopes `finalize()` method!**

######Targeting
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