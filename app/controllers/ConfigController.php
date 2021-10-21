<?php

use Phalcon\Mvc\Controller;
use Phalcon\Validation;
use Phalcon\Validation\Validator\File as FileValidator;
use App\Validator\MimeTypeUpload;
use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\View;
use Phalcon\Di;
use Phalcon\Db\Result\Pdo;
use App\LocalClass\Files;
use App\HTML\HelperPlugin;
use App\LocalClass\viewCdrData;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;

//use App\LDAP;

use \App\PBX\ParsingPBXBilling;
use Phalcon\Filter;

class ConfigController extends Controller
{
    public function indexAction()
    {

        //$this->view->setVar('topCallOut',$topCallOut);
    }

    public function testAction($param = null)
    {

    }


    /**
     * Страница импорта билингового файла от постовщика связи
     *
     */
    public function ImportFormAction()
    {
        if (!$this->session->has('DefVal_TypeParsingFormat'))
            $this->session->set('DefVal_TypeParsingFormat', 0);
        try {
            if ($this->request->isPost()) {
                if (!array_key_exists((int)$this->request->getPost('TypeParsingFormat'), ParsingPBXBilling::$listType)) {
                    throw new Exception("Выбран неверный тип шаблона");
                }
                $this->session->set('DefVal_TypeParsingFormat', (int)$this->request->getPost('TypeParsingFormat'));
                if ($this->request->hasFiles() === false) {
                    throw new Exception("Ошибка загрузки файла");
                }
            }
            // если есть файлы $_POST
            if ($this->request->numFiles(false) > 0) {
                $validator = new Validation();
                $validator->add(
                    "FilePBXToImport",
                    new FileValidator(
                        [
                            "maxSize" => "20M",
                            "messageSize" => "Превышен максимальный размер файла (:size)",
                        ]
                    )
                );
                $validator->add(
                    "FilePBXToImport",
                    new MimeTypeUpload([ // Прикручиваем свой валидатор, т.к. родной не корректно распознает *.xls из $_FILES['']['tmp_name']
                            "types" => [
                                ".xls",
                                ".xlsx",
                                ".csv"
                            ],
                            "message" => "Не верный тип файла"
                        ]
                    )
                );
                $messages = $validator->validate($_FILES);
                if (count($messages)) {
                    foreach ($messages as $message) {
                        $this->flash->error($message);
                    }
                    throw new Exception();
                }
                // перебераем все полученные файлы
                foreach ($this->request->getUploadedFiles() as $file) {
                    if (!preg_match('/(.+)\.(\w+)$/i', $file->getName(), $fileInfo, PREG_OFFSET_CAPTURE))
                        throw new Exception("Ошибка preg_match");
                    // ложим в архивную папку
                    $fileToArchive = $this->config->folderArchivePBX . '' . $fileInfo[1][0] . '_' . time() . '.' . $fileInfo[2][0];
                    if (!@$file->moveTo($fileToArchive)) {
                        throw new Exception('Ошибка загрузки файла ' . $file->getName() . ', проверьте доступность/права на папку (' . $this->config->folderArchivePBX . ')');
                    }
                    // создаем объект для парсинга
                    $prasing = new ParsingPBXBilling($fileToArchive, ParsingPBXBilling::$listType[(int)$this->request->getPost('TypeParsingFormat')]);
                    // отправляем в импорт, должен вернутся массив из коллическва добавленных и всего строк в файле
                    list($newrow, $countrow) = $this->ImportPBXDataToTempDB($prasing);
                    $this->flash->success("Добавленно $newrow из $countrow");
                }
                $this->response->redirect($this->url->get(
                    $this->dispatcher->getControllerName() . '/' . $this->dispatcher->getActionName())); // избавляемся от предупреждения браузера при попытке обновить стр. "отправить форму заново"
            }
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            if (!empty($msg))
                $this->flash->error($msg);
        }
        $this->view->setVar('DefVal_TypeParsingFormat', $this->session->get('DefVal_TypeParsingFormat'));
    }

    /**
     * Вывод таблицы
     */
    public function ViewImportTableAction($idPage = 0)
    {
        if ($this->request->isAjax()) {
            $this->view->setRenderLevel(
                View::LEVEL_ACTION_VIEW
            );
        };
        $this->view->setVar('PageSelect', (int)$idPage);

    }

    public function MoveDataImportAction()
    {
        try {
            $act = $this->db->query(
                'INSERT INTO ' . TABLE_TBX . '(
                        datetime, CalledNumber, direction, duration, tarif, cost, overinfo
                        )
                        SELECT tmpA.* FROM
                        -- Некоторые операторы связывают тарификацию с детализацией по мин. 
                        -- складываем одинаковые звонки 
                        -- !!!в течении минуты 
                            (SELECT 
                                tmp.datetime,  
                                tmp.CalledNumber,  
                                tmp.direction,
                                SUM(tmp.duration) AS duration,  
                                tmp.tarif,  
                                SUM(tmp.cost) AS cost,  
                                tmp.overinfo
                            FROM ' . TABLE_TBX_MEMORY . ' AS tmp
                        GROUP BY CONCAT(tmp.datetime,CalledNumber) ) AS tmpA
                        LEFT JOIN
                        -- выкидываем записи которые уже есть в базе, исключая дубливоание
                        ' . TABLE_TBX . ' main ON 
                            tmpA.datetime=main.datetime AND 
                            tmpA.CalledNumber=main.CalledNumber AND 
                            tmpA.duration=main.duration
                        WHERE main.id IS NULL');

            if ($act->execute()) {
                $this->flash->success("Импорт выполнен успешно");
            } else
                throw new Exception(implode(', ', $act->getMessages()));
            $act = $this->db->query('TRUNCATE ' . TABLE_TBX_MEMORY . '');
            if ($act->execute()) {
                $this->flash->success("Временная база отчищена..");
            }

        } catch (Exception $ex) {
            $this->flash->error("Возникла ошибка (" . $ex->getMessage() . ') ');
        }
        $this->response->redirect($this->url->get($this->dispatcher->getControllerName() . '/ImportForm'));
        return '';
    }


    public function SendImportToMainAction()
    {

    }

    public function UsersAction()
    {
        $this->view->setVar('ListUser', tableSystemUsers::find());

    }

    public function UserDelAction($id)
    {
        if ((int)$id > 0) {
            $usr = tableSystemUsers::findFirst($id);
            $usr->delete();
        }
        $this->response->redirect('Config/users/');
    }

    public function UserUpdatePasswordAction()
    {
        try {
            $usrID = $this->request->getPost('userId', Filter::FILTER_INT, null);
            $pwdStr = $this->request->getPost('NewPwd', Filter::FILTER_STRING, null);
            if ($this->request->isAjax() &&
                $this->request->isPost() &&
                empty($usrID) &&
                empty($pwdStr)
            )
                throw new Exception('Ошибочные данные');
            $usr = tableSystemUsers::findFirstById($usrID);
            if ($usr) {
                $usr->setNewPassword($pwdStr);
                $usr->save();
            } else throw new Exception('Пользователь не найден ' . $usrID . ' ');
        } catch (Exception $ex) {
            $this->response->setStatusCode(501);
            echo $ex->getMessage();
        }
        return false;
    }

    /**
     * Запихиваем во временную базу текущий файл
     * @param ParsingPBXBilling $parsingPBXBilling ссылка на ParsingPBXBilling
     * @param bool $clearTable Выполнить передварительно TRUNCATE
     * @return array [Добавленно строк, всего строк в файле]
     * @throws Exception
     */
    private function ImportPBXDataToTempDB(ParsingPBXBilling &$parsingPBXBilling, $clearTable = true)
    {
        /**
         * @var $FileData \App\PBX\RowPBXModel[]
         */
        $FileData = null;
        if (isset($parsingPBXBilling))
            $FileData = $parsingPBXBilling->getData();
        if (count($FileData) == 0)
            throw new Exception("Файл для импотра пустой или не соответсвтует шаблону импорта");
        $addRow = 0;
        if ($clearTable) // почистить базу truncate
            (new tablePBXMemory())->TruncateTable();
        $manager = new TxManager(); // создаем объект менеджер транзакций, для записи разом.
        $transaction = $manager->get();
        foreach ($FileData as $row) {
            $tablePBX = new tablePBXMemory();
            $tablePBX->setTransaction($transaction);
            $tablePBX->datetime = $row->datetime;
            $tablePBX->CalledNumber = $row->CalledNumber;
            $tablePBX->direction = ($row->direction == null) ? 0 : $row->direction;
            $tablePBX->duration = $row->duration;
            $tablePBX->tarif = $row->tarif;
            $tablePBX->cost = $row->cost;
            $tablePBX->overinfo = $row->overinfo;
            if (!$tablePBX->save()) {
                throw new Exception("Ошибка записи в базу, строка: " . $addRow . ' (' .
                    implode(', ', $tablePBX->getMessages()) . ') ' . ' Поля: (' . implode(', ', $tablePBX->toArray()) . ')');
            }
            $addRow++;
        }
        $transaction->commit(); // Выполняем транзакции
        $countRows = $FileData->countRows;
        unset($FileData);
        return array($addRow, $countRows);
    }


}