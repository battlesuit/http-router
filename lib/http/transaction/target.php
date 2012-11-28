<?php
namespace http\transaction;
use http\Request;

class Target extends Base {
  
  private static $autoloaders = array();
  
  /**
   * Target components to dispatch
   *
   * @access protected
   * @var array
   */
  protected $target;
  
  /**
   * Constructs a new request dispatcher instance
   * 
   * @access public
   * @param mixed $target
   */
  function __construct($target) {
    if(!is_array($target) or is_callable($target)) $target = array('to' => $target);
    $this->target = $target;
  }
  
  /**
   * Reads the target components
   *
   * @access public
   * @return array
   */
  function target() {
    return $this->target;
  }
  
  /**
   * Where does the journey goes?
   *
   * @access public
   * @return mixed
   */
  function targets_to() {
    return $this->target['to'];
  }
  
  function process(Request $request) {
    if(isset($this->target['location'])) {
      $location = $this->target['location'];
      foreach($request->data as $name => $value) {
        if(strpos($name, '_') === 0) {
          $name = substr($name, 1);
          $location = str_replace("&$name", $value, $location);
             
        }
      }
      
      $response = new Response(301);
      if(strpos($location, 'http://') !== false) {
        $response->location($location);
      } else $response->location($request->base_url()."/".trim($location, '/'));
      
      return $response;
    }
    
    $this->compile($request, $processor);
    
    if(is_callable($processor)) {
      return parent::process($request);
    } else throw new \ErrorException("Invalid target or does not exist: Processor comiling failed");
  }
  
  /**
   * 
   *
   * @access public
   * @param Request $request
   * @return Response
   */
  function compile(Request $request, &$processor = null) {
    $to = $action = $base_dir = null;
    extract($this->target);
    
    if(is_string($to)) {
      if(!empty($namespace)) $processor = "$namespace\\$to";
      else $processor = $to;
      
      if(is_callable($processor)) {    
        goto end;
      } elseif(strpos($to, '#') !== false) {
        
        # short callback
        list($controller, $action) = explode('#', $to);
        if(strpos($controller, '.')) {
          
          # read and normalize dotted namespace from controller name
          $namespace = str_replace('.', '\\', substr($controller, 0, strrpos($controller, '.')));
          $controller = substr(strrchr($controller, '.'), 1);
        }
      }
      
    } elseif(is_callable($to)) {
      # closure callback
      $processor = $to;
      goto end;
    }
    
    $controller_class = null;
    if(isset($controller)) {
      if(!empty($alias)) {
        $controller = sprintf($alias, $controller);
      }
      
      # pascalize controller => ClassName
      $controller_class = str_replace(' ', '', ucwords(preg_replace('/(_|-)+/', ' ', $controller)));
    }
    
    if(isset($namespace)) {
      $controller_class = "$namespace\\$controller_class";
    }
    
    $processor = "$controller_class::handle_transaction";
    
    if(!empty($load_dir)) {
      static::register_autoload_for($load_dir); 
      class_exists($processor, true);
    }
    
    end:
    if(!empty($action)) $request->data['_action'] = $action;
    return $this->processor = $processor;
  }
  
  protected static function register_autoload_for($dir) {
    if(isset(static::$autoloaders[$dir])) return;
    
    $loader = function($class) use($dir) {
      $class_name = preg_replace('/(\p{Ll})(\p{Lu})/', '$1_$2', $class);
      $file = "$dir/$class_name.php";
      if(file_exists($file)) require_once $file;     
    };
    
    static::$autoloaders[$dir] = $loader;
    spl_autoload_register(static::$autoloaders[$dir]);
  }
  
  static function unregister_autoloaders() {
    $autoloaders = static::$autoloaders;
    foreach($autoloaders as $dir => $loader) {
      spl_autoload_unregister($loader);
      unset(static::$autoloaders[$dir]);
    }
  }
}
?>