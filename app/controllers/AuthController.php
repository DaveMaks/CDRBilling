<?php
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Model\Query;
use Phalcon\Di;
use Phalcon\Db\Result\Pdo,
    Phalcon\Mvc\View;

class AuthController extends Controller
{
    public function initialize()
    {
        //var_dump($this->session->get('auth'));
    }

    public function indexAction()
    {
        $this->view->disableLevel(View::LEVEL_MAIN_LAYOUT);
        //setRenderLevel();

        //var_dump($this->view->getActiveRenderPath());
        //echo __METHOD__;
        //return '<h1>Hello!index</h1>';//.print_r($query->execute(),true);
    }

    public function signoutAction()
    {
        $this->view->disable();
        $this->session->remove('userID');
        $this->session->remove('userMetodAuth');
        $this->session->remove('userRole');
        $this->session->remove('userName');
        $this->response->redirect('Auth/index');
        return false;
    }

    public function isUserValidationAction()
    {
        $this->view->disable();
        if ($this->request->isPost()) {
            try {
                $this->session->remove('userID');
                $this->session->remove('userMetodAuth');
                $this->session->remove('userRole');
                $this->session->remove('userName');

                if ($this->request->getPost('cbxFromAD') == 'true') {
                    /*AD Авторизация*/
                    try {
                        $ldap = new LDAP($this->config->ActiveDirectory->host, $this->request->getPost('txtlogin'),
                            $this->request->getPost('txtpassword'),
                            $this->config->ActiveDirectory->toArray());
                        $userInfo = $ldap->Search('(&(objectClass=' . OBJECTCLASS_USER . ')(sAMAccountName=' . $this->request->getPost('txtlogin') . ')(!(userAccountControl:1.2.840.113556.1.4.803:=2)))');
                        if (empty($userInfo[0]))
                            throw new Exception('Пользователь не найден');
                        $grpList = LDAP::memberof2Array($userInfo[0]['memberof']);

                        if (in_array($this->config->ActiveDirectory->reportGroupFromAD, $grpList))
                            $this->session->set('userRole', \App\Plugin\AclService::REPORT);
                        if (in_array($this->config->ActiveDirectory->adminGroupFromAD, $grpList))
                            $this->session->set('userRole', \App\Plugin\AclService::ADMIN);

                        $this->session->set('userMetodAuth', 'AD');
                        $this->session->set('userID', $userInfo[0]['samaccountname'][0]);
                        $this->session->set('userName', $userInfo[0]['name'][0]);
                        $this->response->redirect('/Index');
                        return false;
                    } catch (\Exception $ex) {
                        throw new \Exception('Не возможно выполнить авторизацию используя введеные учетные данные в AD' . '(' . $ex->getMessage() . ')');
                    }
                } else {
                    /*Локальная авторизация*/
                    /**
                     * @var $usr tableSystemUsers
                     */
                    $usr = tableSystemUsers::findFirstByLogin($this->request->getPost('txtlogin'));
                    if (is_null($usr))
                        throw new Exception('Не найден пользователь');
                    if (!$usr->PasswordValidation($this->request->getPost('txtpassword')))
                        throw new Exception('Не верный пароль');
                    if (empty($usr->roleList))
                        throw new Exception('Пользователю не назначены роли');
                    $this->session->set('userID', $usr->id);
                    $this->session->set('userMetodAuth', 'LOCAL');
                    $this->session->set('userName', $usr->description);
                    if (!empty($usr->roleList[0]))
                        $this->session->set('userRole', $usr->roleList[0]);

                }
                $this->response->redirect('/Index');
                return false;
            } catch (Exception $ex) {
                //echo $ex->getMessage();
                $this->flash->error($ex->getMessage());
                $this->response->redirect('/Auth');
                return false;
            }
        }
    }

    public function testAction()
    {
        //echo __METHOD__;
    }
}