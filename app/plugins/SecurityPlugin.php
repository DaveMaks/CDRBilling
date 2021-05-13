<?php
use Phalcon\Acl;
use Phalcon\Acl\Component;
use Phalcon\Acl\Role;
use Phalcon\Acl\Adapter\Memory as AclList;
use Phalcon\Acl\Resource;
use Phalcon\Di\Injectable;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;
use App\Plugin\AclService;
use Phalcon\Mvc\Dispatcher\Exception as DispatchException;

//use Phalcon\Mvc\


class SecurityPlugin extends Injectable
{
    public static $currentRoleList = array(\App\Plugin\AclService::GUEST);
    public static $currentUserID;
    public static $currentUserName;

    public function beforeDispatchLoop(Event $event, Dispatcher $dispatcher, $exception)
    {
        $acl = new AclService();

    }

    public function beforeException($event, $dispatcher, $exception)
    {

        if ($exception instanceof DispatchException) {
            $this->flash->error($exception->getMessage());
            $dispatcher->forward(
                [
                    'controller' => 'Index',
                    'action' => 'show404',
                ]
            );
        }
        return false;
    }

    public function beforeDispatch(Event $event, Dispatcher $dispatcher)
    {
        $auth = null;

        if ($this->session->has('userID') && $this->session->has('userRole')) {
            $auth = $this->session->get('userID');
            $role = $this->session->get('userRole');
        } else {
            $role = AclService::GUEST;
        }
        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

        /*if (!AclService::$aclList->isComponent($controller)) {
            $this->flash->error("Не верная ссылка");
            $dispatcher->forward(
                [
                    'controller' => 'Index',
                    'action' => 'show404',
                ]
            );
            return false;
        }*/

        self::$currentRoleList = $role;
        self::$currentUserID = $auth;
        self::$currentUserName = ($this->session->has('userName')) ? $this->session->get('userName') : '';

        if (AclService::$aclList->isAllowed($role, $controller, $action)) {
            return true;
        }
        /* $this->response->redirect('Auth/index');
         return false;*/
        $dispatcher->forward(
            array(
                'controller' => 'Auth',
                'action' => 'index'
            ));

        /*
         if ($chk)
             return true;
         if (!empty(self::$currentRoleList) && !in_array(\App\Plugin\AclService::GUEST, self::$currentRoleList))
             throw new Dispatcher\Exception("", Dispatcher\Exception::EXCEPTION_ACTION_NOT_FOUND); */

        // $this->response->redirect('Auth/index');
        return false;

    }

    public function CheckUser($id_user, $controller, $action): bool
    {
        $access = false;

        /*
        if (!empty($id_user)) {
            $usr = tableSystemUsers::findFirst($id_user);
            self::$currentRoleList = $usr->roleList;
        }
        if (empty(self::$currentRoleList))
            self::$currentRoleList = [\App\Plugin\AclService::GUEST];
        */

        /**
         * @var $acl AclList
         */
        /*$acl = $this->acl;
        if (is_array(self::$currentRoleList)) {
            foreach (self::$currentRoleList as $role) {
                if ($acl->isAllowed($role, $controller, $action)) {
                    $access = true;
                    break;
                }
            }
        } else {
            $access = $acl->isAllowed(self::$currentRoleList, $controller, $action);
        }*/
        return $access;
    }


    private function accessDenied($role, $resourceKey = null, $resourceVal = null, View $view)
    {
        if (in_array($role, ['guest', 'member'])) {
            return $this->redirect('/admin');
        }

        $view->setViewsDir(__DIR__ . '/../modules/Index/views/');
        $view->setPartialsDir('');
        $view->message = "$role - Access Denied to resource <b>$resourceKey::$resourceVal</b>";
        $view->partial('error/error403');

        $response = new \Phalcon\Http\Response();
        $response->setHeader(403, 'Forbidden');
        $response->sendHeaders();
        echo $response->getContent();
        exit;
    }

    private function resourceNotFound($resourceKey, View $view)
    {
        $view->setViewsDir(__DIR__ . '/../modules/Index/views/');
        $view->setPartialsDir('');
        $view->message = "Acl resource <b>$resourceKey</b> in <b>/app/config/acl.php</b> not exists";
        $view->partial('error/error404');
        $response = new \Phalcon\Http\Response();
        $response->setHeader(404, 'Not Found');
        $response->sendHeaders();
        echo $response->getContent();
        exit;
    }

    private function redirect($url, $code = 302)
    {
        switch ($code) {
            case 301 :
                header('HTTP/1.1 301 Moved Permanently');
                break;
            case 302 :
                header('HTTP/1.1 302 Moved Temporarily');
                break;
        }
        header('Location: ' . $url);
        exit;
    }

}