<?php defined('SYSPATH') or die('No direct script access.');

//load the environment (development/testing/staging/production) overrides config reader
Kohana::$config->attach(new Config_File('config/'.strtolower(Kohana::$environment)));

//load environment variable config file reader
Kohana::$config->attach(new Config_Env());

/*
$_SERVER['KOHANA_APP_VAR'] = '["ENV"]';
$_SERVER['KOHANA_APP_ARRAY'] = '{"key1": "env value1", "key3": "env value1"}';
*/

// Make the sitespath relative to the apppath, for symlink'd index.php
$sitespath = 'sites';
if ( ! is_dir($sitespath) AND is_dir(APPPATH.$sitespath))
   $sitespath = APPPATH.$sitespath;

define('SITESPATH', realpath($sitespath).DIRECTORY_SEPARATOR);

