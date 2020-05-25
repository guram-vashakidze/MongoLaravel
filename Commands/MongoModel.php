<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MongoModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:mongomodel {name : Model name} {--s|smallname : Not added "Model" in name Model} {--c|collection= : Collection name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create MongoModel';

    /**
     * Name of creating model
     * @var string
     */
    private $modelName;

    /**
     * default collection for model
     * @var string
     */
    private $collectionName;

    /**
     * Path to models dir
     * @var string
     */
    private $modelsDir;

    /**
     * Namespace for models
     * @var string
     */
    private $namespaceModels;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //Set name for model
        $this->setModelName();
        //Set name for collections
        $this->setCollectionName();
        //Check model dir
        $this->checkDir();
        //Create model file
        return $this->createModelFile();
    }

    /**
     * Set name of creating model
     */
    private function setModelName()
    {
        //Name creating model
        $modelName = $this->argument('name');
        //If "Model" in name Model or not add "Model" in name
        if (preg_match("/Model$/", $modelName) || $this->option('smallname')) {
            $this->modelName = $modelName;
            return;
        }
        //Set model name
        $this->modelName = ucfirst($modelName) . 'Model';
    }

    /**
     * Set name for collections
     */
    private function setCollectionName()
    {
        //Set default collection name
        $this->collectionName = mb_strtolower(preg_replace("/Model$/", '', $this->modelName));
        //Get user data
        $name = $this->option('collection');
        //if name is null
        if ($name === null) {
            $this->collectionName = $this->ask('Enter collection name',$this->collectionName);
            return;
        }
        $this->collectionName = $name;
    }

    private function checkDir() {
        //Get models dir
        $this->modelsDir = trim(config('app.models_dir'),DIRECTORY_SEPARATOR);
        //If not set params
        if (empty($this->modelsDir)) {
            $this->error('You must specify the parameter "MODELS_DIR" in the .env file and "models_dir" in "config/app.php"');
            die();
        }
        //Set namespace
        $this->namespaceModels = str_replace(DIRECTORY_SEPARATOR,'\\',$this->modelsDir);
        if (is_dir(app_path($this->modelsDir))) return;
        //Set split path
        $modelsDir = explode(DIRECTORY_SEPARATOR,$this->modelsDir);
        //Create path to dir
        $path = '';
        foreach ($modelsDir as $dir) {
            $path .= $dir.DIRECTORY_SEPARATOR;
            //If path is exist
            if (is_dir(app_path($path))) continue;
            //Creating dir
            mkdir(app_path($path));
            //Set user info
            $this->info('Create dir: '.app_path($path));
        }
        $this->modelsDir = $path;
    }

    /**
     * Finish create model
     */
    private function createModelFile() {
        //php code
        $php = "<?php\n\n".
            "namespace App\\".$this->namespaceModels.";\n\n".
            "use \App\Module\Mongo\MongoModel;\n\n".
            "class {$this->modelName} extends MongoModel\n".
            "{".
            "\t\n".
            "\t/**\n".
            "\t * Collection name\n".
            "\t * @var string\n".
            "\t */\n".
            "\tprotected \$collection = '{$this->collectionName}';\n\n".
            "}";
        //path to file
        $file = app_path($this->modelsDir).$this->modelName.'.php';
        //set php code in file
        file_put_contents($file,$php);
        //set user info
        $this->info('Model is created: '.$file);
    }
}
