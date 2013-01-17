<?php

//load the environment (development/testing/staging/production) overrides config reader
Kohana::$config->attach(new Config_File('config/'.strtolower($_SERVER['KOHANA_ENV'])));

//load environment variable config file reader
Kohana::$config->attach(new Config_Env());

/*
$_SERVER['KOHANA_APP_VAR'] = '["ENV"]';
$_SERVER['KOHANA_APP_ARRAY'] = '{"key1": "env value1", "key3": "env value1"}';
*/