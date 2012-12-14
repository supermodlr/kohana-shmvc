<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Theme extends Controller_Template {

	public $template = NULL;
	public $templates = array();
	public $binds = array();
	protected $_theme = NULL;
	protected $_default_theme = 'default';
	protected $_media = NULL;
	
	
	/**
	 * Initializes theme, templates, and global view variables
	 */
	public function before()
	{
		//detect theme
		$this->theme = $this->theme();
				
		//defeat varnish cache for now
		$this->response->headers('Cache-Control', 'no-cache, must-revalidate');	

		$this->app_config = Kohana::$config->load('supermodlr');
		
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
		$theme = $this->theme();
		$media = $this->media();
		$controller = $this->request->controller();
	
		//check for /theme/media/controller/file
		$theme_media_controller_file = $theme.'/'.$media.'/'.$controller.'/'.$file;

		//check for /theme/media/file
		$theme_media_file = $theme.'/'.$media.'/'.$file;

		//check for /theme/default/controller/file
		$theme_controller_file = $theme.'/default/'.$controller.'/'.$file;
		
		//check for /theme/file		
		$theme_file = $theme.'/'.$file;

		if (Kohana::find_file('views', $theme_media_controller_file) !== FALSE) 
		{
		    $file = $theme_media_controller_file;
		} 
		else if (Kohana::find_file('views', $theme_media_file) !== FALSE)
		{
			$file = $theme_media_file;
		}
		else if (Kohana::find_file('views', $theme_controller_file) !== FALSE)
		{
			$file = $theme_controller_file;
		}		
		else if (Kohana::find_file('views', $theme_file) !== FALSE)
		{
			$file = $theme_file;
		}
		//check for /file  fail if it doesn't exist		
		else if (Kohana::find_file('views', $file) === FALSE)
		{
			return FALSE;
		}
	    return $file;
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