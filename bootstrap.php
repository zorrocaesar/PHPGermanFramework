<?php
// bootstrap.php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once "vendor/autoload.php";

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);

// database configuration parameters

$dbParams = array(
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => 'a',
    'dbname'   => 'gearman',
);

// obtaining the entity manager
$entityManager = EntityManager::create($dbParams, $config);
