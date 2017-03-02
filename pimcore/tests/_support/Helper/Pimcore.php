<?php

namespace Pimcore\Tests\Helper;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Kernel;
use Pimcore\Model\Tool\Setup;

class Pimcore extends Module\Symfony
{
    /**
     * Shares DB initialization state between multiple module instances
     * @var bool
     */
    protected static $dbInitialized = false;

    /**
     * @inheritDoc
     */
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        // simple unit tests do not need a test DB and run
        // way faster if no DB has to be initialized first, so
        // we enable DB support on a suite level
        $this->config = array_merge($this->config, [
            'initialize_db'         => false,
            'force_reinitialize_db' => false,
        ]);

        parent::__construct($moduleContainer, $config);
    }

    /**
     * @return Pimcore|Module
     */
    public function getPimcoreModule()
    {
        return $this->getModule(__CLASS__);
    }

    /**
     * @return \Symfony\Component\HttpKernel\Kernel|Kernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    public function _initialize()
    {
        // don't initialize the kernel multiple times if running multiple suites
        // TODO can this lead to side-effects?
        if (null !== $kernel = \Pimcore::getKernel()) {
            $this->kernel = $kernel;
        } else {
            $this->initializeKernel();
        }

        // (re-)initialize DB if DB support was requested when
        // loading the module
        if ($this->config['initialize_db']) {
            if (!static::$dbInitialized || $this->config['force_reinitialize_db']) {
                $this->initializeDb();
                static::$dbInitialized = true;
            }
        }
    }

    /**
     * Initialize the kernel (see parent Symfony module)
     */
    protected function initializeKernel()
    {
        Config::setEnvironment($this->config['environment']);

        $maxNestingLevel   = 200; // Symfony may have very long nesting level
        $xdebugMaxLevelKey = 'xdebug.max_nesting_level';
        if (ini_get($xdebugMaxLevelKey) < $maxNestingLevel) {
            ini_set($xdebugMaxLevelKey, $maxNestingLevel);
        }

        $this->kernel = require_once __DIR__ . '/../../../config/startup.php';
        $this->kernel->boot();

        if ($this->config['cache_router'] === true) {
            $this->persistService('router', true);
        }

        // disable cache
        Cache::disable();
    }

    /**
     * Initialize the test DB
     *
     * TODO this currently fails if the DB does not exist. Find a way
     * to drop and re-create the DB with doctrine, even if the DB does
     * not exist yet.
     */
    protected function initializeDb()
    {
        $connection = $this->connectDb();

        if (!($connection instanceof Connection)) {
            $this->debug('[DB] Not initializing DB as the connection failed');
            return;
        }

        $this->debug(sprintf('[DB] Initializing DB %s', $connection->getDatabase()));

        $connection
            ->getSchemaManager()
            ->dropAndCreateDatabase($connection->getDatabase());

        $this->debug(sprintf('[DB] Successfully dropped and re-created DB %s', $connection->getDatabase()));

        /** @var Setup|Setup\Dao $setup */
        $setup = new Setup();
        $setup->database();

        $setup->contents([
            'username' => 'admin',
            'password' => microtime()
        ]);

        $this->debug(sprintf('[DB] Set up the test DB %s', $connection->getDatabase()));

        define('PIMCORE_TEST_DB_INITIALIZED', true);
    }

    /**
     * Try to connect to the DB and set constant if connection was successful.
     *
     * @return bool|\Doctrine\DBAL\Connection
     */
    protected function connectDb()
    {
        $container  = \Pimcore::getContainer();
        $connection = $container->get('database_connection');
        $connected  = false;

        try {
            if (!$connection->isConnected()) {
                $connection->connect();
            }

            $this->debug(sprintf('[DB] Successfully connected to DB %s', $connection->getDatabase()));

            $connected = true;
        } catch (ConnectionException $e) {
            $this->debug(sprintf('[DB] Failed to connect to DB: %s', $e->getMessage()));
        }

        if ($connected) {
            return $connection;
        }
    }
}