<?php

class MongoLaravel
{

    public static function setup()
    {
        return new self();
    }

    const PROVIDER = 'App\Module\Mongo\MongoProvider::class';

    const FACADE = '\'Mongo\' => App\Facades\Mongo::class';

    const COMMAND = 'Commands\MongoModel::class';

    const DEF_MODELS_DIR = 'Http'.DIRECTORY_SEPARATOR.'Models';

    /**
     * Dir app
     * @var null|string
     */
    private $appDir = null;

    /**
     * User argv params
     * -ecommand | --exclude=command
     * -efacade | --exclude=facade
     * @var array
     */
    private $userParams = [
        'facade' => true,//Install facade
        'command' => true//Install artisan command
    ];

    private function __construct()
    {
        $this->setAppDir();
        //Set install user params
        $this->setUserParams();
        //Installing default module
        $this->installModule();
        //Install facade
        $this->installMongoFacade();
        //Install artisan command
        $this->installMongoCommand();
        //Drop installing dir
        self::dropInstallingDir();
        echo 'SUCCESS: Install is ready'."\n";
    }

    /**
     * Set app dir
     */
    private function setAppDir()
    {
        $this->appDir = preg_replace('/\\'.DIRECTORY_SEPARATOR.'[^\\'.DIRECTORY_SEPARATOR.']+$/','',__DIR__).DIRECTORY_SEPARATOR;
    }

    /**
     * Set argv params
     */
    private function setUserParams()
    {
        //Get user option
        $option = getopt('e:', ['exclude::']);
        //if not options
        if (empty($option) || !is_array($option)) return;
        //if exist small options
        $this->checkUserOption($option);
        //if exist long options
        $this->checkUserOption($option, 'exclude');
    }

    /**
     * Check argv option
     * @param array $option - array of option
     * @param string $optionName - checking params
     */
    private function checkUserOption(array $option, string $optionName = 'e')
    {
        if (!array_key_exists($optionName, $option)) return;
        //if one option
        if (!is_array($option[$optionName])) $option[$optionName] = [$option[$optionName]];
        //Checking option
        foreach ($option[$optionName] as $exclude) {
            //if correct option
            if (array_key_exists($exclude, $this->userParams)) //off option
                $this->userParams[$exclude] = false; else
                die('ERROR: Incorrect option "' . $exclude . '"' . "\n");
        }
    }

    /**
     * Installing default module
     */
    private function installModule()
    {
        $ds = DIRECTORY_SEPARATOR;
        //If not fount app folder
        if (!is_dir($this->appDir.'app'))
            die('ERROR: I can not find the "app" folder' . "\n");
        //If not found dir 'app/Module'
        if (!is_dir($this->appDir .'app'. $ds . 'Module')) {
            mkdir($this->appDir .'app'. $ds . 'Module');
            echo 'INFO: Creating dir "app' . $ds . 'Module"' . "\n";
        }
        //If not found dir 'app/Module/Mongo'
        if (!is_dir($this->appDir .'app'. $ds . 'Module' . $ds . 'Mongo')) {
            mkdir($this->appDir .'app'. $ds . 'Module' . $ds . 'Mongo');
            echo 'INFO: Creating dir "app' . $ds . 'Module' . $ds . 'Mongo"' . "\n";
        }
        system('cp -r '.__DIR__.$ds.'Module'.$ds.'Mongo'.$ds.'* '.$this->appDir .'app'. $ds . 'Module' . $ds . 'Mongo');
        echo 'INFO: Module is install'."\n";
        //Set setting in .env file
        $this->setEnvSetting();
        //Set setting in config/database file
        $this->setConfigDbSetting();
        //Register providers
        $this->registerMongoProvider();
    }

    /**
     * Set setting in .env file
     */
    private function setEnvSetting()
    {
        //if not found env file
        if (!file_exists($this->appDir.'.env'))
            die('ERROR: File .env is not found'."\n");
        //Get env setting
        $env = file_get_contents($this->appDir.'.env');
        //if setting is added
        if (
        preg_match(
            "/MONGO\_HOST=[^\n]*\nMONGO\_PORT=[^\n]*\nMONGO\_USER=[^\n]*\nMONGO\_PASSWORD=[^\n]*\nMONGO\_DEBUG=[^\n]*\nMONGO\_DB=[^\n]MONGO\_INC\_COLLECTION=/",
            $env
        )
        ) {
            echo 'INFO: Env setting already added'."\n";
            return;
        }
        //Added setting
        $env .= "\nMONGO_HOST=127.0.0.1\nMONGO_PORT=27017\nMONGO_USER=\nMONGO_PASSWORD=\nMONGO_DEBUG=true\nMONGO_DB=admin\nMONGO_INC_COLLECTION=test.inc\n";
        //Added setting
        file_put_contents($this->appDir.'.env',$env);
        echo 'INFO: Env setting is added'."\n";
    }

    /**
     * Set setting in config/database file
     */
    private function setConfigDbSetting()
    {
        //File config
        $file = $this->appDir.'config'.DIRECTORY_SEPARATOR.'database.php';
        //if not found file
        if (!file_exists($file))
            die('ERROR: File config/database.php not found'."\n");
        //get file
        $config = file_get_contents($file);
        //if mongo setting already added
        if (preg_match("/'mongo' *=> *\[/",$config)) {
            echo 'WARNING: field \'mongo\' already exist in file config/database.php. Check its structure.'."\n";
            return;
        }
        $setting = "\t'mongo' => [\n"
            ."\t\t'host' => env('MONGO_HOST'),\n"
            ."\t\t'port' => env('MONGO_PORT'),\n"
            ."\t\t'user' => env('MONGO_USER'),\n"
            ."\t\t'password' => env('MONGO_PASSWORD'),\n"
            ."\t\t'debug' => env('MONGO_DEBUG',false),\n"
            ."\t\t'db' => env('MONGO_DB','admin'),\n"
            ."\t\t'inc' => env('MONGO_INC_COLLECTION','test.inc')\n"
            ."\t],\n\n"
            ."];\n";
        //else added config
        $config = preg_replace("/\];/",$setting,$config);
        //Added new setting in file
        file_put_contents($file,$config);
        echo 'INFO: Added setting in file config/database.php'."\n";
    }

    /**
     * Register mongo provider
     */
    private function registerMongoProvider()
    {
        //File register provider
        $providerFile = $this->appDir.'config'.DIRECTORY_SEPARATOR.'app.php';
        //If not found file provider
        if (!file_exists($providerFile))
            die('ERROR: File provider (config.'.DIRECTORY_SEPARATOR.'app.php) not found'."\n");
        //get content file
        $php = file_get_contents($providerFile);
        //Search list providers
        preg_match("/'providers' *=>[^\[]*\[([^\]]+)\]/",$php,$search);
        //If not found
        if (empty($search[1])) {
            echo 'WARNING: Failed to register Mongo service provider. Add '.self::PROVIDER.' in config/app to the "providers" section'."\n";
            return;
        }
        $search = $search[1];
        //if provider set
        if (preg_match("/(,|\[)?[\n\r\t ]+".preg_quote(self::PROVIDER)."/",$search)) {
            echo 'INFO: Service provider already registered'."\n";
            return;
        }
        //Correct added class
        $search = self::addClassInRegisterArray($search,self::PROVIDER);
        //Add new value to file
        $php = preg_replace("/'providers' *=>[^\[]*\[([^\]]+)\]/","'providers' => [".$search."\t]",$php);
        //Set new data
        file_put_contents($providerFile,$php);
        echo 'INFO: Service provider successfully registered'."\n";
    }

    /**
     * Install mongo facade
     */
    private function installMongoFacade()
    {
        $ds = DIRECTORY_SEPARATOR;
        // If not install facade
        if (!$this->userParams['facade']) {
            $providerFile = $this->appDir.'app'.$ds.'Module'.$ds.'Mongo'.$ds.'MongoProvider.php';
            //Get provider code
            $provider = file_get_contents($providerFile);
            //Drop registered facade
            $provider = str_replace('$this->app->bind(\'mongo\', \'\App\Module\Mongo\MongoModel\');','',$provider);
            //save new provider
            file_put_contents($providerFile,$provider);
            echo 'INFO: Unregistered Mongo facade'."\n";
            return;
        }
        $facadesDir = $this->appDir.'app'.$ds.'Facades'.$ds;
        //if not dir Facades
        if (!is_dir($facadesDir)) {
            //Create facades dir
            mkdir($facadesDir);
            echo 'INFO: Create Facades dir (app/Facades/)'."\n";
        }
        //Copy facades
        copy(__DIR__.$ds.'Facades'.$ds.'Mongo.php',$facadesDir.'Mongo.php');
        echo 'INFO: Mongo Facade is install'."\n";
        //File register provider
        $appFile = $this->appDir.'config'.$ds.'app.php';
        //get content file
        $php = file_get_contents($appFile);
        //Search list providers
        preg_match("/'aliases' *=>[^\[]*\[([^\]]+)\]/",$php,$search);
        //If not found
        if (empty($search[1])) {
            echo 'WARNING: Failed to register Mongo facade. Add '.self::FACADE.' in config/app to the "aliases" section'."\n";
            return;
        }
        $search = $search[1];
        //if facade set
        if (preg_match("/(,|\[)?[\n\r\t ]+".preg_quote(self::FACADE)."/",$search)) {
            echo 'INFO: Facade alias already registered'."\n";
            return;
        }
        //Correct added class
        $search = self::addClassInRegisterArray($search,self::FACADE);
        //Add new value to file
        $php = preg_replace("/'aliases' *=>[^\[]*\[([^\]]+)\]/","'aliases' => [".$search."\t]",$php);
        //Set new data
        file_put_contents($appFile,$php);
        echo 'INFO: Facade alias successfully registered'."\n";
    }

    /**
     * Install artisan command
     */
    private function installMongoCommand()
    {
        //if not install command
        if (!$this->userParams['command']) return;
        $ds = DIRECTORY_SEPARATOR;
        //Set default dir for Models
        $this->setModelsDir();
        $consoleDir = $this->appDir.'app'.$ds.'Console'.$ds;
        //if not dir console
        if (!is_dir($consoleDir))
            die("ERROR: Dir app/Console not found. Artisan command is not install\n");
        //If not found Kernel file
        if (!file_exists($consoleDir.'Kernel.php'))
            die("ERROR: File app/Console/Kernel.php not found. Artisan command is not install\n");
        //If not found dir Commands
        if (!is_dir($consoleDir.'Commands'.$ds)) {
            mkdir($consoleDir.'Commands');
            echo 'INFO: Dir app/Console/Commands is created'."\n";
        }
        //copy file
        copy(__DIR__.$ds.'Commands'.$ds.'MongoModel.php',$consoleDir.'Commands'.$ds.'MongoModel.php');
        echo 'INFO: Artisan command file is install'."\n";
        //register artisan command
        $commands = $consoleDir.'Kernel.php';
        //get content file
        $php = file_get_contents($commands);
        //Search list providers
        preg_match('/protected \$commands *=[^\[]*\[([^\]]+)\]/',$php,$search);
        //If not found
        if (empty($search[1])) {
            echo 'WARNING: Failed to register Artisan command. Add '.self::COMMAND.' in app/Console/Kernel.php to the "$commands" section'."\n";
            return;
        }
        $search = $search[1];
        //if provider set
        if (preg_match("/(,|\[)?[\n\r\t ]+".preg_quote(self::COMMAND)."/",$search)) {
            echo 'INFO: Artisan command already registered'."\n";
            return;
        }
        //Correct added class
        $search = self::addClassInRegisterArray($search,self::COMMAND);
        //Add new value to file
        $php = preg_replace('/protected \$commands *=[^\[]*\[([^\]]+)\]/','protected $commands = ['.$search."\t]",$php);
        //Set new data
        file_put_contents($commands,$php);
        echo 'INFO: Artisan command successfully registered'."\n";
    }

    /**
     * Set default dir for Models
     */
    private function setModelsDir()
    {
        //Get env setting
        $env = file_get_contents($this->appDir.'.env');
        //if setting is added
        preg_match("/MODELS\_DIR=([^\n]*)\n/", $env,$search);
        //if not set models dir
        if (empty($search[1])) {
            //Get user data
            echo "Set Models dir in your app [".self::DEF_MODELS_DIR."]:\n";
            //open stdin
            $stdin = fopen('php://stdin','r');
            while ($line = fgets($stdin)) {
                $line = trim($line,"\r\n\t ");
                //if set default
                $search[1] = empty($line) ? self::DEF_MODELS_DIR : $line;
                break;
            }
        }
        if (!empty($search[0])) {
            $env = preg_replace("/MODELS\_DIR=([^\n]*)\n/",'MODELS_DIR='.$search[1]."\n",$env);
        } else {
            $env .= "\n".'MODELS_DIR='.$search[1]."\n";
        }
        file_put_contents($this->appDir.'.env',$env);
        echo 'INFO: Models dir is added in .env file'."\n";
        //File config
        $appFile = $this->appDir.'config'.DIRECTORY_SEPARATOR.'app.php';
        //get file
        $config = file_get_contents($appFile);
        //if models_dir already added
        if (preg_match("/'models_dir' *=> *env\(/",$config)) {
            echo 'WARNING: field \'models_dir\' already exist in file config/app.php. Check its structure.'."\n";
            return;
        }
        //else added config
        $config = preg_replace("/\];/","\t'models_dir' => env('MODELS_DIR','".$search[1]."'),\n];",$config);
        //Added new setting in file
        file_put_contents($appFile,$config);
        echo 'INFO: Added field \'models_dir\' in file config/app.php'."\n";
    }

    /**
     * Correct added class
     * @param string $array
     * @param string $class
     * @return string
     */
    private static function addClassInRegisterArray(string $array,string $class): string
    {
        //Exploding array
        $array = explode("\n",$array);
        //Checking items
        foreach ($array as $item) {
            //Check items
            $item = trim($item,",\r\t\n ");
            if (empty($item)) continue;
            //If comment
            if (!preg_match("/^['a-z0-9\\\\]/i",$item)) {
                $result[] = $item;
                continue;
            }
            $result[] = $item.',';
        }
        $result[] = $class.',';
        return "\n\t\t".implode("\n\t\t",$result)."\n";
    }

    /**
     * Drop dir
     */
    private static function dropInstallingDir() {
        system('rm -rf '.__DIR__);
        echo "INFO: Installing dir is remove\n";
    }
}

MongoLaravel::setup();
