<?php defined('SYSPATH') or die('No direct script access.');

/**
* Request
*
* @uses     Kohana_Request
*
* @category Category
* @package  Package
* @author   Justin Shanks <jshanman@gmail.com>, Brandon Krigbaum <brandonbk@gmail.com>
* @license  
* @link     
*/
class Request extends Kohana_Request {

   /**
    * @var   string     Host for this request (www.site.com)
    */
   public $host = NULL;

   /**
    * @var   string     Site key for this request
    */
   public $site_key = NULL;

   /**
    * Creates a new request object for the given URI. New requests should be
    * created using the [Request::instance] or [Request::factory] methods.
    *
    *     $request = new Request($uri);
    *
    * If $cache parameter is set, the response for the request will attempt to
    * be retrieved from the cache.
    *
    * @param   string  $uri              URI of the request
    * @param   array   $client_params    Array of params to pass to the request client
    * @param   bool    $allow_external   Allow external requests? (deprecated in 3.3)
    * @param   array   $injected_routes  An array of routes to use, for testing
    * @return  void
    * @throws  Request_Exception
    * @uses    Route::all
    * @uses    Route::matches
    */
   function __construct($uri, $client_params = array(), $allow_external = TRUE, $injected_routes = array())
   {

      // Call the Kohana_Request::__construct() method
      parent::__construct($uri, $client_params, $allow_external, $injected_routes);


      // Get the url and set the host property
      $url = parse_url(URL::base($this));
      $this->host = $url['host'];


      // Initialize the site (by alias?)
      $host_cache_key = $this->host.'.host_cache';
      $site_key = Cache::instance()->get($host_cache_key);
      if (is_null($site_key))
      {
         // Get the site_key based on current host
         $site_key = $this->get_site_key($this->host);

         // Write the cache file
         Cache::instance()->set($host_cache_key, $site_key);
      }

      if ($site_key) {
         // Make the individual sitepath relative to the sitespath, for symlink'd index.php
         if ( ! is_dir($site_key) AND is_dir(SITESPATH.$site_key))
            $sitepath = SITESPATH.$site_key;

         define('SITEPATH', realpath($sitepath).DIRECTORY_SEPARATOR);

         // Add the site override path to Kohana::$_paths via Closure
         $add_path_function = static function($path) {
            array_unshift(static::$_paths, $path);
            return static::$_paths;
         };

         $add_path = Closure::bind($add_path_function, NULL, 'Kohana');
         $add_path(SITEPATH);
      }

   }

   /**
    * 
    * @param   string  $host  the name of the host to use for site lookup
    * @return  mixed
    */
   public function get_site_key($host)
   {
      
      $site_configs = array();
      try
      {
         $contents = scandir(SITESPATH);
         foreach ($contents as $dir)
         {
            $site_cfg_file = SITESPATH.$dir.'/config/site'.EXT;
            if ($dir != "." && $dir != ".." && is_dir(SITESPATH.$dir) && file_exists($site_cfg_file))
               $site_configs[$dir] = Kohana::load($site_cfg_file);
         }
      }
      catch (Exception $e)
      {
         return FALSE;
      }

      // Iterate through parts of the current host to find a matching host config value.
      // Example: 
      //    1 - developer.www.site.com
      //    2 - www.site.com
      //    3 - site.com
      $host_array = explode('.', $host);
      while (count($host_array) >= 2)
      {
         $compare_host = implode('.', $host_array); // Example: www.site.com
         foreach ($site_configs as $site_key => $site_cfg)
         {
            // If there is no hosts value, skip this folder
            if (!isset($site_cfg['hosts'])) 
               continue;

            foreach ($site_cfg['hosts'] as $h)
            {
               // If a match is found, return the site key
               if ($h == $compare_host) 
                  return $site_key;
            }
         }

         array_shift($host_array);
      }

      // If no matching host is found, return FALSE
      return FALSE;
   }
   
   /**
    * 
    */
   public function uri_parts() {
      return explode('/', $this->uri());
   }

   /**
    * 
    */
   public function is_mobile() 
   {

   }

   /**
    * 
    */
   public function is_tablet() 
   {
   
   }

   /**
    * 
    */
   public function is_web() 
   {
      return TRUE;
   }
}
