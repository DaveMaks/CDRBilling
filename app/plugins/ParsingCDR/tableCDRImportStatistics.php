<?php

namespace App\CDR;

use Phalcon\Mvc\Model;

class tableCDRImportStatistics extends Model
{
    public $datetime;
    public $filename;
    public $countRows;
    public $skipRows;
    public $status;


    public function initialize()
    {
        $this->setSource(TABLE_CDR_IMPORT_STATISTICS);
    }

}

