<?php


use Phalcon\Di;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;
use Phalcon\Mvc\Model\Query;

class tableSystemUsers extends Model
{
    public $id;
    public $login;
    public $password;
    public $description;
    /**
     * @var $roleList mixed array|string  массив ролей
     */
    public $roleList;
    public $role;

    public function initialize()
    {
        $this->setSource(TABLE_SYSTEM_USERS);
    }


    public function setNewPassword($str)
    {
        $s = new Phalcon\Security();
        $this->password = $s->hash($str);
    }

    public function PasswordValidation($str): bool
    {
        if (empty($str) || empty($this->password)) return false;
        $s = new Phalcon\Security();
        return $s->checkHash($str, $this->password);
    }

    public function afterFetch()
    {
        $this->roleList = json_decode($this->role,true);

    }

    public function afterValidation()
    {
        $this->role = json_encode($this->roleList);
    }


}

