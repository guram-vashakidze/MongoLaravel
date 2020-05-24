<?php

namespace App\Module\Mongo;

/**
 * Class Setting
 * Setting data for MongoConnection
 * @package App\Service\Mongo
 */
class MongoSetting
{

    /**
     * @var string - connection host
     */
    private $host;

    /**
     * @var string - connection port
     */
    private $port;

    /**
     * @var string - connection user name
     */
    private $user;

    /**
     * @var string - connection password
     */
    private $password;

    /**
     * @var string - default DB
     */
    private $db;

    /**
     * @var boolean - debug mode
     */
    private $debug;

    public function __construct()
    {
        $this->host = config('database.mongo.host');
        $this->port = config('database.mongo.port');
        $this->user = config('database.mongo.user');
        $this->password = config('database.mongo.password');
        $this->debug = config('database.mongo.debug');
        $this->setDb();
    }

    /**
     * get connection URL
     * @return string
     */
    public function getUrl(): string
    {
        return 'mongodb://' . $this->host . ':' . $this->port;
    }

    /**
     * get auth data
     * @return array
     */
    public function getAuth(): array
    {
        return !$this->user || !$this->password ? [] : ['username' => $this->user, 'password' => $this->password];
    }

    /**
     * get debug mod
     * @return bool
     */
    public function getDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Set name DB
     * @param string $db
     */
    public function setDb(string $db = null)
    {
        $this->db = $db !== null ? $db : config('database.mongo.db');
    }

    /**
     * Return set db name
     * @return string
     */
    public function getDb(): string {
        return $this->db;
    }
}
