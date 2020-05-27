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

    /**
     * @var array - data for increment _id
     */
    private $inc = ['db' => null, 'collection' => null];

    public function __construct()
    {
        $this->host = config('database.mongo.host');
        $this->port = config('database.mongo.port');
        $this->user = config('database.mongo.user');
        $this->password = config('database.mongo.password');
        $this->debug = config('database.mongo.debug');
        //Set name DB
        $this->setDb();
        //set data for inc _id
        $this->setIncCollections();
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
    public function getDb(): string
    {
        return $this->db;
    }

    /**
     * set data for inc _id
     */
    private function setIncCollections()
    {
        //Get default collection/db
        $data = config('database.mongo.inc');
        if (empty($data)) return;
        //split data on db and collection
        $data = explode('.', $data);
        //if not data
        if (empty($data[0]) || empty($data[1])) return;
        $this->inc = ['db' => $data[0], 'collection' => $data[1]];
    }

    /**
     * return inc data
     * @return array|null
     */
    public function getIncData(): ?array
    {
        if ($this->inc['db'] === null) return null;
        return $this->inc;
    }
}
