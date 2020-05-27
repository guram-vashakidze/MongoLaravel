<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Mongo
 * @method static \App\Module\Mongo\MongoModel db(null|string $db)
 * @method static \App\Module\Mongo\MongoModel collection(null|string $collection)
 * @method static array|int|bool get()
 * @method static \App\Module\Mongo\MongoModel count(array $query = null)
 * @method static \App\Module\Mongo\MongoModel getListDatabases(bool $onlyName = true)
 * @method static \App\Module\Mongo\MongoModel getListCollections(bool $onlyName = true)
 * @method static \App\Module\Mongo\MongoModel aggregate(array $pipeline)
 * @method static \App\Module\Mongo\MongoModel find(array $query = [], array $options = [])
 * @method static \App\Module\Mongo\MongoModel findOne(array $query = [], array $options = [])
 * @method static \App\Module\Mongo\MongoModel findAndModify(array $query = [],array $update = [],bool $new = false,bool $remove = false,array $fields = [],bool $upsert = false): MongoModel
 * @method static \App\Module\Mongo\MongoModel update(array $query, array $update, array $options = [])
 * @method static \App\Module\Mongo\MongoModel batchUpdate(array $batch)
 * @method static \App\Module\Mongo\MongoModel insert(array $insert)
 * @method static \App\Module\Mongo\MongoModel batchInsert(array $batch)
 * @method static \App\Module\Mongo\MongoModel remove(array $criteria = [])
 * @method static array|float|int|\MongoDB\BSON\ObjectId|string getLastInsertId()
 * @package App\Module\Mongo
 */
class Mongo extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mongo';
    }
}
