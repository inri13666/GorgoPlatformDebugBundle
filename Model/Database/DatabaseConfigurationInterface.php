<?php

namespace Gorgo\Bundle\PlatformDebugBundle\Model\Database;

interface DatabaseConfigurationInterface
{
    const DRIVER_PDO_POSTGRESQL = 'pdo_pgsql';
    const DRIVER_PDO_MYSQL = 'pdo_mysql';

    /**
     * @return bool
     */
    public function isValid();

    /**
     * @return string
     */
    public function getDriver();

    /**
     * @return string
     */
    public function getHost();

    /**
     * @return int
     */
    public function getPort();

    /**
     * @return string
     */
    public function getUser();

    /**
     * @return string
     */
    public function getPassword();

    /**
     * @return string
     */
    public function getDbName();
}
