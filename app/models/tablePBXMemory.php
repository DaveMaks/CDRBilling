<?php
use Phalcon\Mvc\Model;

class tablePBXMemory extends tablePBX
{


    public function initialize()
    {
        $this->setSource(TABLE_TBX_MEMORY);
    }


    public function TruncateTable()
    {
        $cnt = $this->getReadConnection()->query("TRUNCATE " . $this->getSource() . ';')->execute();
        return $cnt;
    }
}