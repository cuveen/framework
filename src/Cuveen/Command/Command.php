<?php
namespace Cuveen\Command;

use Cuveen\Helper\Str;

class Command {
    protected $app;
    protected $base_path;
    protected $list_commands = [
        'make:controller',
        'make:middleware',
        'make:schedule',
        'make:model',
        'make:mail',
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
        echo "\e[45m+--------------------------------------------------------------------------------+\e[0m\n";
        echo "\e[45m|                            CUVEEN ARTISAN COMMAND                              |\e[0m\n";
        echo "\e[45m+--------------------------------------------------------------------------------+\e[0m\n";
        echo "Cuveen Framework 1.3\n";
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
        echo "\033[1;33mschedule\033[0m\n";
        echo "   \e[0;32mschedule:run\e[0m                   Run the scheduled commands\n";
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
                    $composer_file = $this->base_path.'/composer.json';
                    if(!file_exists($composer_file)){
                        echo "\033[1;31mThis app did not installation via composer\033[0m\n";
                    }
                    else {
                    }
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
        if ($handle = opendir($this->base_path . '/schedules/')) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..' && substr($entry, -4, 4) == '.php') {
                    include($this->base_path.'/schedules/'.$entry);
                    $info = pathinfo($entry);
                    $class_name = $info['filename'];
                    if(class_exists($class_name) && method_exists($class_name, 'handle')){
                        $class = new $class_name();
                        call_user_func_array([$class,'handle'],[]);
                    }
                }
            }
            closedir($handle);
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
                $full_mail_path = realpath($this->base_path.'/mail');
                if(!$full_mail_path){
                    mkdir($this->base_path.'/mail', 0777);
                }
                $full_mail_path = realpath($this->base_path.'/mail');
                $file_name = $full_mail_path.'/'.$class_name.'.php';
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
            $full_schedules_path = realpath($this->base_path.'/schedules');
            if(!$full_schedules_path){
                mkdir($this->base_path.'/schedules', 0777);
            }
            $full_schedules_path = realpath($this->base_path.'/schedules');
            if(count($exs) == 1){
                if($this->validClassName(trim($arg))){
                    $file_name = $full_schedules_path.'/'.trim($arg).'.php';
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
            $full_middlewares_path = realpath($this->base_path.'/'.$middlewares_path);
            if(!$full_middlewares_path){
                mkdir($this->base_path.'/'.$middlewares_path, 0777);
            }
            $full_middlewares_path = realpath($this->base_path.'/'.$middlewares_path);
            if(count($exs) == 1){
                if($this->validClassName(trim($arg))){
                    $file_name = $full_middlewares_path.'/'.trim($arg).'.php';
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
                    $class_namespace = '';
                    $class_name = '';
                    foreach($exs as $ex1){
                        $i++;
                        if($i == count($exs)){
                            $class_name = trim($ex1);
                            $file_name = $full_middlewares_path.'/'.trim($ex1).'.php';
                        }
                        else{
                            $class_namespace .= (empty($class_namespace))?trim($ex1):'\\'.trim($ex1);
                            $full_middlewares_path .= '/'.trim($ex1);
                            if(!is_dir($full_middlewares_path)){
                                mkdir($full_middlewares_path, 0777);
                            }
                        }
                    }
                    if(!empty($file_name) && !empty($class_name)){
                        if(file_exists($file_name)){
                            echo "\033[1;33mMiddleware class already exist\033[0m\n";
                        }
                        else {
                            $content = "";
                            $content .= "<?php\n";
                            if(!empty($class_namespace)) {
                                $content .= "namespace ".$class_namespace.";\n\n";
                            }
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
                else{
                    echo "\033[1;33mMiddleware class is not valid\033[0m\n";
                }
            }
        }
        else {
            echo "\033[1;33mMiddleware name is required\033[0m\n";
        }
    }

    protected function makecontroller($arg)
    {
        if(!is_null($arg)){
            $exs = explode('/',$arg);
            $controllers_path = (!empty($this->app->config->get('app.controllers')))?$this->app->config->get('app.controllers'):'controllers';
            $full_controllers_path = realpath($this->base_path.'/'.$controllers_path);
            if(count($exs) == 1){
                if($this->validClassName(trim($arg))){
                    $file_name = $full_controllers_path.'/'.trim($arg).'.php';
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
                    $class_namespace = '';
                    $class_name = '';
                    foreach($exs as $ex1){
                        $i++;
                        if($i == count($exs)){
                            $class_name = trim($ex1);
                            $file_name = $full_controllers_path.'/'.trim($ex1).'.php';
                        }
                        else{
                            $class_namespace .= (empty($class_namespace))?trim($ex1):'\\'.trim($ex1);
                            $full_controllers_path .= '/'.trim($ex1);
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
            $full_models_path = realpath($this->base_path.'/models');
            if(!$full_models_path){
                mkdir($this->base_path.'/models', 0777);
            }
            $full_models_path = realpath($this->base_path.'/models');
            if(count($exs) == 1){
                if($this->validClassName(trim($arg))){
                    $file_name = $full_models_path.'/'.trim($arg).'.php';
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
        $cache_path = (!empty($this->app->config->get('cache.path')))?$this->base_path.'/'.$this->app->config->get('cache.path'):'/tmp';
        if(realpath($cache_path)){
            $files = glob($cache_path.'/*');
            foreach($files as $file){ // iterate files
                if(is_file($file))
                    unlink($file); // delete file
            }
        }
        echo "\e[0;32mCache cleared successfully\e[0m";
    }

    protected function viewclear()
    {
        $complied_path = (!empty($this->app->config->get('view.compiled')))?$this->app->config->get('view.compiled'):$this->base_path.'/complied';
        if(realpath($complied_path)){
            $files = glob($complied_path.'/*');
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
            $full_views_path = realpath($this->base_path.'/'.$views_path);
            if(!$full_views_path){
                mkdir($this->base_path.'/'.$views_path, 0777);
            }
            $full_views_path = realpath($this->base_path.'/'.$views_path);
            if(count($exs) == 1){
                if($this->validFileName(trim($arg))){
                    $file_name = $full_views_path.'/'.trim($arg).'.blade.php';
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
                            $file_name = $full_views_path.'/'.trim($ex1).'.blade.php';
                        }
                        else{
                            $full_views_path .= '/'.trim($ex1);
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
        echo "+------------------".($this->returnLine($this->mostLenght($routes), 'URI'))."-".($this->returnLine($this->mostLenght($routes,'name'), 'Name'))."-".($this->returnLine($this->mostLenght($routes,'callback'), 'Action'))."-".($this->returnLine($this->mostLenght($routes,'before'), 'Before'))."-".($this->returnLine($this->mostLenght($routes,'after'), 'After'))."+\n";
        echo "| \e[0;32mDomain\e[0m | \e[0;32mMethod\e[0m |\e[0;32m".($this->returnSpace($this->mostLenght($routes), 'URI'))."\e[0m|\e[0;32m".($this->returnSpace($this->mostLenght($routes,'name'), 'Name'))."\e[0m|\e[0;32m".($this->returnSpace($this->mostLenght($routes,'callback'), 'Action'))."\e[0m|\e[0;32m".($this->returnSpace($this->mostLenght($routes,'before'), 'Before'))."\e[0m|\e[0;32m".($this->returnSpace($this->mostLenght($routes,'after'), 'After'))."\e[0m|\n";
        echo "+------------------".($this->returnLine($this->mostLenght($routes), 'URI'))."-".($this->returnLine($this->mostLenght($routes,'name'), 'Name'))."-".($this->returnLine($this->mostLenght($routes,'callback'), 'Action'))."-".($this->returnLine($this->mostLenght($routes,'before'), 'Before'))."-".($this->returnLine($this->mostLenght($routes,'after'), 'After'))."+\n";
        if(count($routes) > 0){
            foreach($routes as $route){
                echo "|        |".($this->returnSpace($this->mostLenght($routes), $this->convertType($route['method'])))."|".($this->returnSpace($this->mostLenght($routes), $this->convertType($route['route'])))."|".($this->returnSpace($this->mostLenght($routes,'name'), $this->convertType($route['name'])))."|".($this->returnSpace($this->mostLenght($routes,'callback'), $this->convertType($route['callback'],'callback')))."|".($this->returnSpace($this->mostLenght($routes,'before'), $this->convertType($route['before'],'before')))."|".($this->returnSpace($this->mostLenght($routes,'after'), $this->convertType($route['after'],'after')))."|\n";
                echo "+------------------".($this->returnLine($this->mostLenght($routes), $this->convertType($route['route'])))."-".($this->returnLine($this->mostLenght($routes,'name'), $this->convertType($route['name'])))."-".($this->returnLine($this->mostLenght($routes,'callback'), $this->convertType($route['callback'],'callback')))."-".($this->returnLine($this->mostLenght($routes,'before'), $this->convertType($route['before'],'before')))."-".($this->returnLine($this->mostLenght($routes,'after'), $this->convertType($route['after'],'after')))."+\n";
            }
        }

    }

    public function returnSpace($lenght, $text)
    {
        $result = '';
        if($lenght > strlen($text)){
            for($i = 0; $i < ($lenght-strlen($text)); $i++){
                $result .= ' ';
            }
        }
        return ' '.$text.$result.'  ';
    }

    public function returnLine($lenght, $text)
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
        if($type == 'callback' && is_object($value)){
            $text_router = 'Closure';
        }
        elseif($type == 'before'){
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
        if(!file_exists($this->base_path.'/.env')){
            $content_file = $default_env;
        }
        else{
            $content_file = file_get_contents($this->base_path.'/.env');
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
        if($this->createFile($this->base_path.'/.env',$new_content)){
            echo "\e[0;32mApplication key set successfully.\e[0m";
        }
        else{
            echo "\e[0;33mSomething went wrong\e[0m";
        }
    }
}