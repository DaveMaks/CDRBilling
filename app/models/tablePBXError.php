<?php
use Phalcon\Mvc\Model;

class tablePBXError extends Model
{
    public $id;
    public $message;
    public $date;

    public function initialize()
    {
        $this->setSource(TABLE_TBX_ERROR);
    }


    public function TruncateTable()
    {
        $cnt = $this->getReadConnection()->query("TRUNCATE " . $this->getSource() . ';')->execute();
        return $cnt;
    }
}