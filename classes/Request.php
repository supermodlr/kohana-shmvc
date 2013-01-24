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

   public $host = NULL;

   /**
    *
    */
   function __construct($uri, $client_params = array(), $allow_external = TRUE, $injected_routes = array()) {

      parent::__construct($uri, $client_params, $allow_external, $injected_routes);


      // Get the url and set the host property
      $url = parse_url(URL::base($this));
      $this->host = $url['host'];


      // Initialize the site (by alias?)
      $cached_site = FALSE;
      if ($cached_site)
      {

      } 
      else 
      {
         $sitepath = $this->get_sitepath();
      }

      if ($sitepath) {
         // Make the individual sitepath relative to the sitespath, for symlink'd index.php
         if ( ! is_dir($sitepath) AND is_dir(SITESPATH.$sitepath))
            $sitepath = SITESPATH.$sitepath;

         define('SITEPATH', realpath($sitepath).DIRECTORY_SEPARATOR);

         // Add the site override path to Kohana::$_paths
         Kohana::add_path(SITEPATH);
      }

   }

   /**
    *
    */
   public function get_sitepath() {
      
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


      $host_array = explode('.', $this->host);
      while (count($host_array) >= 2)
      {
         $compare_host = implode('.', $host_array);
         foreach ($site_configs as $site_key => $site_cfg)
         {
            //fbl($site_cfg, $site_key);
            if (!isset($site_cfg['hosts'])) continue;
            foreach ($site_cfg['hosts'] as $host)
            {
               if ($host == $compare_host) {
                  return $site_key;
               }
            }
         }

         array_shift($host_array);
      }

      return FALSE;
   }

   public function is_mobile() 
   {
   
   }
   
   public function is_tablet() 
   {
   
   }   

   public function is_web() 
   {
      return TRUE;
   }
}
