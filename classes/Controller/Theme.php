<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Theme extends Controller_Template {

	public $template = NULL;
	public $templates = array();
	public $binds = array();
	protected $_theme = NULL;
	protected $_default_theme = 'default';
	protected $_default_media = 'web';
	protected $_media = NULL;
	
	
	/**
	 * Initializes theme, templates, and global view variables
	 */
	public function before()
	{
		// Detect and set theme
		if (is_null($this->_theme)) $this->theme($this->detect_theme());
				
		//defeat varnish cache for now
		// @todo remove this (hack)
		$this->response->headers('Cache-Control', 'no-cache, must-revalidate');	

		$this->app_config = Kohana::$config->load('supermodlr');
		
	}

	/**
	 * 
	 */
	public function detect_theme()
	{
		// @todo Review/update this logic
		$host = $this->request->host;
		$host_array = array_reverse(explode('.', $host));
		$sub_domain = $host_array[2];
		$theme = $sub_domain;
		return $theme;
	}


	/**
	 *  sets a template file to a template object.  $which can be index, body, '' (meaning content template), or any other view name
	 *  @param $file (string) filename for this view
	 *  @param $which (string) 'index', 'body', 'template', or a view name.  causes this view to be stored in $this->templates[$which] to be accessed as needed 
	 *  @return View with filename set based on $file
	 */
	public function set_template($file, $which = 'template') 
	{	
		//if this template is not initialized
		if (!isset($this->templates[$which]) || !is_object($this->templates[$which])) 
		{
			//create template view
			$this->templates[$which] = View::factory();

			$this->bind_vars($which);

		}
		//set filename on the view
		$this->templates[$which]->set_filename($this->find_template($file));
		
		//store default template on controller as 'template'
		if ($which == 'template') 
		{
			$this->template = &$this->templates[$which];
		}
		return $this->templates[$which];
	}
	
	/**
	 *  finds a template file by looking in /theme/controller, then /theme, then /
	 *  @param $file (string) file name to search for among the theme/media files
	 * returns string filepath and filename to be send to View::factory
	 */
	public function find_template($file) 
	{
		// @todo create theme repo(s)

		$theme = $this->theme();
		$media = $this->media();
		$controller = strtolower($this->request->controller());

		// Initialize file paths array
		$file_paths = array();

		// Add override paths
		$file_paths[] = $theme.'/'.$media.'/'.$controller;
		$file_paths[] = $theme.'/'.$media;
		$file_paths[] = $theme.'/'.$this->_default_media.'/'.$controller;
		$file_paths[] = $this->_default_theme.'/'.$media.'/'.$controller;
		$file_paths[] = $this->_default_theme.'/'.$this->_default_media.'/'.$controller;
		$file_paths[] = $this->_default_theme.'/'.$this->_default_media;
		$file_paths[] = $theme;
		$file_paths[] = $this->_default_theme;

		// Ensure unique values
		$file_paths = array_values(array_unique($file_paths));


		$found = $this->override($file, $file_paths);
		fbl($file_paths, $found['file']);
		if ($found)
		{
			//fbl($found['file'], 'found');
			return $found['file'];
		}
		else
		{
			return FALSE;
		}

		// Loop through file paths and return a match if found
		foreach ($file_paths as $path)
		{
			$path = trim($path, '/').'/';
			$current_file = $path.$file;
			if (Kohana::find_file('views', $current_file) !== FALSE)
				return $current_file;
		}

		// Check for /views/file (default location)
		if (Kohana::find_file('views', $file) !== FALSE)
			return $file;

		// If no file is found, return FALSE
		return FALSE;
	}
	
	/**
	 * 
	 */
	public function override($name, $paths)
	{
		$multi_wildcards = FALSE;
		$wildcards = FALSE;
		$uri_parts = $this->request->uri_parts();

      // Lob off the first uri part if its name is the same as the first part of the url
      // to avoid having to name things redundantly (ie "section.section.12345.ctrl.php")
      if ($uri_parts[0] == $name)
         array_shift($parts);

      $parts_set = array();

      // If an alias is set for this request, combine alias_parts and uri_parts, sorted by parts.length desc
      /*if (isset($alias_parts) && is_array($alias_parts) && !empty($alias_parts)) {
         //remove first part of alias if it is the same as the requested file
         if ($alias_parts[0] == $name) {
            array_shift($alias_parts);
         }

         $parts_temp = $alias_parts;
         while (count($parts_temp) > 0) {
            $parts_set[count($parts_temp)][] = $parts_temp;
            array_pop($parts_temp);
         }
      }*/

      // Add in uri parts
      $parts = $uri_parts;
      while (count($parts) > 0)
      {
         $parts_set[count($parts)][] = $parts;
         array_pop($parts);
      }

      ksort($parts_set);
      $parts_set = array_reverse($parts_set, TRUE);
      
      $return_file = FALSE;

      // Loop through each request uri sorted by count of parts, desc
      foreach ($parts_set as $count => $req_parts)
      {
         foreach ($req_parts as $path_parts)
         {

            // Loop through each path that we want to search, first found gets returned
            foreach ($paths as $key => $path)
            {
            	// Ensure paths have trailing slash only
            	$path = trim($path, '/').'/';

               // Check for hard override (all uri's are ignored if '$override' is sent and a matching file is found
               // @todo re-enable this
               /*if (!empty($override) && file_exists($path.$name.'.'.$override))
               {
                  $override_obj = array('file'=> $path.$name.'.'.$override, 'name'=> $name.'_'.$override);
                  //@todo self::set_override_cache_key($path_override_key,$override_obj);
                  return $override_obj;
               }*/

               // Generate possible file name
               $current_file = $name.".".implode('.',$path_parts);

               // Generate expected class name to be loaded (no periods are allowed in class names)
               $current_name = $name."_".implode('_',$path_parts);

               // Look for file and return it if found
					if (Kohana::find_file('views', $path.$current_file) !== FALSE)
					{
                  $return_file = $path.$current_file;
                  $override_obj = array('file'=> $return_file, 'name'=> $current_name);
                  // @todo self::set_override_cache_key($path_override_key, $override_obj);
                  return $override_obj;
               }

               $checked = array();

               // If wild cards are enabled (ie '_' in a filename can replace any ONE part of the filename)
               if ($wildcards)
               {
                  // Make a temp array
                  $pparts = $path_parts;

                  // Check for all possible single wildcards
                  foreach ($path_parts as $i => $part) {
                     // Replace one part with a '_'
                     $pparts[$i] = '_';
                     // Generate posible file name
                     $current_file = $name.".".implode('.',$pparts);
                     // Generate expected class name
                     $current_name = $name."_".implode('_',$pparts);
                     // Store this path so new know it was already checked (so multi_wildcards doesn't re-check the same path, if enabled)
                     $checked[$path.$current_file] = TRUE;

							if (Kohana::find_file('views', $path.$current_file) !== FALSE)
							{
                        $override_obj = array('file'=> $path.$current_file, 'name'=> $current_name);
                        // @todo self::set_override_cache_key($path_override_key, $override_obj);
                        return $override_obj;
                     }
                     // Replace this part with the actual part value again
                     $pparts[$i] = $part;
                  }
               } // end [if ($wildcards) {}]

               // If multiple wild cards are allowed (ie '_' in a filename can replace ANY or ALL parts of the filename)
               if ($multi_wildcards) {

                  // Generate all possible combinations, excluding start and end in gray code
                  $n = count($path_parts);
                  $combos = array();
                  for ($k = 1; $k < $n; $k++) {
                     $combo = gray_code::enumNK($n, $k);
                     $combos = array_merge($combo,$combos);
                  }

                  // Check each possible combination of wildcards
                  foreach ($combos as $code)
                  {
                     // Store parts in temp array
                     $pparts = $path_parts;

                     // Convert this combination into an array
                     $code_arry = str_split($code);

                     // Loop through each character in the code (it's either 1 or 0)
                     foreach ($code_arry as $i => $v) {
                        // Replace all '1' entries in this code with a '_' in the potential path
                        if ($v == '1') $pparts[$i] = '_';
                     }
                     // Generate possible file name
                     $current_file = $name.".".implode('.',$pparts).$ext;

                     // Generate expected classname
                     $current_name = $name."_".implode('_',$pparts);

                     // Ensure this potential file path hasn't already been checked
                     if (!isset($checked[$path.$current_file]) || !$checked[$path.$current_file])
                     {
                        // Mark this potential file path as checked
                        $checked[$path.$current_file] = TRUE;

                        // Check for the file
                        if (file_exists($path.$current_file))
                        {
                           $return_file = $path.$current_file;
                           $override_obj = array('file'=> $return_file, 'name'=> $current_name);
                           // @todo self::set_override_cache_key($path_override_key, $override_obj);
                           return $override_obj;
                        }
                     }
                  }
               } // end if ($multi_wildcards) {}
            }
         }
      }

      // Loop through each path that we want to search, first found gets returned
      foreach ($paths as $key => $path)
      {
			// Ensure paths have trailing slash only
			$path = trim($path, '/').'/';

         // Look for the parent override file
         // @todo re-enable this
         /*if (!empty($parent) && file_exists($path.$name.'.'.$parent.$ext)) {
            $return_file = $path.$name.'.'.$parent.$ext;
            $override_obj = array('file'=> $return_file, 'name'=> $name.'_'.$parent);
            self::set_override_cache_key($path_override_key,$override_obj);
            return $override_obj;
         }*/

         // Look for the requested file (default; no URI override)
         if (Kohana::find_file('views', $path.$name) !== FALSE)
         {
            $override_obj = array('file'=> $path.$name, 'name'=> $name);
            //@todo self::set_override_cache_key($path_override_key,$override_obj);
            return $override_obj;
         }
      }

      return FALSE;
	}

	/**
	 *  binds a key/value to $this->template if it is set as a view, otherwise it stores the bind key/value.  Calling with no params will bind all stored key/values to $this->template if it is a view
	 */
	public function bind($key = NULL, &$val = NULL) 
	{	
		//if a key and value was sent
		if ($key !== NULL && $val !== NULL) 
	    {
			//check to see if template was already set as a view
			if ($this->template instanceof View) 
			{
				$this->template->bind($key,$val);
				$this->binds[$key] = &$val;
			}
			//if the template is just a string, store the key/value on the object for later binding
			else
			{	
				$this->binds[$key] = &$val;
			}
		}
		// if key/value was not sent
		else
		{
			$this->bind_vars('template');
		}
		
	}

	/**
	 *  binds all vars bound to the controller to a template view
	 *  @param $template (string) send string name of template to bind this template too
	 *  @return null
	 */
	public function bind_vars($template = 'template')
	{
		//check to see if template is set as a view
		if (isset($this->templates[$template]) && $this->templates[$template] instanceof View) 
		{

			$this->templates[$template]->bind('controller',$this);
			$this->templates[$template]->bind('request',$this->request);	
					
			$keys = array_keys($this->binds);
			foreach ($keys as $key)
			{
				$this->templates[$template]->bind($key,$this->binds[$key]);
			}
		}
	}

	/**
	 *  sets or returns the media string
	 *  @param $media (string) send value to set $this->_media.  if not sent, $this->_media is init and/or returned
	 *  @return (string) media string based on request user agent
	 */
	public function media($media = NULL) 
	{
		if (is_null($media))
		{
			if (is_null($this->_media))
			{
				$this->_media = $this->init_media();
			}
			return $this->_media;
		}
		else
		{
			$this->_media = $media;
		} 		
	}

	/**
	 *  returns a value for media type based on user agent
	 *  @return (string) media string based on request user agent
	 */	
	public function init_media()
	{
	    if ($this->request->is_web()) 
	    {
	        return 'web';
	    }
		else if ($this->request->is_tablet()) 
	    {
	        return 'tablet';
	    }
		else if ($this->request->is_mobile()) 
	    {
	        return 'mobile';
	    }
        else 
		{
		    return 'web';
		}
	}
	
	/**
	 *  returns or sets theme string based on request/site context/settings
	 *  @param $theme (string) send a value to set a theme.  
	 *  @return (string) returns $this->_theme, if it is not yet set, $this->_default_theme is used
	 */
	public function theme($theme = NULL) 
	{
		if (is_null($theme))
		{
			if (is_null($this->_theme))
			{
				$this->_theme = $this->_default_theme;
			}
			return $this->_theme;
		}
		else
		{
			$this->_theme = $theme;
		}
	}
	
	
	/**
	 *  loads the theme init.php file(s).  Checks for /init.php , {$theme}/init.php, {$theme}/{$controller}/init.php
	 */
	public function init_theme() 
	{
		$theme = $this->theme();
		$media = $this->media();
		$controller = $this->request->controller();
	
		//check for /init		
		$init = 'init';
		
		if (Kohana::find_file('views', $init) !== FALSE)
		{
			$init_template = View::factory($init);
			$init_template->bind('controller',$this);
			$init_template->bind('request',$this->request);
			$init_template->render();
		}	
		
		//check for /theme/init		
		$theme_init = $theme.'/init';
		if (Kohana::find_file('views', $theme_init) !== FALSE)
		{
			$init_template = View::factory($theme_init);
			$init_template->bind('controller',$this);
			$init_template->bind('request',$this->request);			
			$init_template->render();
		}

		//check for /theme/media/init		
		$theme_media_init = $theme.'/'.$media.'/init';
		if (Kohana::find_file('views', $theme_media_init) !== FALSE)
		{
			$init_template = View::factory($theme_media_init);
			$init_template->bind('controller',$this);
			$init_template->bind('request',$this->request);			
			$init_template->render();
		}		
		
		//check for /theme/default/controller/init
		$theme_controller_init = $theme.'/default/'.$controller.'/init';
		if (Kohana::find_file('views', $theme_controller_init) !== FALSE) 
		{
			$init_template = View::factory($theme_controller_init);
			$init_template->bind('controller',$this);
			$init_template->bind('request',$this->request);			
			$init_template->render();
		} 		

		//check for /theme/media/controller/init
		$theme_media_controller_init = $theme.'/'.$media.'/'.$controller.'/init';
		if (Kohana::find_file('views', $theme_media_controller_init) !== FALSE) 
		{
			$init_template = View::factory($theme_media_controller_init);
			$init_template->bind('controller',$this);
			$init_template->bind('request',$this->request);			
			$init_template->render();
		} 		

	}
	
	/**
	 *  returns path to files dir
	 * @returns file_dir (string)
	 */
	public function get_files_dir() 
	{
		if (!isset($this->files_dir) || is_null($this->files_dir)) {
			$this->files_dir = DOCROOT.'application'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
		}
		return $this->files_dir;	
	}
	
	/**
	 *  returns relative path to files dir (used in a url)
	 * @returns file_path (string)
	 */
	public function get_files_path() 
	{ 
		if (!isset($this->files_path) || is_null($this->files_path)) {
			$this->files_path = Kohana::$base_url.'application/cache/';
		}
		return $this->files_path;
	}
	
	/**
	 *  Displays a view using the block_controller. This means that it is not wrapped by the standard index or body templates and can be called all by itself by esi
	 */	
	public function block($params) 
	{
		//if $params is not an array, assume it is the uri
		if (!is_array($params)) 
		{
			$uri = $params;
		} 
		//uri is extracted from params and any other params are treated as _GET params to the request
		else 
		{
			$uri = $params['uri'];
			unset($params['uri']);
		}
		//create sub request to block controller
		$request = new Request($uri);
		
		//assign any extra params to _GET
		if (!empty($params)) 
		{
			$request->query($params);
		}
		
		//echo request response
		echo $request->execute();

	}
	
	/**
	 *  Displays a view usig the current controller
	 */	
	public function view($params) 
	{
		//if $params is not an array, assume it is the uri
		if (!is_array($params)) 
		{
			$view = $params;
		} 
		//uri is extracted from params and any other params are treated as _GET params to the request
		else 
		{
			$view = $params['view'];
			unset($params['view']);
		}
		
		//include view file by name and store it in $this->{$view}_template
		$view_template = $this->set_template($view,$view);
		
		echo $view_template->render();
	}


}