<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Block extends Controller_Theme {

	public $template = NULL;
	public $body = NULL;
		
	/**
	 * Initializes theme, templates, and global view variables
	 */
	public function before()
	{
		parent::before();
	}

	/**
	 * Assigns the template [View] as the request response.
	 */
	public function after()
	{
		if ($this->auto_render === TRUE)
		{
			//if body wasn't already set by the controller, we load the block controller
			if (is_null($this->body())) 
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
				
				//set response body to index render
			    $this->response->body($this->template->render());				
			}
			else 
			{
			   $this->response->body($this->body());
			}
		}
	}
	
	/**
	 *  adds or retrieves body
	 */
	public function body($body = NULL) 
	{	
	    if (is_null($body)) 
	    {
			return $this->body;
		}
		else 
		{
			$this->body = $body;
		}
	}	
	
}
