<?php

namespace App\LocalClass;

class Files{
    var $name;
    var $path;
    var $fullname;

    public function __construct($name='',$path='')
    {
        $this->path=$path;
        $this->name=$name;
        $this->fullname=$this->path.$this->name;
    }

    public function __toString()
    {
        return $this->fullname;
    }

}