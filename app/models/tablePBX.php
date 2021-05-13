<?php
use Phalcon\Mvc\Model;

class tablePBX extends Model
{

    var $id;
    var $datetime;
    var $CalledNumber;
    var $direction;
    var $duration;
    var $tarif;
    var $cost;
    var $overinfo;

    public function initialize()
    {
        $this->setSource(TABLE_TBX);
    }


}