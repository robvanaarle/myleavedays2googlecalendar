<?php

require './vendor/autoload.php';
error_reporting(-1);
ini_set('display_errors', 1);

// Read config
$configs = new \ultimo\util\config\IniConfig('./config.ini');
$config = $configs->getSection('master');

// Create PDO connection
$pdoConnection = new PDO($config['db']['dsn'], $config['db']['username'], $config['db']['password']);

// Create tables
$tables = array(
  'CREATE TABLE IF NOT EXISTS `mld2gc_leaverequest` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `hours` decimal(6,2) NOT NULL,
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `start_date_hours` decimal(6,2) NOT NULL,
    `end_date_hours` decimal(6,2) NOT NULL,
    `event_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
    `approved` tinyint(4) NOT NULL,
    `deleted` tinyint(4) NOT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;',
    
  'CREATE TABLE IF NOT EXISTS `mld2gc_setting` (
    `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `value` longtext COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY (`name`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;'
);

echo "Creating tables...";
foreach ($tables as $query) {
  $pdoConnection->query($query);
}
echo "ok\n";


// Initialize Google Client
$gClient = new Google_Client();
$gClient->setApplicationName($config['google']['application_name']);
$gClient->setScopes(Google_Service_Calendar::CALENDAR);
$gClient->setAuthConfig($config['google']['client_secret']);
$gClient->setAccessType('offline');

echo "\n";
echo "Visit the following url in your browser: \n" . $gClient->createAuthUrl() . "\n";
echo "Authorize with the desired Google account and enter verification code: ";
$authCode = trim(fgets(STDIN));
echo "Testing verification code: ";
$accessToken = $gClient->authenticate($authCode);
echo "ok\n";

$statement = $pdoConnection->prepare(
        "INSERT INTO `mld2gc_setting` (`name`, `value`) "
        . "VALUES ('google.access_token', :accessToken) "
        . "ON DUPLICATE KEY UPDATE `value`=:accessToken");
if (!$statement->execute(array('accessToken' => $accessToken))) {
  echo "Could not save access token: " . $pdoConnection->errorInfo();
  exit();
}

echo "Finished automatic configuration, please perform the following manual actions\n";
echo "- settings in config.ini\n";
echo "- create cronjob for tasks\myleavedays2db.php: recommended each 30 mins\n";
echo "- create cronjob for tasks\db2googlecalendar.php: recommended 2 minutes after myleavedays2db.php\n";