<?php

namespace App\Plugin\Menu;
use Phalcon\Di;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;
use Phalcon\Mvc\Model\Query;

class tableMenu extends Model
{
    public $id;
    public $id_parent;
    public $name;
    public $controller;
    public $action;
    public $class;
    public $param;
    public $urlPath;


    public function initialize()
    {
        $this->setSource(TABLE_MENU);
       // $this->belongsTo('id_unit', 'tableUnits', 'id', ['alias' => 'Unit']);
    }
    public function afterFetch(){
        $this->urlPath=$this->controller.'/'.$this->action.'/'.$this->param;
    }

/*
cucmdb.tb_menu (
id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
id_parent int(11) UNSIGNED NOT NULL DEFAULT 0,
name varchar(50) DEFAULT NULL,
class varchar(255) DEFAULT NULL,
controller varchar(255) DEFAULT NULL,
action varchar(255) DEFAULT NULL,
PRIMARY KEY (id)*/
}

