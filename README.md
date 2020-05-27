## MongoLaravel
### Содержание
- [Требования](#requirements)  
- [Установка](#install)  
    - [Автоматическая установка](#autoinstall)
    - [Ручная установка](#manualinstall)
- [Работа с модулем](#module)
    - [Artisan-команда](#artisan_make_mongomodel)
    - [Доступные методы](#methods)
        - [db()](#db)
        - [collection()](#collection)
        - [getListDatabases()](#getListDatabases)
        - [getListCollections()](#getListCollections)
        - [find()](#find)
        - [findOne()](#findOne)
        - [findAndModify()](#findAndModify)
        - [aggregate()](#aggregate)
        - [update()](#update)
        - [batchUpdate()](#batchUpdate)
        - [insert()](#insert)
        - [batchInsert()](#batchInsert)
        - [remove()](#remove)
        - [count()](#count)
        - [get()](#get)
        - [getLastInsertId()](#getLastInsertId)
    - [Ключи. Метод ```id()```](#keys)
    - [Фасад Mongo](#facade) 

### <a name="requirements"></a> Требования
> PHP - 7.x  
> MongoDB - 4.2  
> Laravel - 7.x  
> PHP [MongoDriver](https://docs.mongodb.com/drivers/php)
### <a name="install"></a> Установка
##### <a name="autoinstall"></a> Автоматическая установка
```bash
git clone https://github.com/guram-vashakidze/MongoLaravel.git && cd MongoLaravel && php setup.php
```
Будут установлены:
- Модуль подключения к БД
- Модель для работы с БД
- Фасад Mongo  
    Для отмены установки фасада использовать ключ -e (--exclude)
    ```bash
    php setup.php --exclude=facade
    ```
    ```bash
    php setup.php -efacade
    ```
- Artisan-команда - автоматическое создание моделей:
    Для отмены установки artisan-команды использовать ключ -e (--exclude)
    ```bash
    php setup.php --exclude=command
    ```
    ```bash
    php setup.php -ecommand
    ```
    В процессе установки необходимо указать путь к папке с моделями. По-умолчанию Http/Models. 
##### <a name="manualinstall"></a> Ручная установка  
1. Перейти в папку проекта
2. Клонируем репозиторий
    ```bash
    git clone https://github.com/guram-vashakidze/MongoLaravel.git
    ```
3.  Папку /Module/Mongo перенести в /app
4.  В .env файле задать параметры подключения к MongoDB
    ```bash
    MONGO_HOST=127.0.0.1
    MONGO_PORT=27017
    MONGO_USER=
    MONGO_PASSWORD=
    MONGO_DEBUG=true
    MONGO_DB=admin
    MONGO_INC_COLLECTION=test.inc
    ```
    ```MONGO_HOST``` - хост подключения к Mongo  
    ```MONGO_PORT``` - порт подключения к Mongo  
    ```MONGO_USER``` - имя пользователя Mongo  
    ```MONGO_PASSWORD``` - пароль пользователя Mongo  
    ```MONGO_DEBUG``` - вывод исключений. ```true``` - работает только в случае включенного режима отладки проекта Laravel  
    ```MONGO_DB``` - используемая база данных по-умолчнию. В поцессе разработки можно менять  
    <a name="MONGO_INC_COLLECTION"></a>```MONGO_INC_COLLECTION``` - База данных/коллекция используемая для хранения автоинкремента ```_id```
5. В config/database.php указать ссылки на параметры подключения к MongoDB
<a name="inc"></a>
    ```php
    'mongo' => [
        'host' => env('MONGO_HOST'),
        'port' => env('MONGO_PORT'),
        'user' => env('MONGO_USER'),
        'password' => env('MONGO_PASSWORD'),
        'debug' => env('MONGO_DEBUG',false),
        'db' => env('MONGO_DB','admin'),
        'inc' => env('MONGO_INC_COLLECTION','test.inc')
    ],
    ```
6. Зарегистрировать провайдер MongoProvider в config/app.php
    ```php
    'providers' => [
        /**
        * Other providers
        * .............
        */
        App\Module\Mongo\MongoProvider::class,
    ],
    ```
7. Установка фасада Mongo.
    - Файл Facades/Mongo.php перенести в app/Facades/
    - Зарегистрировать алиас для фасада Mongo в config/app.php:
    ```php
    'aliases' => [
        /**
        * Other aliases
        * .............
        */
        'Mongo' => App\Facades\Mongo::class,
    ],
    ```
    - Если установка фасада не производится то в файле провайдера app/Module/Mongo/MongoProvider.php удалить 
    сервис-контейнер для фасада (стр. 34):
    ```php
    public function boot()
    {
        //Drop this line
        $this->app->bind('mongo', '\App\Module\Mongo\MongoModel');
    }
    ```
8. Установка Artisan-команды
    - Файл Commands/MongoModel.php перенести в app/Console/Commands/
    - Зарегистрировать команду в app/Console/Kernel.php:
    ```php
    protected $commands = [
       //Other commands...
       Commands\MongoModel::class,
    ];
    ```  
    - Указать путь по умолчанию для файлов моделей в .env файле
    ```bash
    MODELS_DIR=Http/Models
    ```
    - В config/app.php указать ссылку на параметр пути к папке файлов моделей:
    ```php
    'models_dir' => env('MODELS_DIR','Http/Models'),
    ```
### <a name="module"></a> Работа с модулем
###### <a name="artisan_make_mongomodel"></a> Создание модели Mongo с помощью Artisan-команды
Создание модели.
```bash
php artisan make:mongomodel User
```
Будет автоматически создана модель UserModel, в папке env('MODELS_DIR').  
В процессе создания необходимо будет указать коллекцию Mongo. По-умолчанию название коллекции соответствуют
названию модели без приставки 'Model'.  
Ключи:
```bash
-sn|--smallname - Не добавлять приставку 'Model' к названию модели  
-с|--collection - Указать название коллекции по-умолчанию
``` 
Созданная модель:
```php
namespace App\Http\Models;

use \App\Module\Mongo\MongoModel;

class UserModel extends MongoModel
{	
	/**
	 * Collection name
	 * @var string
	 */
	protected $collection = 'user';

}
```
###### <a name="methods"></a> Доступные методы
1) <a name="db"></a>```db(string $db = null): MongoModel``` - указание названия базы данных. По-умолчанию название берется из .env файла. 
При вызове метода без аргументов
устанавливается название базы данных по-умолчанию.
    ```php
    $this->db('my-db');
    ```  
2) <a name="collection"></a>```collection(string $collection = null): MongoModel``` - указание коллекции в базе данных. 
Если задано свойство ```protected $collection``` в моделе данных, то применяется это значение.
При вызове метода без аргументов название коллекции устанавливается в ```NULL```.
    ```php
    public function __construct(MongoSetting $connection)
    {
        parent::__construct($connection);
    
        $this->db('my-db');
        $this->collection('user');
        /** or */
        $this->db('my-db')->collection('user');
    }
    ```
3) <a name="getListDatabases"></a>```getListDatabases(bool $onlyName = true): MongoModel``` - получение списка баз данных. ```$onlyName``` - получение
списка содержащего только названия баз данных.
    ```php
    public function __construct(MongoSetting $connection)
    {
        parent::__construct($connection);
        $this->getListDatabases();
    }
    ``` 
4) <a name="getListCollections"></a>```getListCollections($onlyName = true): MongoModel``` - получение списка коллекций из базы данных. ```$onlyName``` - 
получение списка содержащего только названия коллекций.
    ```php
    public function __construct(MongoSetting $connection)
    {
        parent::__construct($connection);
        $this->db('my-db')->getListCollections();
    }
    ```
5) <a name="find"></a>```find(array $query = [],array $options = []): MongoModel``` - выполнение запроса ```find``` к Mongo.  
    ```$query``` - [условия выборки](https://docs.mongodb.com/manual/reference/operator/query/)  
    ```$options``` - дополнительные параметры:
    - ```fields``` - массив полей выводимых в результат. Поле ```_id``` по-умолчанию отключено.
        ```php
        ...
        //_id default false
        'fields' => ['field1','field2','field3',...,'fieldN']
        
        //add field '_id' in result
        'fields' => ['_id',field1','field2','field3',...,'fieldN']
        ...
        ```
    - ```sort``` - поле и тип способ сортировки
        ```php
        ...
        'sort' => ['fieldN' => 1]
        ...
        ```
    - ```limit``` - лимит выборки
       ```php
        ...
        'limit' => 10
        ...
        ```
    - ```skip``` - количество пропускаемых значений из результата.
       ```php
       ...
       'skip' => 10
       ...
       ```
    ```php
    public function __construct(MongoSetting $connection)
    {
        parent::__construct($connection);
        $this->db('my_db')->collection('my_collection')->find(['field' => ['$ne' => null]],['fields' => ['field']]);
    }
    ``` 
6) <a name="findOne"></a>```findOne(array $query = [],array $options = []): MongoModel``` - получение одной записи из коллекции. Описание входных
параметров аналогично запросу [```find```](#find)
7) <a name="findAndModify"></a>```findAndModify(array $query = [],array $update = [],bool $new = false,bool $remove = false,array $fields = [],bool $upsert = false): MongoModel``` -
выполнение запроса [findAndModify](https://docs.mongodb.com/manual/reference/method/db.collection.findAndModify/). Входные
параметры:  
    ```$query``` - [условия выборки/обновления](https://docs.mongodb.com/manual/reference/operator/query/)  
    ```$update``` - [параметры обновления](https://docs.mongodb.com/manual/reference/method/db.collection.update/#update-parameter)  
    ```$new``` - вернуть обновленный результат  
    ```$remove``` - удалить найденный результат. Работает только если не указан массив ```$update```  
    ```$fields``` - массив полей выводимых в результат. Поле ```_id``` по-умолчанию отключено.  
    ```$upsert``` - вставка записи если такова отсутствует в коллекции
    ```php
    public function __construct(MongoSetting $connection)
    {
        parent::__construct($connection);
        $this->db('my_db')
        $this->collection('my_collection')
        $this->findAndModify(['_id' => 1],['$set' => ['my_field' => true]],true);
    }
    ``` 
8) <a name="aggregate"></a>```aggregate(array $pipeline): MongoModel``` - выполнение запроса [aggregate](https://docs.mongodb.com/manual/reference/method/db.collection.aggregate/)  
   ```$pipeline``` - [последовательность](https://docs.mongodb.com/manual/reference/operator/aggregation-pipeline/) операций или этапов агрегирования данных.
   ```php
   public function __construct(MongoSetting $connection)
   {
       parent::__construct($connection);
       $this->db('my_db')
       $this->collection('my_collection')
       $this->aggregate([
            [
                '$match' => [
                  'field' => [
                      '$ne' => null
                  ]
                ]
            ],
            [
                '$project' => [
                  '_id' => false,
                  'fieldN' => true
                ]
            ]
        ]);
   }
   ```
9) <a name="update"></a>```update(array $query, array $update, array $options = []): MongoModel``` - выполнение запроса [update](https://docs.mongodb.com/manual/reference/method/db.collection.update/).  
    ```$query``` - [условия обновления](https://docs.mongodb.com/manual/reference/operator/query/)  
    ```$update``` - [параметры обновления](https://docs.mongodb.com/manual/reference/method/db.collection.update/#update-parameter)  
    ```$options``` - [дополнительные опции](https://docs.mongodb.com/manual/reference/method/db.collection.update/#update-upsert). По-умолчанию опция 
    [```multi```](https://docs.mongodb.com/manual/reference/method/db.collection.update/#update-multi) включена если не включена
    опция [```upsert```](https://docs.mongodb.com/manual/reference/method/db.collection.update/#update-upsert).
   ```php
   public function __construct(MongoSetting $connection)
   {
       parent::__construct($connection);
       $this->db('my_db')
       $this->collection('my_collection')
       $this->update(['field' => 1],['$set' => ['fieldN' => 1]],['$upsert' => true]);
   }
   ```
10) <a name="batchUpdate"></a>```batchUpdate(array $batch): MongoModel``` - пакетное обновление данных.  
    ```$batch``` - массив с данными для обновления:  
    ```php
    [
        [
            $query,
            $update,
            $options
        ],
        .....
        [
            $query,
            $update,
            $options
        ],
    ]
    ```
    Параметры массива аналогичны запросу [```update```](#update)
       ```php
       public function __construct(MongoSetting $connection)
       {
           parent::__construct($connection);
           $this->db('my_db')
           $this->collection('my_collection')
           $this->batchUpdate([
                [['field' => 1],['$set' => ['fieldN' => 1]],['$upsert' => true]],
                [['field' => 4],['$set' => ['fieldN' => 10]]]
           ]);
       }
       ```
11) <a name="insert"></a>```insert(array $insert): MongoModel``` - [вставка](https://docs.mongodb.com/manual/reference/method/db.collection.insert/) данных в коллекцию.
При вставке поле ```_id``` является не обязательным. В случае его отсутствия будет автоматически сгенерировано его значение.
    ```php
    public function __construct(MongoSetting $connection)
    {
       parent::__construct($connection);
       $this->db('my_db')
       $this->collection('my_collection')
       $this->insert(['field' => 1,'field2' => 1]);
    }
    ```
12) <a name="batchInsert"></a>```batchInsert(array $batch): MongoModel``` - пакетная вставка данных в коллекцию. 
```$batch``` - содержит массив документов идентичных входному параметру метода [```insert```](#insert)
    ```php
    public function __construct(MongoSetting $connection)
    {
       parent::__construct($connection);
       $this->db('my_db')
       $this->collection('my_collection')
       $this->batchInsert([
            ['field' => 1,'field2' => 1],
            ['field' => 2,'field2' => 2],
            ['field' => 3,'field2' => 3]
       ]);
    }
    ```
13) <a name="remove"></a>```remove(array $criteria = []): MongoModel``` - удаление записей из коллекции/удаление коллекции/удаление базы данных.  
    ```$criteria``` - [условия удаления данных](https://docs.mongodb.com/manual/reference/operator/query/). Если данный входной
    параметр не передан, то:
    - если указано название коллекции, то будет удалена коллекция
    - если не указано название коллекции, то будет удалена база данных
       ```php
       public function __construct(MongoSetting $connection)
       {
           parent::__construct($connection);
           //remove item 
           $this->db('my_db')->collection('my_collection')->remove(['field' => 1]);
           //drop collection
           $this->db('my_db')->collection('my_collection')->remove();
           //drop db
           $this->db('my_db')->collection()->remove();
           
       }
       ```
14) <a name="count"></a>```count($query = null): MongoModel``` - Подсчет количества результатов/установка флага для подсчета количества результатов.  
    ```$query``` - соответствует условиям выборки метода [```find```](#find) или последовательности операций агрегирования данных
    метода [```aggregate```](#aggregate). При использовании последних из операций агрегирования будут автоматически удалены
    операции ```$limit``` и ```$skip```. Если данный параметр передан (отличен от ```NULL```), то будет применен "независимый"
    вызов метода, т.е. результат подсчета количества записей будет рассчитан сразу после его вызова:
    ```php
    public function __construct(MongoSetting $connection)
    {
       parent::__construct($connection);
       $this->db('my_db')->collection('my_collection')->count(['field' => 1]);
    }
    ```
    "Пустой" вызов метода используется, как установка флага подсчета количества результатов после дальнейшего выполнения
    одного из следующих запросов: [```getListDatabases```](#getListDatabases), [```getListCollections```](#getListCollections), 
    [```find```](#find), [```findOne```](#findOne), [```aggregate```](#aggregate). Т.о. к результату будет добавлен подсчет
    количества записей. При этом, параметры ```limit```/```skip``` (для запросов группы ```find*```) и параметры
    ```$limit```/```$skip``` (для запроса ```aggregate```) учитываться не будут.
    ```php
    public function __construct(MongoSetting $connection)
    {
       parent::__construct($connection);
       //Get list databases and count databeases
       $this->count()->getListDatabase()
       //Get 10 items where 'field'=1 and count items where field=1  
       $this->db('my_db')->collection('my_collection')->count()->find(['field' => 1],['limit' => 10])
    }
    ```
15) <a name="get"></a>```get(): array|int|bool``` - Получение результата выполненного запроса. Вывод данного метода зависит
от вызываемых методов.
    - [```insert```](#insert), [```batchInsert```](#batchInsert), [```update```](#update), [```batchUpdate```](#batchUpdate),
     [```remove```](#remove) - вернут количество (``int``) вставленных / обновленных / удаленных записей.
    Исключение [```remove```](#remove) без входных параметров, в этом случае вернется ```true``` если коллекция/база данных удалена и 
    ```false``` - если нет.
        ```php
        public function __construct(MongoSetting $connection)
        {
            parent::__construct($connection);
            /**
             * Result: 1
             */
            $this->insert(['field' => 1])->get();
            /**
             * Result: 2
             */
            $this
                ->batchInsert(
                    [
                        ['field' => 1],
                        ['field' => 1]
                    ]
                )
                ->get();
            /**
             * Result: 3 (but option 'multi' true)
             */
            $this->update(['field' => 1],['$set' => ['field' => 2]])->get();
            /**
             * Result: 1 (but option 'multi' false)
             */
            $this
                ->batchUpdate([
                    [
                        ['field' => 2],['$set' => ['field' => 1]],['multi' => false]
                    ]
                ])
                ->get();
            /**
             * Result: 1
             */
            $this->remove(['field' => 1])->get();
            /**
             * Result: true (drop collection)
             */
            $this->remove()->get();
            /**
             * Result: true (drop database)
             */
            $this->collection()->remove()->get();
        }
        ```
    - [```count```](#count) - только в случае вызова метода с параметрами вернет количество записей (```int```) удовлетворяющих условию.
        ```php
        public function __construct(MongoSetting $connection)
        {
            parent::__construct($connection);
            /**
             * Result: 1
             */
            $this->insert(['field' => 1])->get();
            /**
             * Result: 1
             */
            $this->count(['field' => 1])->get();
            /**
             * Result: NULL (but not $query)
             */
            $this->count()->get();
        }
        ```
    - [```getListDatabases```](#getListDatabases), [```getListCollections```](#getListCollections), [```find```](#find),
     [```findOne```](#findOne), [```aggregate```](#aggregate) - возвращает массив (```array```) с результатами запроса. В случае использования
     метода [```count```](#count). Возвращает массив с двумя ключами:
        - ```result``` - массив (```array```) с результатами запроса. Соответствует вызову указанных методов без метода [```count```](#count)
        - ```count``` - количество (```int```) записей. Соответствует вызов метода [```count```](#count) с входными параметрами. Исключение:
        [```getListDatabases```](#getListDatabases), [```getListCollections```](#getListCollections) - вернет количество баз данных/коллекций
        ```php
        public function __construct(MongoSetting $connection)
        {
            parent::__construct($connection);
            $this->batchInsert([
                ['field' => 1],
                ['field' => 2],
                ['field' => 3],
                ['field' => 4],
                ['field' => 5],
                ['field' => 6]
            ]);
            /**
             * Result: array (An array containing previously inserted records)
             */
            $this->find()->get();
            /**
             * Result: array
             * [
             *      'result' => array (An array containing two previously inserted records),
             *      'count' => 6 (int) total result
             * ]
             */
            $this->count()->find([],['limit' => 2])->get();
        }
        ```
16) <a name="getLastInsertId"></a> ```getLastInsertId(): mixed``` - возвращает идентификатор последней вставленной записи.
    ```php
    public function __construct(MongoSetting $connection)
    {
        parent::__construct($connection);
        /**
         * Inserting data
         */
        $this->batchInsert([
            ['_id' => 1, 'field' => 1],
            ['_id' => 2, 'field' => 2],
            ['_id' => 3, 'field' => 3],
            ['_id' => 4, 'field' => 4],
            ['_id' => 5, 'field' => 5],
            ['_id' => 6, 'field' => 6]
        ]);
        /**
         * Update field
         */
        $this->update(['_id' => 4],['$set' => ['field' => 1]]);
        /**
         * Get last insert ID
         * Result: 6
         */
        $this->getLastInsertId();
    }
    ```
###### <a name="keys"></a> Ключи
При вставке записей в коллекции MongoDB используя методы [```insert```](#insert) и [```batchInsert```](#batchInsert), если 
не указан ключ ```_id``` то он автоматически генерируется в формате ```\MongoDB\BSON\ObjectId```. В данном модуле доступна
генерация ключа в формате ```(string)\MongoDB\BSON\ObjectId``` и ```int``` с автоинкрементом значения.
- Для вставки записей с ключом ```(string)\MongoDB\BSON\ObjectId``` необходимо использовать значение ```#string```:
    ```php
    $this->batchInsert([
        ['_id' => '#string', 'field' => 1],
        ['_id' => '#string', 'field' => 2],
        ['_id' => '#string', 'field' => 3],
        ['_id' => '#string', 'field' => 4],
        ['_id' => '#string', 'field' => 5],
        ['_id' => '#string', 'field' => 6]
    ]);
    ```
- Для вставки записей с ключом автоинкремент-```int``` необходимо использовать значение ```#inc```:
    ```php
    $this->batchInsert([
        ['_id' => '#inc', 'field' => 1],
        ['_id' => '#inc', 'field' => 2],
        ['_id' => '#inc', 'field' => 3],
        ['_id' => '#inc', 'field' => 4],
        ['_id' => '#inc', 'field' => 5],
        ['_id' => '#inc', 'field' => 6]
    ]);
    ```
    Все записи будут иметь последовательный ключ. Для использования данной опции должен быть задан параметр [```MONGO_INC_COLLECTION```](#MONGO_INC_COLLECTION)
    в .env файле, а также [```database.mongo.inc```](#inc) в config/database.php  

Для работы с ключами доступен метод ```id($id = null)```. Данный метод возвращает:
- если ```$id === null``` - ```\MongoDB\BSON\ObjectId```
- если ```$id === '#inc'``` - ```int``` значение ключа с инкрементом
- если ```$id === '#string'``` - ```(string)\MongoDB\BSON\ObjectId```
- если ```is_string($id)``` - ```\MongoDB\BSON\ObjectId($id)```
- в остальных случаях - ```(string)$id```

Метод можно использовать для генерации ```_id``` до момента вставки записи. **Внимание:** если используется входной параметр
```#inc``` для определения ключа записи до ее сохранения в коллекции, и в дальнейшем эта запись не будет вставлена в коллекцию
(например: не пройдет валидацию), то при следующей попытке будет сгенерировано уже следующее значение:
```php
public function __construct(MongoSetting $connection)
{
    parent::__construct($connection);
    /**
     * Last incId = 0
     * First try
     */
    $insert = [
        //result: 1
        '_id' => $this->id('#inc'),
        'email' => 'my-email'
    ];
    /**
     * Validate email
     */
    if (!isEmail($insert['email'])) 
        return false;
    return true;
}
```
```php
public function __construct(MongoSetting $connection)
{
    parent::__construct($connection);
    /**
     * Last incId = 1
     * Second try
     */
    $insert = [
        //result: 2
        '_id' => $this->id('#inc'),
        'email' => 'my_email@post.com'
    ];
    /**
     * Validate email
     */
    if (!isEmail($insert['email'])) 
        return false;
    return true;
}
```
Метод позволяет преобразовывать ```\MongoDB\BSON\ObjectId $_id``` в ```(string)\MongoDB\BSON\ObjectId $_id``` и обратно.

###### <a name="facade"></a> Фасад Mongo
Фасад ```Mongo``` предоставляет статический интерфейс к классу модели ```MongoModel```.
```php
public function __construct()
{
    /**
     * Set db
     */
    Mongo::db('my_db');
    /**
     * Set collection
     */
    Mongo::collection('my_collection');
    /**
     * Find data
     */
    Mongo::find();
    /**
     * get result
     */
    $result = Mongo::get();
    /**
     * Or
     */
    Mongo::db('my_db')//Set db
        ->collection('my_collection')//Set collection
        ->find()//Find data
        ->get();//get result
}
```
