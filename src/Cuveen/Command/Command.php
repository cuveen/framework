<?php
namespace Cuveen\Command;

use Cuveen\App;
use Cuveen\Config\Config;
use Cuveen\Database\Database;
use Cuveen\Helper\Str;

class Command {
    protected $app;
    protected $base_path;
    protected $list_commands = [
        'make:controller',
        'make:middleware',
        'make:schedule',
        'make:model',
        'make:migration',
        'make:mail',
        'migrate:install',
        'migrate:fresh',
        'migrate:refresh',
        'migrate:status',
        'migrate:rollback',
        'migrate:list',
        'migrate:reset',
        'migrate:force',
        'cache:clear',
        'view:clear',
        'view:create',
        'route:list',
        'key:generate',
        'schedule:run'
    ];
    public function __construct($app)
    {
        $this->app = $app;
        $this->base_path = $this->app->base_path;
    }

    protected function getHelper()
    {
        echo "\e[1;37m\e[45m+--------------------------------------------------------------------+\e[0m\n";
        echo "\e[1;37m\e[45m|                      CUVEEN ARTISAN COMMAND                        |\e[0m\n";
        echo "\e[1;37m\e[45m+--------------------------------------------------------------------+\e[0m\n";
        echo "Cuveen Framework 1.0\n";
        echo "\033[1;33mUsage:\033[0m\n";
        echo "   command [arguments]\n";
        echo "\033[1;33mAvailable commands:\033[0m\n";
        echo "   \e[0;32mkey:generate\e[0m                   Set the application key\n";
        echo "\033[1;33mmake\033[0m\n";
        echo "   \e[0;32mmake:controller\e[0m                Create a new controller class\n";
        echo "   \e[0;32mmake:middleware\e[0m                Create a new middleware class\n";
        echo "   \e[0;32mmake:schedule\e[0m                  Create a new schedule class\n";
        echo "   \e[0;32mmake:model\e[0m                     Create a new model class\n";
        echo "   \e[0;32mmake:mail\e[0m                      Create a new email class\n";
        echo "   \e[0;32mmake:migration\e[0m                 Create a new migration file\n";
        echo "\033[1;33mschedule\033[0m\n";
        echo "   \e[0;32mschedule:run\e[0m                   Run the scheduled commands\n";
        echo "\033[1;33mmigrate\033[0m\n";
        echo "   \e[0;32mmigrate:fresh\e[0m                  Drop all tables and re-run all migrations\n";
        echo "   \e[0;32mmigrate:install\e[0m                Create the migration repository\n";
        echo "   \e[0;32mmigrate:refresh\e[0m                Reset and re-run all migrations\n";
        echo "   \e[0;32mmigrate:reset\e[0m                  Rollback all database migrations\n";
        echo "   \e[0;32mmigrate:rollback\e[0m               Rollback the last database migration\n";
        echo "   \e[0;32mmigrate:status\e[0m                 Show the status of each migration\n";
        echo "\033[1;33mcache\033[0m\n";
        echo "   \e[0;32mcache:clear\e[0m                    Flush the application cache\n";
        echo "\033[1;33mroute\033[0m\n";
        echo "   \e[0;32mroute:list\e[0m                     List all registered routes\n";
        echo "\033[1;33mview\033[0m\n";
        echo "   \e[0;32mview:clear\e[0m                     Clear all compiled view files\n";
        echo "   \e[0;32mview:create\e[0m                    Create a new view file\n";
    }

    public function run()
    {
        $server = $_SERVER;
        if(isset($server['argv'])){
            if(count($server['argv']) == 1){
                return $this->getHelper();
            }
            else{
                $command = $server['argv'][1];
                $arg = isset($server['argv'][2])?$server['argv'][2]:null;
                if($this->validCommand($command)){
                    return $this->runCommand($command, $arg);
                }
                elseif($command == '-v'){
                    // Display version
                    echo 'Cuveen '.App::VERSION;
                }
                else{
                    echo "\033[1;31mCommand `".$command."` is not valid\033[0m\n";
                }
            }
        }
    }

    protected function validCommand($command){
        return in_array($command, $this->list_commands);
    }

    protected function runCommand($command, $arg){
        $command = str_replace(':','',$command);
        return $this->$command($arg);
    }

    protected function validClassName($string)
    {
        if(in_array($string, ['Controller','Model','Cuveen'])){
            return false;
        }
        else {
            return preg_match('/^[a-zA-Z\x7f-\xff][a-zA-Z0-9\x7f-\xff]*/',$string);
        }
    }

    protected function validFileName($name)
    {
        return preg_match('/^[a-z0-9-_]+$/',$name);
    }

    protected function schedulerun($arg)
    {
        if(realpath($this->base_path . DIRECTORY_SEPARATOR.'schedules')) {
            if ($handle = opendir($this->base_path . DIRECTORY_SEPARATOR.'schedules'.DIRECTORY_SEPARATOR))
            {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != '.' && $entry != '..' && substr($entry, -4, 4) == '.php') {
                        if(!in_array($this->base_path . DIRECTORY_SEPARATOR.'schedules'.DIRECTORY_SEPARATOR.$entry, get_included_files())) {
                            include($this->base_path . DIRECTORY_SEPARATOR.'schedules' . DIRECTORY_SEPARATOR . $entry);
                        }
                        $info = pathinfo($entry);
                        $class_name = $info['filename'];
                        if (class_exists($class_name) && method_exists($class_name, 'handle')) {
                            $class = new $class_name();
                            call_user_func_array([$class, 'handle'], []);
                        }
                    }
                }
                closedir($handle);
            }
            else{
                echo "\033[1;33mNo command for schedule\033[0m\n";
            }
        }
        else{
            echo "\033[1;33mNo command for schedule\033[0m\n";
        }
    }

    private function includeAll($path)
    {
        if (is_dir($path) && $handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..' && substr($entry, -4, 4) == '.php') {
                    include($path.DIRECTORY_SEPARATOR.$entry);
                }
            }
            closedir($handle);
        }
    }

    public function migratefresh()
    {
        $config = Config::getInstance();
        if(!empty($config->get('database.connections.mysql.database'))){
            Database::rawExecute("SET FOREIGN_KEY_CHECKS = 0");
            $query = Database::rawExecute("SELECT table_name FROM information_schema.tables WHERE table_schema = '".$config->get('database.connections.mysql.database')."'");
            $statement = Database::getLastStatement();
            $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if(count($results) > 0){
                foreach($results as $result){
                    Database::rawExecute("DROP TABLE IF EXISTS {$result['table_name']}");
                }
            }
            echo "\e[0;32mDropped all tables successfully.\e[0m\n";
            $this->migrateinstall();
            $this->migratingall();
        }
    }

    private function migratingall()
    {
        $migrations_path = realpath($this->base_path.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations');
        if($migrations_path){
            if (is_dir($migrations_path) && $handle = opendir($migrations_path)) {
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != '.' && $entry != '..' && substr($entry, -4, 4) == '.php') {
                        $time_start = microtime(true);
                        if(!in_array($migrations_path.DIRECTORY_SEPARATOR.$entry, get_included_files())) {
                            include($migrations_path . DIRECTORY_SEPARATOR . $entry);
                        }
                        $file_name = str_replace('.php','', $entry);
                        echo "\033[1;33mMigrating: \033[0m".$file_name."\n";
                        $classes = get_declared_classes();
                        $class_name = end($classes);
                        $class = new $class_name();
                        call_user_func_array([$class, 'up'], []);
                        $time_end = microtime(true);
                        Database::raw_execute("INSERT INTO migrations (migration, batch) VALUES ('{$file_name}',1)");
                        echo "\033[1;32mMigrated: \033[0m".$file_name." (".number_format($time_end-$time_start,3)." seconds)\n";
                    }
                }
                closedir($handle);
            }
        }
        else{
            echo "\033[1;33mNothing to do\033[0m\n";
        }
    }

    private function rollingback($batch = false)
    {
        $migrations_path = realpath($this->base_path.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations');
        if($migrations_path){
            $where = ($batch)?" WHERE batch='{$batch}'":'';
            $query = Database::raw_execute("SELECT * FROM migrations".$where." ORDER BY batch ASC");
            $statement = Database::getLastStatement();
            $results = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if(count($results) > 0){
                foreach($results as $result){
                    $time_start = microtime(true);
                    $file_name = $result['migration'];
                    echo "\033[1;33mRolling back: \033[0m".$file_name."\n";
                    Database::raw_execute("DELETE FROM migrations WHERE id=".$result['id']);
                    if(file_exists($migrations_path.DIRECTORY_SEPARATOR.$file_name.'.php')) {
                        if(!in_array($migrations_path.DIRECTORY_SEPARATOR.$file_name.'.php', get_included_files())) {
                            include($migrations_path . DIRECTORY_SEPARATOR . $file_name.'.php');
                        }
                        $classes = get_declared_classes();
                        $class_name = end($classes);
                        $class = new $class_name();
                        if(method_exists($class, 'down')) {
                            call_user_func_array([$class, 'down'], []);
                            $time_end = microtime(true);
                            echo "\033[1;32mRolled back: \033[0m".$file_name." (".number_format($time_end-$time_start,3)." seconds)\n";
                        }
                        else{
                            echo "\033[1;31mRollback function not found: \033[0m".$file_name."\n";
                        }
                    }
                    else{
                        echo "\033[1;31mMigration not found: \033[0m".$file_name."\n";
                    }
                }
            }
            else{
                echo "\033[1;33mNothing to do\033[0m\n";
            }
        }
        else{
            echo "\033[1;31mMigrations folder does not exist\033[0m\n";
        }
    }

    public function migraterefresh($arg)
    {
        $this->migrateinstall(false);
        $this->rollingback();
        $this->migrateforce(false);
    }

    public function migrateforce($display = true)
    {
        // Migration not exist file
        $migrations_path = realpath($this->base_path.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations');
        if($migrations_path){
            $migrated = 0;
            if (is_dir($migrations_path) && $handle = opendir($migrations_path)) {
                $this->migrateinstall(false);
                $query = Database::raw_execute("SELECT MAX(batch) as maxbatch FROM migrations LIMIT 1");
                $statement = Database::getLastStatement();
                $maxBatch = $statement->fetch(\PDO::FETCH_ASSOC);
                $max_batch = (is_null($maxBatch['maxbatch']))?1:(int)$maxBatch['maxbatch']+1;
                while (false !== ($entry = readdir($handle))) {
                    if ($entry != '.' && $entry != '..' && substr($entry, -4, 4) == '.php') {
                        $time_start = microtime(true);
                        $file_name = str_replace('.php','', $entry);
                        $query = Database::raw_execute("SELECT * FROM migrations WHERE migration = '{$file_name}'");
                        $statement = Database::getLastStatement();
                        $resultsCheck = $statement->fetchAll(\PDO::FETCH_ASSOC);
                        if(count($resultsCheck) == 0){
                            if(!in_array($migrations_path.DIRECTORY_SEPARATOR.$entry, get_included_files())) {
                                include($migrations_path . DIRECTORY_SEPARATOR . $entry);
                            }
                            $migrated++;
                            echo "\033[1;33mMigrating: \033[0m".$file_name."\n";
                            $classes = get_declared_classes();
                            $class_name = end($classes);
                            $class = new $class_name();
                            call_user_func_array([$class, 'up'], []);
                            $time_end = microtime(true);
                            Database::raw_execute("INSERT INTO migrations (migration, batch) VALUES ('{$file_name}','{$max_batch}')");
                            echo "\033[1;32mMigrated: \033[0m".$file_name." (".number_format($time_end-$time_start,3)." seconds)\n";
                        }
                    }
                }
                closedir($handle);
            }
            if($migrated == 0){
                if($display) {
                    echo "\033[1;33mNothing to do\033[0m\n";
                }
            }
        }
        else{
            if($display) {
                echo "\033[1;33mNothing to do\033[0m\n";
            }
        }
    }

    public function migraterollback($arg)
    {
        // Rollback last migration
        $step = false;
        if(strpos($arg, 'step') !== false){
            $step = str_replace('--step=','',$arg);
            $step = str_replace('step=','',$step);
            $step = str_replace('--step','',$step);
            $step = str_replace('step','',$step);
        }
        else{
            $query = Database::raw_execute("SELECT MAX(batch) as maxbatch FROM migrations LIMIT 1");
            $statement = Database::getLastStatement();
            $maxBatch = $statement->fetch(\PDO::FETCH_ASSOC);
            $step = (is_null($maxBatch['maxbatch']))?1:(int)$maxBatch['maxbatch'];
        }
        $this->rollingback($step);
    }

    public function migratereset()
    {
        // Rollback all migration and truncate migrations table
        $this->migrateinstall(false);
        $this->rollingback();
        Database::raw_execute("TRUNCATE `migrations`");
    }

    public function migratestatus()
    {
        $migrations_path = realpath($this->base_path.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations');
        if($migrations_path && is_dir($migrations_path) && $handle = opendir($migrations_path)){
            $results = [];
            $key =0;
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..' && substr($entry, -4, 4) == '.php') {
                    $file_name = str_replace('.php','', $entry);
                    $results[$key]['migration'] = $file_name;
                    $results[$key]['ran'] = 'No';
                    $results[$key]['batch'] = '';
                    $query = Database::raw_execute("SELECT * FROM migrations WHERE migration='{$file_name}' LIMIT 1");
                    $statement = Database::getLastStatement();
                    $migration = $statement->fetch(\PDO::FETCH_ASSOC);
                    if($migration){
                        $results[$key]['ran'] = 'Yes';
                        $results[$key]['batch'] = $migration['batch'];
                    }
                    $key++;
                }
            }
            $table = new ConsoleTable();
            $table->addHeader('Ran?')
                ->addHeader('Migration')
                ->addHeader('BATCH')
                ->showAllBorders();
            foreach($results as $result){
                $table->addRow()
                    ->addColumn($result['ran'])
                    ->addColumn($result['migration'])
                    ->addColumn($result['batch']);
            }
            $table->display();
        }
        else{
            echo "\033[1;33mNothing to display\033[0m\n";
        }
    }

    public function migrateinstall($display = true)
    {
        $query = Database::rawExecute("SHOW TABLES LIKE 'migrations'");
        $statement = Database::getLastStatement();
        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        if($result){
            if($display) {
                echo "\033[1;33mMigration table already exist\033[0m\n";
            }
        }
        else{
            Database::rawExecute("CREATE TABLE `migrations` (`id` int(11) PRIMARY KEY AUTO_INCREMENT NOT NULL,`migration` varchar(255) NOT NULL,`batch` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
            if($display) {
                echo "\e[0;32mMigration table created successfully.\e[0m\n";
            }
        }
    }

    protected function makemigration($arg)
    {
        if(!is_null($arg)){
            $className = $arg;
            $table = false;
            $exs = explode(':', $arg);
            if(strpos($arg, ':') !== false && count($exs) > 1){
                $ex = explode(':', $arg);
                $className = $exs[0];
                $table = $exs[1];
            }
            $database_path = realpath($this->base_path.DIRECTORY_SEPARATOR.'database');
            if(!$database_path){
                mkdir($this->base_path.DIRECTORY_SEPARATOR.'database', 0777);
            }
            $database_path = realpath($this->base_path.DIRECTORY_SEPARATOR.'database');
            $migrations_path = realpath($database_path.DIRECTORY_SEPARATOR.'migrations');
            if(!$migrations_path){
                mkdir($database_path.DIRECTORY_SEPARATOR.'migrations', 0777);
            }
            $migrations_path = realpath($database_path.DIRECTORY_SEPARATOR.'migrations');
            $this->includeAll($migrations_path);
            $className = trim($className);
            if(class_exists('Cuveen\Migration\\'.ucfirst($className))){
                echo "\033[1;33mMigration class `".$className."` already exist\033[0m\n";
            }
            elseif(!$this->validClassName($className)){
                echo "\033[1;33mMigration class is not valid\033[0m\n";
            }
            else{
                $content = "<?php\n\n";
                $content .= "namespace Cuveen\Migration;\n\n";
                $content .= "use Cuveen\Database\Migration;\n\n";
                $content .= "class ".ucfirst($className)." extends Migration\n";
                $content .= "{\n";
                $content .= "\t/**\n";
                $content .= "\t* Run the migrations.\n";
                $content .= "\t*\n";
                $content .= "\t* @return void\n";
                $content .= "\t*/\n";
                $content .= "\tpublic function up()\n";
                $content .= "\t{\n";
                if($table) {
                    $content .= "\t\t".'$this->create(\''.$table.'\', function(){'."\n";
                    $content .= "\t\t\t//\n";
                    $content .= "\t\t});\n";
                }
                else{
                    $content .= "\t\t//\n";
                }
                $content .= "\t}\n";
                $content .= "\t/**\n";
                $content .= "\t* Reverse the migrations.\n";
                $content .= "\t*\n";
                $content .= "\t* @return void\n";
                $content .= "\t*/\n";
                $content .= "\tpublic function down()\n";
                $content .= "\t{\n";
                if($table) {
                    $content .= "\t\t".'$this->table(\''.$table.'\');'."\n";
                }
                else{
                    $content .= "\t\t//\n";
                }
                $content .= "\t}\n";
                $content .= "}\n";
                $file_name = date('Y_m_d_H_i_s').'_'.Str::slug($className).'.php';
                $file_path = $migrations_path.'/'.$file_name;
                if($this->createFile($file_path, $content)){
                    echo "\e[0;32mCreated Migration:\e[0m ".$file_name;
                }
                else{
                    echo "\033[1;33mSomething went wrong\033[0m\n";
                }
            }
        }
        else {
            echo "\033[1;33mNot enough arguments (missing: \"name\").\033[0m\n";
        }
    }

    protected function makemail($arg)
    {
        if(!is_null($arg)){
            if(strpos($arg, '/') !== false){
                echo "\033[1;33mMail class can not call child\033[0m\n";
            }
            else{
                $class_name = trim($arg);
                $full_mail_path = realpath($this->base_path.DIRECTORY_SEPARATOR.'mail');
                if(!$full_mail_path){
                    mkdir($this->base_path.DIRECTORY_SEPARATOR.'mail', 0777);
                }
                $full_mail_path = realpath($this->base_path.DIRECTORY_SEPARATOR.'mail');
                $file_name = $full_mail_path.DIRECTORY_SEPARATOR.$class_name.'.php';
                if(file_exists($file_name)){
                    echo "\033[1;33mMail class `".$class_name."` already exist\033[0m\n";
                }
                else{
                    $content = "<?php\n\n";
                    $content .= "namespace Cuveen\Mail;\n\n";
                    $content .= "use Cuveen\Mail\Mailable;\n\n";
                    $content .= "class ".$class_name." extends Mailable\n";
                    $content .= "{\n\n";
                    $content .= "\t".'public $data = [];'."\n\n";
                    $content .= "\t".'public function __construct($data = [])'."\n";
                    $content .= "\t{\n";
                    $content .= "\t\t".'$this->data = $data;'."\n";
                    $content .= "\t}\n\n";
                    $content .= "\t/**\n";
                    $content .= "\t*\n";
                    $content .= "\t* Build the message\n";
                    $content .= "\t* @return ".'$this'."\n";
                    $content .= "\t*/\n";
                    $content .= "\tpublic function build()\n";
                    $content .= "\t{\n";
                    $content .= "\t\t".'return $this->from(\'example@example.com\')->template(\'emails.example\')->with($this->data);'."\n";
                    $content .= "\t}\n";
                    $content .= "}\n";
                    if($this->createFile($file_name, $content)){
                        echo "\e[0;32mMail class created successfully\e[0m";
                    }
                    else{
                        echo "\033[1;33mSomething went wrong\033[0m\n";
                    }
                }
            }
        }
        else {
            echo "\033[1;33mMail name is required\033[0m\n";
        }
    }

    protected function makeschedule($arg)
    {
        if(!is_null($arg)){
            $exs = explode('/',$arg);
            $full_schedules_path = realpath($this->base_path.DIRECTORY_SEPARATOR.'schedules');
            if(!$full_schedules_path){
                mkdir($this->base_path.DIRECTORY_SEPARATOR.'schedules', 0777);
            }
            $full_schedules_path = realpath($this->base_path.DIRECTORY_SEPARATOR.'schedules');
            if(count($exs) == 1){
                if($this->validClassName(trim($arg))){
                    $file_name = $full_schedules_path.DIRECTORY_SEPARATOR.trim($arg).'.php';
                    $class_name = trim($arg);
                    if(file_exists($file_name)){
                        echo "\033[1;33mSchedule class already exist\033[0m\n";
                    }
                    else {
                        if(!empty($file_name) && !empty($class_name)){
                            $content = "";
                            $content .= "<?php\n";
                            $content .= "use Cuveen\App;\n\n";
                            $content .= "class ".$class_name." extends App\n";
                            $content .= "{\n";
                            $content .= "\tpublic function handle()\n";
                            $content .= "\t{\n";
                            $content .= "\t\t//You can configure the scheduled jobs here\n";
                            $content .= "\t\t//And start schedule run\n";
                            $content .= "\t\t".'$this->scheduler->run();'."\n";
                            $content .= "\t}\n";
                            $content .= "}\n";
                            $content .= "?>\n";
                            if($this->createFile($file_name, $content)){
                                echo "\e[0;32mSchedule created successfully\e[0m";
                            }
                            else{
                                echo "\033[1;33mSomething went wrong\033[0m\n";
                            }
                        }
                    }
                }
                else{
                    echo "\033[1;33mSchedule class is not valid\033[0m\n";
                }
            }
            else{
                echo "\033[1;33mSchedule can not call child class\033[0m\n";
            }
        }
        else {
            echo "\033[1;33mSchedule name is required\033[0m\n";
        }
    }

    protected function makemiddleware($arg)
    {
        if(!is_null($arg)){
            $exs = explode('/',$arg);
            $middlewares_path = (!empty($this->app->config->get('middleware.path')))?$this->app->config->get('middleware.path'):'middlewares';
            $full_middlewares_path = realpath($this->base_path.DIRECTORY_SEPARATOR.$middlewares_path);
            if(!$full_middlewares_path){
                mkdir($this->base_path.DIRECTORY_SEPARATOR.$middlewares_path, 0777);
            }
            $full_middlewares_path = realpath($this->base_path.DIRECTORY_SEPARATOR.$middlewares_path);
            if($this->validClassName(trim($arg))){
                $file_name = $full_middlewares_path.DIRECTORY_SEPARATOR.trim($arg).'.php';
                $class_name = trim($arg);
                if(file_exists($file_name)){
                    echo "\033[1;33mMiddleware class already exist\033[0m\n";
                }
                else {
                    if(!empty($file_name) && !empty($class_name)){
                        if(file_exists($file_name)){
                            echo "\033[1;33mMiddleware class already exist\033[0m\n";
                        }
                        else {
                            $content = "";
                            $content .= "<?php\n";
                            $content .= "namespace Cuveen\Middleware;\n\n";
                            $content .= "class ".$class_name."\n";
                            $content .= "{\n";
                            $content .= "\tpublic function handle()\n";
                            $content .= "\t{\n";
                            $content .= "\t\t//You can check and redirect another url here\n";
                            $content .= "\t\treturn true;\n";
                            $content .= "\t}\n";
                            $content .= "}\n";
                            $content .= "?>\n";
                            if($this->createFile($file_name, $content)){
                                echo "\e[0;32mMiddleware created successfully\e[0m";
                            }
                            else{
                                echo "\033[1;33mSomething went wrong\033[0m\n";
                            }
                        }
                    }
                }
            }
            else{
                echo "\033[1;33mMiddleware class is not valid\033[0m\n";
            }
        }
        else {
            echo "\033[1;33mMiddleware name is required\033[0m\n";
        }
    }

    protected function makecontroller($arg)
    {
        if(!is_null($arg)){
            $exs = explode('\\',$arg);
            $controllers_path = (!empty($this->app->config->get('app.controllers')))?$this->app->config->get('app.controllers'):'controllers';
            $full_controllers_path = realpath($this->base_path.DIRECTORY_SEPARATOR.$controllers_path);
            if(count($exs) == 1){
                if($this->validClassName(trim($arg))){
                    $file_name = $full_controllers_path.DIRECTORY_SEPARATOR.trim($arg).'.php';
                    $class_name = trim($arg);
                    if(file_exists($file_name)){
                        echo "\033[1;33mController class already exist\033[0m\n";
                    }
                    else {
                        if(!empty($file_name) && !empty($class_name)){
                            if(file_exists($file_name)){
                                echo "\033[1;33mController class already exist\033[0m\n";
                            }
                            else {
                                $content = "";
                                $content .= "<?php\n";
                                $content .= "namespace Cuveen\Controller;\n\n";
                                $content .= "use Cuveen\Controller\Controller;\n\n";
                                $content .= "class ".$class_name." extends Controller\n";
                                $content .= "{\n\n";
                                $content .= "}\n";
                                $content .= "?>\n";
                                if($this->createFile($file_name, $content)){
                                    echo "\e[0;32mController created successfully\e[0m";
                                }
                                else{
                                    echo "\033[1;33mSomething went wrong\033[0m\n";
                                }
                            }
                        }
                    }
                }
                else{
                    echo "\033[1;33mController class is not valid\033[0m\n";
                }
            }
            else{
                $error = false;
                foreach($exs as $ex){
                    if(!$this->validClassName(trim($ex))){
                        $error = true;
                        break;
                    }
                }
                if(!$error){
                    $i = 0;
                    $file_name = '';
                    $class_namespace = 'Cuveen\Controller\\';
                    foreach($exs as $ex1){
                        $i++;
                        if($i == count($exs)){
                            $class_name = trim($ex1);
                            $file_name = $full_controllers_path.DIRECTORY_SEPARATOR.trim($ex1).'.php';
                        }
                        else{
                            $class_namespace .= trim($ex1);
                            $full_controllers_path .= DIRECTORY_SEPARATOR.trim($ex1);
                            if(!is_dir($full_controllers_path)){
                                mkdir($full_controllers_path, 0777);
                            }
                        }
                    }
                    if(!empty($file_name) && !empty($class_name)){
                        if(file_exists($file_name)){
                            echo "\033[1;33mController class already exist\033[0m\n";
                        }
                        else {
                            $content = "";
                            $content .= "<?php\n";
                            if(!empty($class_namespace)) {
                                $content .= "namespace ".$class_namespace.";\n\n";
                            }
                            $content .= "use Cuveen\Controller\Controller;\n\n";
                            $content .= "class ".$class_name." extends Controller\n";
                            $content .= "{\n\n";
                            $content .= "}\n";
                            $content .= "?>\n";
                            if($this->createFile($file_name, $content)){
                                echo "\e[0;32mController created successfully\e[0m";
                            }
                            else{
                                echo "\033[1;33mSomething went wrong\033[0m\n";
                            }
                        }
                    }
                }
                else{
                    echo "\033[1;33mController class is not valid\033[0m\n";
                }
            }
        }
        else {
            echo "\033[1;33mController name is required\033[0m\n";
        }
    }

    protected function makemodel($arg)
    {
        if(!is_null($arg)){
            $exs = explode('/',$arg);
            $full_models_path = realpath($this->base_path.DIRECTORY_SEPARATOR.'models');
            if(!$full_models_path){
                mkdir($this->base_path.DIRECTORY_SEPARATOR.'models', 0777);
            }
            $full_models_path = realpath($this->base_path.DIRECTORY_SEPARATOR.'models');
            if(count($exs) == 1){
                if($this->validClassName(trim($arg))){
                    $file_name = $full_models_path.DIRECTORY_SEPARATOR.trim($arg).'.php';
                    $class_name = trim($arg);
                    if(file_exists($file_name)){
                        echo "\033[1;33mModel class already exist\033[0m\n";
                    }
                    else {
                        if(!empty($file_name) && !empty($class_name)){
                            if(file_exists($file_name)){
                                echo "\033[1;33mModel class already exist\033[0m\n";
                            }
                            else {
                                $content = "";
                                $content .= "<?php\n";
                                $content .= "namespace Cuveen\Model;\n\n";
                                $content .= "class ".$class_name." extends Model\n";
                                $content .= "{\n\n";
                                $content .= "\t/*\n";
                                $content .= "\t*--------------------------------------------------------------\n";
                                $content .= "\t*       YOU CAN SET MANUALLY TABLE AND PRIMARY COLUMN\n";
                                $content .= "\t*--------------------------------------------------------------\n";
                                $content .= "\t*".' public static $_id_column = '."'id';\n";
                                $content .= "\t*".' public static $_table = '."'table_name';\n";
                                $content .= "\t*\n";
                                $content .= "\t*/\n";
                                $content .= "}\n";
                                $content .= "?>\n";
                                if($this->createFile($file_name, $content)){
                                    echo "\e[0;32mModel created successfully\e[0m";
                                }
                                else{
                                    echo "\033[1;33mSomething went wrong\033[0m\n";
                                }
                            }
                        }
                    }
                }
                else{
                    echo "\033[1;33mModel class is not valid\033[0m\n";
                }
            }
            else{
                echo "\033[1;33mModel class can not make child\033[0m\n";
            }
        }
        else {
            echo "\033[1;33mModel name is required\033[0m\n";
        }
    }

    protected function cacheclear()
    {
        $cache_path = (!empty($this->app->config->get('cache.path')))?$this->base_path.DIRECTORY_SEPARATOR.$this->app->config->get('cache.path'):DIRECTORY_SEPARATOR.'tmp';
        if(realpath($cache_path)){
            $files = glob($cache_path.DIRECTORY_SEPARATOR.'*');
            foreach($files as $file){ // iterate files
                if(is_file($file))
                    unlink($file); // delete file
            }
        }
        echo "\e[0;32mCache cleared successfully\e[0m";
    }

    protected function viewclear()
    {
        $complied_path = (!empty($this->app->config->get('view.compiled')))?$this->app->config->get('view.compiled'):$this->base_path.DIRECTORY_SEPARATOR.'complied';
        if(realpath($complied_path)){
            $files = glob($complied_path.DIRECTORY_SEPARATOR.'*');
            foreach($files as $file){ // iterate files
                if(is_file($file))
                    unlink($file); // delete file
            }
        }
        echo "\e[0;32mComplied view files cleared successfully\e[0m";
    }

    protected function viewcreate($arg)
    {
        if(!is_null($arg)){
            $exs = explode('.',$arg);
            $views_path = (!empty($this->app->config->get('view.path')))?$this->app->config->get('view.path'):'views';
            $full_views_path = realpath($this->base_path.DIRECTORY_SEPARATOR.$views_path);
            if(!$full_views_path){
                mkdir($this->base_path.DIRECTORY_SEPARATOR.$views_path, 0777);
            }
            $full_views_path = realpath($this->base_path.DIRECTORY_SEPARATOR.$views_path);
            if(count($exs) == 1){
                if($this->validFileName(trim($arg))){
                    $file_name = $full_views_path.DIRECTORY_SEPARATOR.trim($arg).'.blade.php';
                    if(file_exists($file_name)){
                        echo "\033[1;33mView file already exist\033[0m\n";
                    }
                    else {
                        $content = "";
                        if($this->createFile($file_name, $content)){
                            echo "\e[0;32mView file created successfully\e[0m";
                        }
                        else{
                            echo "\033[1;33mSomething went wrong\033[0m\n";
                        }
                    }
                }
                else{
                    echo "\033[1;33mView file name is not valid\033[0m\n";
                }
            }
            else{
                $error = false;
                foreach($exs as $ex){
                    if(!$this->validFileName(trim($ex))){
                        $error = true;
                        break;
                    }
                }
                if(!$error){
                    $i = 0;
                    $file_name = '';
                    foreach($exs as $ex1){
                        $i++;
                        if($i == count($exs)){
                            $class_name = trim($ex1);
                            $file_name = $full_views_path.DIRECTORY_SEPARATOR.trim($ex1).'.blade.php';
                        }
                        else{
                            $full_views_path .= DIRECTORY_SEPARATOR.trim($ex1);
                            if(!is_dir($full_views_path)){
                                mkdir($full_views_path, 0777);
                            }
                        }
                    }
                    if(!empty($file_name)){
                        if(file_exists($file_name)){
                            echo "\033[1;33mView file already exist\033[0m\n";
                        }
                        else {
                            $content = "";
                            if($this->createFile($file_name, $content)){
                                echo "\e[0;32mView file created successfully\e[0m";
                            }
                            else{
                                echo "\033[1;33mSomething went wrong\033[0m\n";
                            }
                        }
                    }
                }
                else{
                    echo "\033[1;33mView file name is not valid\033[0m\n";
                }
            }
        }
        else {
            echo "\033[1;33mView file name is required\033[0m\n";
        }
    }

    protected function routelist()
    {
        $routes = $this->app->router->getRoutes();
        $table = new ConsoleTable();
        $table->addHeader('Domain')
            ->addHeader('Method')
            ->addHeader('URI')
            ->addHeader('Name')
            ->addHeader('Action')
            ->addHeader('Middlewares')
            ->showAllBorders();
        if(count($routes) > 0){
            foreach($routes as $route){
                $table->addRow()
                    ->addColumn('')
                    ->addColumn($this->convertType($route['method']))
                    ->addColumn($this->convertType($route['pattern']))
                    ->addColumn($this->convertType($route['name']))
                    ->addColumn($this->convertType($route['fn'],'fn'))
                    ->addColumn($this->convertType($route['middlewares'],'middlewares'));
            }
        }
        $table->display();

    }

    public function returnSpace($lenght, $text, $secondText = '')
    {
        $result = '';
        if($lenght > strlen($text)){
            for($i = 0; $i < ($lenght-strlen($text)); $i++){
                $result .= ' ';
            }
        }
        elseif($lenght == strlen($text) && strlen($secondText) > $lenght){
            for($i = 0; $i < (strlen($secondText)-strlen($text)-1); $i++){
                $result .= ' ';
            }
        }

        return ' '.$text.$result.'  ';
    }

    public function returnLine($lenght, $text, $secondText = '')
    {
        $result = '';
        for($i = 0; $i < strlen($text); $i++){
            $result .= '-';
        }
        if($lenght > strlen($text)){
            for($i = 0; $i < ($lenght-strlen($text)); $i++){
                $result .= '-';
            }
        }
        return '-'.$result.'--';
    }

    protected function convertType($value, $type = 'route'){
        if($type == 'fn' && is_object($value)){
            $text_router = 'Closure';
        }
        elseif($type == 'middlewares'){
            $list_middlewares = '';
            if(count($value) > 0){
                foreach($value as $item){
                    $list_middlewares .= (empty($list_middlewares))?$item:', '.$item;
                }
            }
            $text_router = (strlen($list_middlewares) == 0)?'      ':$list_middlewares;
        }
        elseif($type == 'after'){
            $list_middlewares = '';
            if(count($value) > 0){
                foreach($value as $item){
                    $list_middlewares .= (empty($list_middlewares))?$item:', '.$item;
                }
            }
            $text_router = (strlen($list_middlewares) == 0)?'      ':$list_middlewares;
        }
        else{
            $text_router = $value;
        }
        return $text_router;
    }

    protected function mostLenght($routes = array(), $type = 'route'){
        $lenght = 0;
        foreach($routes as $route){
            $text_router = $this->convertType($route[$type], $type);
            if(strlen($text_router) > $lenght){
                $lenght = strlen($text_router);
            }
        }
        return $lenght;
    }

    protected function createFile($file_path, $content = null)
    {
        $myfile = fopen($file_path, "w") or die("Unable to open file!");
        fwrite($myfile, $content);
        fclose($myfile);
        return true;
    }

    protected function keygenerate()
    {
        $default_env = 'APP_NAME=Cuveen
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

VIEW_PATH=views
CONTROLLER_PATH=controllers

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cuveen
DB_USERNAME=root
DB_PASSWORD=

SESSION_LIFETIME=120

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="${APP_NAME}"';
        $random = base64_encode(Str::random(80,80));
        if(!file_exists($this->base_path.DIRECTORY_SEPARATOR.'.env')){
            $content_file = $default_env;
        }
        else{
            $content_file = file_get_contents($this->base_path.DIRECTORY_SEPARATOR.'.env');
            if($content_file == ''){
                $content_file = $default_env;
            }
        }
        $new_content = '';
        $lines = explode("\n", $content_file);
        foreach($lines as $line){
            $pos = strpos($line, 'APP_KEY=');
            if($pos !== false){
                $new_content .= 'APP_KEY='.$random."\n";
            }
            else{
                $new_content .= $line."\n";
            }
        }
        if($this->createFile($this->base_path.DIRECTORY_SEPARATOR.'.env',$new_content)){
            echo "\e[0;32mApplication key set successfully.\e[0m";
        }
        else{
            echo "\e[0;33mSomething went wrong\e[0m";
        }
    }
}