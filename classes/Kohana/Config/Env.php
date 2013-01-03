<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Config_Env implements Kohana_Config_Reader {

	/**
	 * Load and merge all of the configuration values in this group from enviroment variables formated like: KOHANA_{$GROUP}_{$KEY} = [mixed value] OR [json string of value]
	 *
	 *     $config->load($name);
	 *
	 * @param   string  $group  configuration group name
	 * @return  $this   current object
	 * @uses    Kohana::load
	 */
	public function load($group)
	{
		$config = array();

		$uppercase_group = strtoupper($group);

		foreach ($_SERVER as $key => $var)
		{
			if (substr($key,0,8+strlen($uppercase_group)) === 'KOHANA_'.$uppercase_group.'_')
			{
				$config_key = strtolower(str_replace('KOHANA_'.$uppercase_group.'_', '', $key));

				//attempt to decode this value as a json object
				$value = json_decode($var,TRUE);
				
				//if this is a valid json string
				if (json_last_error() === JSON_ERROR_NONE)
				{
					//assign the resulting object to the config
					$config[$config_key] = $value;
				}
				//this was not valid json, so assign the exact value as stored
				else
				{
					$config[$config_key] = $var;
				}
			}
		}

		return $config;
	}

}