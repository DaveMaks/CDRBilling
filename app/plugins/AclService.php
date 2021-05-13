<?php

namespace App\Plugin;

use Phalcon\Mvc\Dispatcher,
    Phalcon\Di\Injectable,
    Phalcon\Acl\Enum as ACL,
    Phalcon\Acl\Role,
    Phalcon\Acl\Adapter\Memory as AclList;

class AclService extends Injectable
{

    const ADMIN = 'admin';
    const REPORT = 'report';
    const GUEST = 'guest';

    const ROLES = [self::ADMIN,
        self::REPORT,
        self::GUEST];

    /**
     * @var $aclList Phalcon\Acl\Adapter\Memory
    */
    static public $aclList;

    public function __construct()
    {
        self::$aclList = $this->getAcl();
    }


    public function getAcl()
    {
        if (isset(self::$aclList))
            return self::$aclList;
        else
            return $this->loadAcl();
    }

    private function loadAcl()
    {
        /// TODO Добавить раздачу ролей из БД
        $acl = new AclList();
        $acl->setDefaultAction(ACL::DENY);
        $acl->addRole(new Role(self::ADMIN, 'Администратор системы'));
        $acl->addRole(new Role(self::REPORT, 'Доступ на отчеты'));
        $acl->addRole(new Role(self::GUEST, 'Доступ только на авторизацию'));
        //$acl->allow(self::ADMIN, '*', '*');

        $resources = include APP_PATH . '/config/acl.php';
        foreach ($resources as $role=>$components){
            foreach ($components as $name=>$access){
                $acl->addComponent($name,$access);
                $acl->allow($role,$name,$access);
            }
        }
       // $acl->addComponent('Index','*')
       // $acl->addComponent(new \Phalcon\Acl\Component("dashborad"),'*');
            //$acl->addResource(new \Phalcon\Acl\Resource($resource), $registerActions);


       // $acl->allow(self::GUEST, 'dashborad', '*');

        return $acl;

        /*
        $acl->allow('*', 'Auth', 'Index');
        $acl->allow('admins', '*', '*');
        $acl->allow('reports', 'Index', '*');
        $acl->allow('reports', 'Dashborad', '*');
        */
    }

    public function getRole()
    {
        $auth = $this->session->get('userRole');
        if (!$auth) {
            $role = self::GUEST;
        } else {
            /* if ($auth->admin_session == true) {
                 $role = \Admin\Model\AdminUser::getRoleById($auth->id);
             } else {
                 $role = 'member';
             }*/
        }
        return $role;
    }


}