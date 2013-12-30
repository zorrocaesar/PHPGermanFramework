<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

class DatabaseAccess {

    /**
     * @var \Doctrine\ORM\EntityManager;
     */
    protected static $entityManager;

    protected function __construct() {

        // obtaining the entity manager
    }

    public static function getInstance() {
        if (!isset(self::$entityManager)) {
            $isDevMode = true;
            $config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/../config/yaml"), $isDevMode);

            // database configuration parameters

            $dbParams = array(
                'driver'   => 'pdo_mysql',
                'user'     => 'root',
                'password' => 'a',
                'dbname'   => 'gearman'
            );

            self::$entityManager = EntityManager::create($dbParams, $config);
        }
        return self::$entityManager;
    }
} 