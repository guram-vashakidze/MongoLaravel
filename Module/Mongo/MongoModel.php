<?php

namespace App\Module\Mongo;


class MongoModel extends MongoConnect
{

    /**
     * Collection name
     * @var string
     */
    protected $collection;

    /**
     * Result command to DB
     * @var array
     */
    private $result;

    /**
     * Count in result db command
     * @var int
     */
    private $count = null;

    /**
     * Flag for counting result
     * @var boolean
     */
    private $getCount = false;

    /**
     * Flag counting only "count" params
     * @var bool
     */
    private $onlyCount = true;

    public function __construct(MongoSetting $connection)
    {
        parent::__construct($connection);
    }

    /**
     * Set db name
     * @param null|string $db - db name
     * @return $this
     */
    public function db(string $db = null): MongoModel
    {
        $this->connectionData->setDb($db);
        return $this;
    }

    /**
     * Set collection name
     * @param string $collection - collection name
     * @return MongoModel
     */
    public function collection(string $collection = null): MongoModel
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * Getting result query
     * @return array
     */
    public function get()
    {
        //If need count add in result
        if ($this->getCount) {
            $result = $this->onlyCount === false ? ['result' => $this->result, 'count' => $this->count] : $this->count;
        } else {
            $result = $this->result;
        }
        $this->setDefaultParams();
        return $result;
    }

    /**
     * Set default result params
     */
    private function setDefaultParams()
    {
        $this->result = [];
        $this->count = null;
        $this->getCount = false;
        $this->onlyCount = true;
    }

    /**
     * Off flag only count
     */
    private function offOnlyCount()
    {
        $this->onlyCount = false;
    }

    /**
     * Counting result/set flag counting result
     * @param array $query
     * @return MongoModel
     */
    public function count($query = null): MongoModel
    {
        $this->setDefaultParams();
        //set flag counting
        $this->getCount = true;
        //counting after request
        if ($query === null) return $this;
        //if query is aggregate pipeline
        if (!empty($query[0]) && preg_match('/^\$/', array_keys($query[0])[0])) return $this->countAggregate($query);
        //if is find query
        return $this->findCount($query, []);
    }

    /**
     * Get list db in mongo
     * @param bool $onlyName - flag return only name db
     * @return $this
     */
    public function getListDatabases(bool $onlyName = true): MongoModel
    {
        $this->offOnlyCount();
        $result = $this->db('admin')->executeCommand(['listDatabases' => 1]);
        $this->db();
        if ($result === null) return $this;
        $this->result = $onlyName === true ? array_column($result[0]->databases, 'name') : $result[0]->databases;
        $this->count = $this->getCount ? count($this->result) : null;
        return $this;
    }

    /**
     * Get list collection in DB
     * @param bool $onlyName - flag return only name collection
     * @return $this
     */
    public function getListCollections($onlyName = true): MongoModel
    {
        $this->offOnlyCount();
        $result = $this->executeCommand(['listCollections' => 1]);
        if ($result === null) return $this;
        $this->result = $onlyName === true ? array_column($result, 'name') : $result;
        $this->count = $this->getCount ? count($this->result) : null;
        return $this;
    }

    /**
     * Execute aggregate query
     * @param array $pipeline
     * @return $this
     */
    public function aggregate(array $pipeline): MongoModel
    {
        $this->offOnlyCount();
        $this->result = $this->executeCommand(['aggregate' => $this->collection, 'pipeline' => $pipeline, 'cursor' => new \stdClass]);
        //if is it needed counting result
        return $this->countAggregate($pipeline);
    }

    /**
     * Count result in aggregate query
     * @param array $pipeline - query params
     * @return $this
     */
    private function countAggregate(array $pipeline): MongoModel
    {
        //If you do not need to count
        if ($this->getCount === false) return $this;
        //Query for get all items
        $query = [];
        //Unset $limit and $skip items
        foreach ($query as $item) {
            if (!empty($item['$limit']) || !empty($item['$skip'])) continue;
            $query[] = $item;
        }
        //If not $limit/$skip in pipeline
        if ($query === $pipeline) {
            //if error in result
            if ($this->result === null) return $this;
            //counting result
            $this->count = count($this->result);
        } else {
            //Get result for new pipeline
            $result = $this->executeCommand(['aggregate' => $this->collection, 'pipeline' => $query, 'cursor' => new \stdClass]);
            $this->count = count($result);
        }
        return $this;
    }

    /**
     * Run find query
     * @param array $query - query field
     * @param array $options - ['fields' => list field in result, 'limit' => 'limit result','skip' => 'count item skip','sort' => 'sort field/type']
     * @return $this;
     */
    public function find(array $query = [], array $options = []): MongoModel
    {
        $this->offOnlyCount();
        //Checking options query
        $_options = [];
        if (!empty($options['fields'])) $_options['projection'] = $options['fields'];
        if (!empty($options['sort'])) $_options['sort'] = $options['sort'];
        if (!empty($options['limit']) && is_numeric($options['limit'])) $_options['limit'] = (int)$options['limit'];
        if (!empty($options['skip']) && is_numeric($options['skip'])) $_options['skip'] = (int)$options['skip'];
        //Run query
        $this->result = $this->executeQuery($query, $this->collection, $options);
        //if is it needed counting result
        return $this->findCount($query, $options);
    }

    /**
     * Counting find result
     * @param array $query
     * @param array $options
     * @return MongoModel
     */
    private function findCount(array $query, array $options): MongoModel
    {
        //If you do not need to count
        if ($this->getCount === false) return $this;
        //if not limit/skip in options
        if (empty($options['limit']) && empty($options['skip'])) {
            $this->count = count($this->result);
            return $this;
        }
        //Counting result
        $result = $this->executeCommand(['count' => $this->collection, 'query' => $query]);
        //If error
        if ($result === null) return $this;
        //Set count result
        $this->count = $result[0]->n;
        return $this;
    }

    /**
     * Run find one result
     * @param array $query
     * @param array $options
     * @return MongoModel
     */
    private function findOne(array $query = [], array $options = []): MongoModel
    {
        $this->offOnlyCount();
        //Set one result
        $options['limit'] = 1;
        //Getting result
        $this->find($query, $options);
        //Set first result
        $this->result = !empty($this->result[0]) ? $this->result[0] : null;
        return $this;
    }

    /**
     * Checking update options
     * @param array $options
     * @param array $update
     * @return array
     */
    private function checkUpdateOptions(array $options, array $update): array
    {
        //Add multi key
        if (!array_key_exists('multi', $options)) $options['multi'] = true;
        //Remove "multi" if upsert query
        if (array_key_exists('upsert', $options)) unset($options['multi']);
        $key = array_keys($update)[0];
        if (mb_stripos($key, '$') !== 0) unset($options['multi']);
        return $options;
    }

    /**
     * Run update query
     * @param array $query
     * @param array $update
     * @param array $options
     * @return MongoModel
     */
    public function update(array $query, array $update, array $options = []): MongoModel
    {
        //Counting update item
        $this->result = $this
            ->setBulk(
                'update',
                [
                    $query,
                    $update,
                    $this->checkUpdateOptions(
                        $options,
                        $update
                    )
                ]
            )
            ->bulkWrite($this->collection);
        return $this;
    }

    /**
     * Batch update items
     * @param array $batch
     * @return MongoModel
     */
    public function batchUpdate(array $batch): MongoModel
    {
        //set batch item in bulk job
        foreach ($batch as $update) {
            $this->setBulk(
                'update',
                [
                    $update[0],
                    $update[1],
                    $this->checkUpdateOptions(
                        !empty($update[2]) ? $update[2] : [],
                        $update[1]
                    )
                ]
            );
        }
        $this->result = $this->bulkWrite($this->collection);
        return $this;
    }

    /**
     * Run insert data
     * @param array $insert
     * @return MongoModel
     */
    public function insert(array $insert): MongoModel
    {
        $this->result = $this->setBulk('insert', [$insert])->bulkWrite($this->collection);
        return $this;
    }

    /**
     * Batch insert items
     * @param array $batch
     * @return MongoModel
     */
    public function batchInsert(array $batch): MongoModel
    {
        //set batch item in bulk job
        foreach ($batch as $insert) {
            $this->setBulk('insert',[$insert]);
        }
        $this->result = $this->bulkWrite($this->collection);
        return $this;
    }


    /**
     * Remove item from collection/drop collection|db
     * @param array $criteria
     * @return MongoModel
     */
    public function remove(array $criteria = []): MongoModel
    {
        //If dropping collection
        if (empty($criteria)) return $this->drop();
        $this->result = $this->setBulk('delete',[$criteria])->bulkWrite($this->collection);
        return $this;
    }

    /**
     * Drop collection or db
     * @return MongoModel
     */
    private function drop(): MongoModel
    {
        $result = $this->executeCommand($this->collection !== null ? ['drop' => $this->collection] : ['dropDatabase' => 1]);
        $this->result = !empty($result[0]->ok);
        return $this;
    }
}
