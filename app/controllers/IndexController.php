<?php
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\View;
use Phalcon\Di;
use Phalcon\Db\Result\Pdo;
use App\LocalClass\Files;
use App\HTML\HelperPlugin;
use App\LocalClass\viewCdrData;


class IndexController extends Controller
{
    public function indexAction()
    {

        //$this->view->setVar('topCallOut',$topCallOut);
    }

    public function testAction($param = null)
    {
        echo '!!!!!!!!!!!!asdfasdf';
    }

    public function errorAction()
    {
        $this->view->disable();
        echo 'errorAction';
    }
    public function show404Action()
    {

        $this->view->disable();
        echo $this->flash->output(true);
        //echo 'show404Action';
    }

}