<?php

require_once(implode(DIRECTORY_SEPARATOR,array(__DIR__,'..','..','constants.php')));
$yiit=implode(DIRECTORY_SEPARATOR,array(__DIR__,'..','..','framework','yiit.php'));
$config=require_once(implode(DIRECTORY_SEPARATOR,array(__DIR__,'..','config','test.php')));

// Some last-minute modifications (for unit testing only)
$config['params']['noSession'] = true;
require_once($yiit);
Yii::createWebApplication($config);

?>