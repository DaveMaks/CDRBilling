<?php
use Phalcon\Cli\Task;

class MainTask extends Task
{
    public function mainAction()
    {
        $sql='SELECT * FROM tests as tst LIMIT 3';
        $result=$this->getDI()->getShared('db')->query($sql);
        var_dump($result->fetchAll());
        echo 'This is the default task and the default action' . PHP_EOL;
    }
    public function parsingcdrAction(){
        echo 'parsing';
    }
}