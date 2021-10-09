<?php
use Phalcon\Cli\Task;

class MainTask extends Task
{
    public function mainAction()
    {
        echo 'Не указаны параметры, например cli.php import' . PHP_EOL;
    }
   
}