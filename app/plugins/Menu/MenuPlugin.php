<?php
/**
 * Created by PhpStorm.
 * User: vanuykov.m
 * Date: 20.12.2020
 * Time: 17:59
 */

namespace App\Plugin\Menu;

use App\Plugin\AclService;
use Phalcon\Di\Injectable,
    Phalcon\Di;

class MenuPlugin extends Injectable
{


    private function __construct()
    {

    }

    static function getMenu($id_Parent = 0)
    {
        /**
         * @var $listMenu tableMenu[]
         */
        $listMenu = tableMenu::findByIdParent($id_Parent);
        $retList = array();
        if (count($listMenu) > 0) {
            foreach ($listMenu as $key => $item) {
                if (empty($item->action))
                    $item->action = 'index';
                if (empty($item->controller)) {
                    $item->controller = 'Index';
                    $item->action = 'index';
                }
                if (self::AllowAcl($item->controller, $item->action))
                    $retList[] = $item;
            }
            if (count($retList) > 0)
                return $retList;
        }
        return null;
        //return Menu::getMenu()
    }

    static function AllowAcl($controller, $action)
    {
        //$di = Di::getDefault();
        $acl = AclService::$aclList;
        $access = false;
        if (is_array(\SecurityPlugin::$currentRoleList)) {
            foreach (\SecurityPlugin::$currentRoleList as $role) {
                if ($acl->isAllowed($role, $controller, $action)) {
                    $access = true;
                    break;
                }
            }
        } else {
            $access = $acl->isAllowed(\SecurityPlugin::$currentRoleList, $controller, $action);
        }
        return $access;
    }


}