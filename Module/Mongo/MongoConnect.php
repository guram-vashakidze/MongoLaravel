<?php

namespace App\Module\Mongo;

/**
 * PHP Mongo Driver
 */

use \MongoDB\Driver\Manager as MongoDriver;
use \MongoDB\Driver\Exception\Exception as MongoExceptionInterface;
use \MongoDB\Driver\Exception\ConnectionException;
use \MongoDB\Driver\Exception\CommandException;
use \MongoDB\Driver\Exception\InvalidArgumentException;
use \MongoDB\Driver\Exception\BulkWriteException;
use \MongoDB\Driver\BulkWrite;
use \MongoDB\Driver\Command as MongoCommand;
use \MongoDB\Driver\Query;

/**
 * Class Connect
 * Connection to mongo
 * @package App\Http\Mongo
 */
class MongoConnect
{

    /**
     * Connection data
     * @var null|array
     */
    protected $connectionData = null;

    /**
     * Connect to mongo
     * @var MongoDriver
     */
    private $connect;

    /**
     * Bulk object
     * @var BulkWrite;
     */
    private $bulk = null;

    /**
     * Bulk type job
     * @var null|string
     */
    private $bulkType = null;

    /**
     * Last inserting ID
     * @var array|string|int|float|\MongoDB\BSON\ObjectId
     */
    private $lastInsertId = null;

    /**
     * Connect constructor.
     * @param MongoSetting $connection
     */
    public function __construct(MongoSetting $connection)
    {
        $this->connectionData = $connection;
        //Connect to mongo
        $this->connectionToDb();
    }

    /**
     * Connect to mongo
     */
    private function connectionToDb()
    {
        $this->connect = new MongoDriver($this->connectionData->getUrl(), $this->connectionData->getAuth());
        if ($this->checkConnect() === false) $this->connect = null;
    }

    /**
     * Check connect to DB
     * @return bool
     */
    private function checkConnect(): bool
    {
        //ping mongo db
        $result = $this->executeCommand(['ping' => 1]);
        //not ping
        if ($result === null) return false;
        if (empty($result[0]->ok)) {
            if ($this->connectionData->getDebug()) throw new ConnectionException('Failed to connect to MongoDB');
        }
        unset($command);
        return true;
    }

    /**
     * execute command to mongo
     * @param array $command
     * @return array|null
     */
    protected function executeCommand(array $command): ?array
    {
        //if non conect
        if ($this->connect === null) {
            //set error
            if ($this->connectionData->getDebug())
                throw new ConnectionException('Failed to connect to MongoDB');
            return null;
        }
        //Create mongo command
        $_command = new MongoCommand($command);
        //Execute mongo command
        try {
            $result = $this->connect->executeCommand($this->connectionData->getDb(), $_command);
        } catch (MongoExceptionInterface $e) {
            if ($e->getCode() === 26) return null;
            //Set error
            if ($this->connectionData->getDebug())
                throw new CommandException($e->getMessage(), $e->getCode(), $e);
            return null;
        }
        unset($_command);
        return $result->toArray();
    }

    /**
     * Execute query mongo
     * @param array $query - query data
     * @param string $collection - query collection
     * @param array $options - query options
     * @return array|null
     */
    protected function executeQuery(array $query,string $collection,array $options = []): ?array
    {
        try {
            $query = new Query($query, $options);
        } catch (InvalidArgumentException $e) {
            //Set error
            if ($this->connectionData->getDebug())
                throw new InvalidArgumentException($e->getMessage(),$e->getCode(),$e);
            return null;
        }
        //Run query
        try {
            $result = $this->connect->executeQuery($this->connectionData->getDb() . '.' . $collection, $query);
        } catch (MongoExceptionInterface $e) {
            //Set error
            if ($this->connectionData->getDebug())
                throw new CommandException($e->getMessage(), $e->getCode(), $e);
            return null;
        }
        return $result->toArray();
    }

    /**
     * Save last insert id
     * @param array|string|int|float|\MongoDB\BSON\ObjectId $id
     */
    private function setLastInsertId($id) {
        //if not insert operation
        if ($this->bulkType !== 'insert') return;
        //save id
        $this->lastInsertId = $id;
    }

    /**
     * Get insert id
     * @return array|float|int|\MongoDB\BSON\ObjectId|string
     */
    public function getLastInsertId() {
        return $this->lastInsertId;
    }

    /**
     * Create bulk object/added bulk job
     * @param string $bulkType - insert/update/remove
     * @param array $params - insert = [insertdata]|update = [query,updatedata,options]|delete=[query]
     * @return MongoConnect
     */
    protected function setBulk(string $bulkType,array $params): MongoConnect
    {
        $this->bulkType = $bulkType;
        //Creating bulk object
        if ($this->bulk === null) $this->bulk = new BulkWrite();
        //if insert job - save id
        $this->setLastInsertId(
            //add bulk job
            $this->bulk->{$this->bulkType}(...$params)
        );
        return $this;
    }


    /**
     * Run bulk write query
     * @param string $collection - collection for bulk query
     * @return int|null
     */
    protected function bulkWrite(string $collection): ?int {
        $bulk = $this->bulk;
        unset($this->bulk);
        //Setting bulk query
        try {
            $result = $this->connect->executeBulkWrite($this->connectionData->getDb() . '.' . $collection, $bulk);
        } catch (BulkWriteException $e) {
            if ($this->connectionData->getDebug())
                throw new BulkWriteException($e->getMessage(),$e->getCode(),$e);
            return null;
        } catch (InvalidArgumentException $e) {
            if ($this->connectionData->getDebug())
                throw new BulkWriteException($e->getMessage(),$e->getCode(),$e);
            return null;
        }
        return $result->{'get'.['update' => 'Modified','insert' => 'Inserted','delete' => 'Deleted'][$this->bulkType].'Count'}();
    }

}
