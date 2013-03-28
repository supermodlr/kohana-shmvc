<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Page extends Controller_Theme {

   /**
    * Default index template file to use
    */
   public $index_template = NULL;	 
   public $default_index_file = 'rootindex';	 
	
   public $body_template = NULL;
   public $default_body_file = 'body';	 
	
   public $content_type = 'html';
	
	public $index_vars = array(
		'title' => '',
		'doctype' => array(),
		'htmlattrs' => array(),
		'metatags' => array(),
		'heasdertags' => array(),
		'headertags' => array(),
		'bodyattrs' => array(),
		'body' => NULL,
		'content' => NULL		
	);
	public $js_inline = array('headerinline','readyinline');
	public $js_tags = array('headertags','bodytags','footertags','loadinline','bodyinline','footerinline');
	public $javascript = array();
	public $css = array();	
	
	/**
	 * Initializes theme, templates, and global view variables
	 */
	public function before()
	{
		parent::before();

		//if there is a body template
		if (!is_null($this->default_body_file)) 
		{
			//get the body template
			$this->set_template($this->default_body_file,'body'); 
		
			//init theme template defaults
			$this->init_theme();					
		}
		//bind all index vars to all views so that any controller can set values to them
		foreach ($this->index_vars as $var => $val) 
		{
			View::bind_global($var, $this->index_vars[$var]);
		}
		
	}

	/**
	 * Assigns the template [View] as the request response.
	 */
	public function after()
	{
		if ($this->auto_render === TRUE)
		{
			//if body wasn't already set by the controller, we load the index controller
			if (is_null($this->body())) 
			{		
    			//get index template
				$this->set_template($this->default_index_file,'index'); 
				
				//set template response to index body
				$this->body($this->templates['body']->render());				
				
				//set response body to index render
			    $this->response->body($this->templates['index']->render());				
			}
			else 
			{
			   $this->response->body($this->body());
			}
		}
	}
	
	
	/**
	 *  ads js code to the template
	 */
	public function js($js = NULL, $position = 'footer', $weight = 10, $defer = FALSE) 
	{
		 // If this is js code and not a path
		if (in_array($position,$this->js_inline)) 
		{
			if (!isset($this->javascript[$position])) $this->javascript[$position] = array();
			$this->javascript[$position][] = $js;
		} 
		else 
		{
			//ensure this file hasn't already been included
			if (!isset($this->javascript['all'][$js])) {
				$this->javascript[$position][$weight][] = array('src'=> $js, 'defer'=> $defer);
				$this->javascript['all'][$js] = TRUE;
			}
		 }
	}
	
	/**
	 *  returns js tags
	 *  aggregates js files
	 *  minifies js files
	 */
	public function get_js($position, $defer = FALSE) 
	{
      // If this is js code and not a path
      if (in_array($position,$this->js_inline))
      {
         if (!isset($this->javascript[$position]) || !is_array($this->javascript[$position]))
            return '';


         $script = implode(PHP_EOL,$this->javascript[$position]);

         if (!empty($script))
         {
            if ($position == 'ready')
            {
               $script = '$(document).ready(function(){'.$script.'});';
            }
            else if ($position == 'load')
            {
               $script = '$(window).load(function(){'.$script.'});';
            }
         return '
<script type="text/javascript">
//<![CDATA[
'.$script.'
//]]>
</script>
';
         } 
         else
            return '';

      
      }
      // Sort by weight and aggregate all paths for this position
      else
      {

         // Stores all src's and filemtime's to create the aggregated file hash
         $hash_context = '';
         $external = '';
         $internal = '';
         $protocol = $this->request->protocol();
         $files_dir = $this->get_files_dir();
         $apppath = APPPATH;
         $modpath = MODPATH.'supermodlr/';

         if (!isset($this->javascript[$position]) || !is_array($this->javascript[$position]))
            $this->javascript[$position] = array();

        // Loop through each weight key by sent position
        foreach ($this->javascript[$position] as $weight => $js_set)
        {

            // Loop through each js src at this weight level
            foreach ($js_set as $i => $js)
            {

               // If this is an external include
               if (substr($js['src'],0,7) == 'http://' || substr($js['src'],0,8) == 'https://')
               {

                  // Look for defer in either get_js call or add_js call
                  $defer_attr = ($defer || $js['defer']) ? ' defer="defer"' : '';

                  // Force https if called from https but referenced as http
                  if (substr($js['src'],0,5) == 'http:' && $protocol == 'https') $js['src'] = preg_replace('/^http:\/\/','https://',$js['src']);

                  // Add to external list
                  $external .= '<script type="text/javascript" src="'.$js['src'].'"'.$defer_attr.'></script>'.PHP_EOL;

                  // Unset so the aggregator doesn't try to include it
                  unset($this->javascript[$position][$weight][$i]);

               
               }
               // If this is an internal include
               else
               {

                  // Remove the leading forward slash, if present
                  $js['src'] = trim($js['src'], '/');

                  // Make sure the file exists
                  if (file_exists($apppath.$js['src'])) {
                     $mtime = filemtime($apppath.$js['src']);
                     $hash_context .= $js['src'].$mtime;
                  } else if (file_exists($modpath.$js['src'])) {
                     $mtime = filemtime($modpath.$js['src']);
                     $hash_context .= $js['src'].$mtime;

                  // File does not exist, throw an error
                  } else {
                     throw new Kohana_Exception('Cannot get_js :dir',
                        array(':dir' => Debug::path($apppath.$js['src']).' OR '.Debug::path($modpath.$js['src']) ));				  

                     // Remove this src from the list of valid files
                     unset($this->javascript[$position][$weight][$i]);
                  } // End foreach js_set
               }

            } // End foreach js[position]

         } // End file_exists

         // If no js, return nothing
         if ($hash_context != '')
         {

            // Calculates the hash
            $file_hash = md5($hash_context);

            // Get full file path and name
            $file_name = $files_dir.$file_hash.'.js';

            // Checks to see if this file has been created yet. if not, then create it
            if (TRUE) { //(!file_exists($file_name)) {

               $aggregated = '';

               // Loop through each weight key by sent position
               foreach ($this->javascript[$position] as $weight => $js_set) {

                  // Loop through each js src at this weight level
                  foreach ($js_set as $js) {

                     // Assume file exists since it is checked during the hash generation
					 if (file_exists($apppath.$js['src']))
					 {
						$js_contents = file_get_contents($apppath.$js['src']);
					 }
					 
					 else if (file_exists($modpath.$js['src']))
					 {
						$js_contents = file_get_contents($modpath.$js['src']);
					 }
					 else
					 {
						$js_contents = FALSE;
					 }
                     
                     if ($js_contents !== FALSE) {
                        // Minify the file
                        //require_once LIBPATH.'jsmin/jsmin.php';
                        //$js_contents_minified = JSMin::minify($js_contents);
						$js_contents_minified = $js_contents;
                        $aggregated .= PHP_EOL.'/*! start '.$js['src'].'*/'.PHP_EOL.$js_contents_minified.PHP_EOL.'/*! end '.$js['src'].'*/'.PHP_EOL;

                     //could not file_get_contents
                     } else {
						throw new Kohana_Exception('could not file_get_contents :dir',
							array(':dir' => Debug::path($files_dir.$js['src'])));	
                     }

                  } // End foreach js_set

               } // End foreach js[position]

               // Save aggregated file
               if ($aggregated !== FALSE) {
                  $file_write = file_put_contents($file_name,$aggregated);
                  if ($file_write === FALSE) {
					throw new Kohana_Exception('could not file_put_contents :dir',
						array(':dir' => Debug::path($file_name)));					  
                  }

                // If minification failed
                } else {
					throw new Kohana_Exception('Could not JSMin::minify!');				   

                }

            } // End file_exists

            // If defered, create attr
            $defer_attr = ($defer) ? ' defer="defer"' : '';

            // Get file relative url
            $file_url = $this->get_files_path().$file_hash.'.js';
            $internal = '<script type="text/javascript" src="'.URL::base($this->request).trim($file_url,'/').'"'.$defer_attr.'></script>';
         }

         // Returns the formatted script tag
         return $external.$internal.PHP_EOL;
      }
	}

	/**
	 *  adds header tags to the template
	 */
	public function headertags($tag = NULL) 
	{
		if (is_null($tag))
		{
			$str = '';
			foreach ($this->index_vars['headertags'] as $header_tag)
			{
				$str .= '<'.$header_tag['tag'].' ';
				foreach ($header_tag as $attr => $val) 
				{
					if ($attr == 'tag') continue;
					$str .= $attr.'="'.$val.'"';
				}
				
				$str .= ' />'.PHP_EOL;
			}
			return $str;
			
		}
		else if (is_array($tag))
		{
			$this->index_vars['headertags'][] = $tag;
		}
	}	
	
	/**
	 *  adds css file to the template
	 */
	public function css($css = NULL,$media = 'screen') 
	{
		if (is_null($css))
		{
			$str = '';
			foreach ($this->css as $css)
			{
				$str .= '<link style="text/css" rel="stylesheet" href="'.$css['href'].'" media="'.$css['media'].'"/>'.PHP_EOL;
			}
			return $str;
			
		}
		else
		{
			$this->css[] = array('href'=> $css, 'media'=> $media);
		}
	}	
	
	/**
	 *  adds or retrieves content
	 */
	public function content($content = NULL) 
	{	
	   if (is_null($content)) 
	    {
			//if no content was set directly, find a file based on the action
			if (is_null($this->index_vars['content'])) 
			{
				//use action name as default content view
				if ($this->template === NULL)
				{
					$this->set_template($this->request->action());	
				}			
				//if the controller set a template name
				else
				{
					$this->set_template($this->template);	
				}

				//bind all vars
				$this->bind();
				
				//render content template
				$this->index_vars['content'] = $this->template->render();
			}
			return $this->index_vars['content'];
		}
		else 
		{
			$this->index_vars['content'] = $content;
		}
	}		
	
	/**
	 *  adds or retrieves body
	 */
	public function body($body = NULL) 
	{	
	    if (is_null($body)) 
	    {
			//if no body was set directly, find a file based on the default_body_file if it is not null
		/*	if (is_null($this->index_vars['body']) && is_object($this->body_template) && $this->body_template instanceof SHMVC_View) 
			{		
				//render content template
				$this->index_vars['body'] = $this->body_template->render();
			}*/
			return $this->index_vars['body'];
		}
		else 
		{
			$this->index_vars['body']= $body;
		}
	}	
	
	/**
	 *  adds or retrieves title
	 */
	public function title($title = NULL) 
	{	
	   if (is_null($title)) 
	   {
			return $this->index_vars['title'];
		}
		else 
		{
			$this->index_vars['title']= $title;
		}
	}	
	
	/**
	 *  adds or retrieves doctype
	 */
	public function doctype($doctype = NULL) 
	{	
	   if (is_null($doctype)) 
	   {
			return implode('',$this->index_vars['doctype']);
		}
		else 
		{
			$this->index_vars['doctype'][] = $doctype;
		}
	}
	
	/**
	 *  adds or retrieves htmlattrs
	 */
	public function htmlattrs($attr = NULL, $val = NULL) 
	{	
	    if ($attr === NULL) 
	    {
			if (empty($this->index_vars['htmlattrs'])) 
			{
				return '';
			}
			else
			{
				$attr_arry = array();
				foreach ($this->index_vars['htmlattrs'] as $attr => $attrval)
				{
					if ($attrval === NULL)
					{
						$attr_arry[] = $attr;
					}
					else
					{
						$attr_arry[] = $attr.'="'.HTML::chars($attrval).'"';
					}
				}
				return ' '.implode(' ',$attr_arry);
			}
			
		}
		else 
		{
			$this->index_vars['htmlattrs'][$attr] = $val;
		}
	}	
	
	/**
	 *  adds or retrieves metatags
	 */
	public function metatags($metatags = NULL) 
	{	
	   if (is_null($metatags)) 
	   {
			return implode(PHP_EOL,$this->index_vars['metatags']);
		}
		else 
		{
			$this->index_vars['metatags'][] = $metatags;
		}
	}	
	
	/**
	 *  adds or retrieves bodyattrs
	 */
	public function bodyattrs($attr = NULL, $val = NULL) 
	{	
	    if ($attr === NULL) 
	    {
			if (empty($this->index_vars['bodyattrs']))
			{
				return '';
			}
			else
			{
				$attr_arry = array();
				foreach ($this->index_vars['bodyattrs'] as $attr => $attrval)
				{
					if ($attrval === NULL)
					{
						$attr_arry[] = $attr;
					}
					else
					{
						$attr_arry[] = $attr.'="'.HTML::chars($attrval).'"';
					}
				}
				return ' '.implode(' ',$attr_arry);
			}
			
		}
		else 
		{
			$this->index_vars['bodyattrs'][$attr] = $val;
		}
	}		
	

}
