<?php

require_once __DIR__ . '/../vendor/autoload.php';
error_reporting(-1);
ini_set('display_errors', 1);

$configs = new \ultimo\util\config\IniConfig(__DIR__ . '/../config.ini');
$config = $configs->getSection('master');

if (!$config['development']) {
  $errorHandler = new \ultimo\debug\error\ErrorCarer(array(
      'print_errors' => false,
      'email_to' => $config['errorcarer']['email_to'],
      'email_from' => $config['errorcarer']['email_from'],
      'email_subject' => '[Errorcarer] %s: \'%s\' (code %s) in %s:%s',
      'response' => 'An error has occured and has been mailed'
   ));
  $errorHandler->register();
  
  register_shutdown_function(function() use ($errorHandler) {
    $errorHandler->unregister();
  });
}

$pdoConnection = new PDO($config['db']['dsn'], $config['db']['username'], $config['db']['password']);
$manager = new \ultimo\orm\Manager($pdoConnection);
$manager->associateModel('mld2gc_leaverequest', 'Leaverequest', 'mld2gc\models\Leaverequest');
$manager->associateModel('mld2gc_setting', 'Setting', 'mld2gc\models\Setting');

