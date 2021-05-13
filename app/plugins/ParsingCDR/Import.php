<?php
/**
 * Created by PhpStorm.
 * User: vanuykov.m
 * Date: 13.12.2020
 * Time: 21:32
 */

namespace App\CDR;

use Phalcon\Di;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
use \Exception;
use Phalcon\Mvc\Model\ManagerInterface;
use Phalcon\Mvc\Model\Transaction\Failed;


class Import
{

    /**
     * @var $_dependencyInjector Di
     */
    private $_dependencyInjector;
    private $_transaction;

    private $_currentRow = 0;
    private $_currentFile = "";

    private $_folderLog = '/tmp/';
    private $_logger = null;
    private $_maskReplacePartyNumber = array();
    private $_valueReplacePartyNumber = array();
    private $config;
    private $_useTransaction = true;


    public function __construct(DiInterface $dependencyInjector = null)
    {
        if (!is_object($dependencyInjector))
            $this->_dependencyInjector = Di::getDefault();
        else
            $this->_dependencyInjector = $dependencyInjector;

        if (isset($this->_dependencyInjector->getShared("config")->importcdr->folderLog))
            $this->_folderLog = $this->_dependencyInjector->getShared("config")->importcdr->folderLog;

        $this->config = $this->_dependencyInjector->getShared("config");

        $adapter = new Stream($this->_folderLog . 'ImportCDR_' . date("Ymd", time()) . '.log');
        $this->_logger = new Logger(
            'messages',
            [
                'main' => $adapter,
            ]
        );
        $this->_maskReplacePartyNumber = $this->config->importcdr->maskReplacePartyNumber->toArray();
        $this->_valueReplacePartyNumber = $this->config->importcdr->valueReplacePartyNumber->toArray();

    }

    function Load($inputFileName)
    {
        $_state = false;
        try {
            $f = explode('/', $inputFileName);
            $f = array_reverse($f);
            $this->_currentFile = $f[0];
            unset($f);
            $this->_currentRow = 0;
            if (defined('DEBUG') && DEBUG)
                $this->_logger->info("Старт обработки файла $this->_currentFile");
            $handle = $this->ReadFile($inputFileName);
            if ($this->_useTransaction) {
                $manager = new TxManager(); // создаем объект менеджер транзакций, для записи разом.
                $this->_transaction = $manager->get();
            } else
                unset($this->_transaction);
            //$columns = array();
            $countAllowRows = 0;
            while (!feof($handle)) {
                $this->_currentRow++;
                $buffer = fgets($handle, 4096);
                $columns = explode(',', $buffer);//$content[$i]);
                if (!$this->validRow($columns)) {
                    if (defined('DEBUG') && DEBUG)
                        $this->_logger->error("Ошибка валидации формата строки $this->_currentRow , длина строки (" . strlen($buffer) . ")");
                    continue;
                }
                if (!$this->updateRow($columns)) {
                    if (defined('DEBUG') && DEBUG)
                        $this->_logger->error("Ошибка обновления полей строки $this->_currentRow");
                    continue;
                }
                if (!$this->SaveToDB($columns)) {
                    if (defined('DEBUG') && DEBUG)
                        $this->_logger->error("Ошибка сохранения полей строки $this->_currentRow");
                    continue;
                }
                $countAllowRows++;
                if ($this->_useTransaction)
                    if ($this->_currentRow % 100 == 0) {
                        $this->_transaction->commit();
                        $this->_transaction = $manager->get();
                    }
            }
            if ($this->_useTransaction)
                $this->_transaction->commit();

            $this->MoveRowsFromTempTable();
            $_state = true;
        } catch (Exception $ex) {
            $this->_logger->error($this->_currentFile . ' ' . $ex->getMessage());
        }
        $ImportStatistics = new tableCDRImportStatistics();
        $ImportStatistics->filename = $this->_currentFile;
        $ImportStatistics->countRows = $this->_currentRow;
        $ImportStatistics->skipRows = $this->_currentRow - $countAllowRows;
        $ImportStatistics->status = $_state;
        $ImportStatistics->save();
        @fclose($handle);
        return $_state;
    }

    /**
     * переносим строки из временной таблицы в основную
     */
    private function MoveRowsFromTempTable()
    {
        /**
         * @var $db \Phalcon\Db\Adapter\Pdo\AbstractPdo
         */
        // выполняем сложный запрос переноса данных из временной таблицы в основную через Pdo
        $db = $this->_dependencyInjector->getShared("db");
        $sql = 'INSERT INTO ' . TABLE_CDR_LOGS . ' (pkid,
                globalcallid_callmanagerid, 
                globalcallid_callid, 
                origlegcallidentifier, 
                datetimeorigination, 
                origipaddr, 
                callingpartynumber, 
                originalcalledpartynumber, 
                finalcalledpartynumber, 
                datetimeconnect,
                datetimedisconnect, 
                lastredirectdn, duration, outpulsedcallingpartynumber, outpulsedcalledpartynumber, origdevicename, 
                destdevicename, filename, coment,id_PhoneCode)
                  ( SELECT
                        tmp.pkid,
                        tmp.globalcallid_callmanagerid,
                        tmp.globalcallid_callid,
                        tmp.origlegcallidentifier,
                        tmp.datetimeorigination,
                        tmp.origipaddr,
                        tmp.callingpartynumber,
                        tmp.originalcalledpartynumber,
                        tmp.finalcalledpartynumber,
                        tmp.datetimeconnect,
                        tmp.datetimedisconnect,
                        tmp.lastredirectdn,
                        tmp.duration,
                        tmp.outpulsedcallingpartynumber,
                        tmp.outpulsedcalledpartynumber,
                        tmp.origdevicename,
                        tmp.destdevicename,
                        tmp.filename,
                        tmp.coment,
                        IF (LENGTH(tmp.finalcalledpartynumber) < 8,null, 
                        (SELECT pc.id FROM '.TABLE_PHONE_CODE.' as pc
                          WHERE tmp.finalcalledpartynumber REGEXP CONCAT(\'^\', pc.code, \'\')
                          ORDER BY LENGTH(pc.code) DESC
                          LIMIT 1))
                  FROM  (SELECT * FROM ' . TABLE_CDR_CACHE_LOGS . ' tc
                    WHERE tc.filename = :FILENAME) AS tmp
                    LEFT JOIN ' . TABLE_CDR_LOGS . ' AS cdr
                      ON tmp.pkid = cdr.pkid
                      AND tmp.datetimeconnect = cdr.datetimeconnect
                      AND tmp.finalcalledpartynumber = cdr.finalcalledpartynumber
                      AND tmp.duration = cdr.duration
                  WHERE cdr.pkid IS NULL)';
        $ret = false;
        try {
            $db->prepare($sql)->execute(['FILENAME' => $this->_currentFile]);
        } catch (Exception $ex) {
            throw $ex;
        } finally {
            $db->prepare('DELETE FROM ' . TABLE_CDR_CACHE_LOGS . '  WHERE filename=:FILENAME')->execute(['FILENAME' => $this->_currentFile]);
        }
        return true;
    }

    private function ReadFile($inputFileName)
    {
        if (!is_file($inputFileName)) {
            throw new Exception('Файл "' . $inputFileName . '" не существует! ');
        }
        if (!is_readable($inputFileName)) {
            throw new Exception('Не возможно открыть файл "' . $inputFileName . '". ');
        }
        $handle = @fopen($inputFileName, "r");
        return $handle;
    }

    /**
     * выполянем проверку корректности полей колличество и формат
     */
    private function validRow(&$row): bool
    {
        $columnsNotNULL = array(4, 7, 8, 29, 30, 47, 55); //номер столбцов в файле cdr, обязательные для записи
        try {
	    //echo count($row).' - ';
            if ((count($row) != 94) && (count($row) != 123) && (count($row) != 129))  // если в строке не 94 поля для версии cucm =7 и 123 поля для версии 9
                throw  new Exception('Не верное колл. полей (пропущено) ' . $this->_currentRow . ' ' . $this->_currentFile);

            if ((int)$row[55] <= 0) { // пропускаем звонки с 0 продолжительностью
                throw  new Exception('Нулевая длительность (пропущено) ' . $this->_currentRow . ' ' . $this->_currentFile);
            }
            foreach ($columnsNotNULL as $a) { // отбрасываем строки с пустыми необходимыми полями
                if ($row[$a] == '' || $row[$a] == '""') {
                    throw  new Exception('Пустое поле (пропущено) ' . $this->_currentRow . ' ' . $this->_currentFile);
                }
            }
        } catch (Exception $ex) {
            if (defined('DEBUG') && DEBUG)
                $this->_logger->warning($ex->getMessage());
            return false;
        }
        return true;
    }

    /**
     * выполняем необходимые видоизменения в полях строки
     */
    private function updateRow(&$row): bool
    {
        $columnsTrim = [50, 8, 29, 30, 56, 57];
        foreach ($columnsTrim as $a) {
            $row[$a] = trim($row[$a], '"');
        }
        $info = array();
        // удаляем ведущие коды набора (08 0810) и тп на основе конфига
        $tmp = $this->UpdatePartyNumber($row[29]);
        if ($tmp != $row[29])
            $info['originalcalledpartynumber'] = $row[29];
        $row[29] = $tmp;

        $tmp = $this->UpdatePartyNumber($row[30]);
        if ($tmp != $row[30])
            $info['finalcalledpartynumber'] = $row[30];
        $row[30] = $tmp;
        $row['debug'] = $info;

        return true;
    }

    function SaveToDB(&$row)
    {
        $cdr = new tableCDRMemory();
        if (isset($this->_transaction))
            $cdr->setTransaction($this->_transaction);
        $cdr->pkid = $row[50];
        $cdr->globalcallid_callmanagerid = (int)$row[1];
        $cdr->globalcallid_callid = (int)$row[2];
        $cdr->origlegcallidentifier = (int)$row[3];
        $cdr->datetimeorigination = (int)$row[4];
        $cdr->origipaddr = (int)$row[7];
        $cdr->callingpartynumber = $row[8];
        $cdr->originalcalledpartynumber = $row[29];
        $cdr->finalcalledpartynumber = $row[30];
        $cdr->datetimeconnect = (int)$row[47];
        $cdr->datetimedisconnect = (int)$row[48];
        $cdr->lastredirectdn = (int)$row[49];
        $cdr->duration = (int)$row[55];
        $cdr->outpulsedcallingpartynumber = (int)$row[78];
        $cdr->outpulsedcalledpartynumber = (int)$row[79];
        $cdr->origdevicename = $row[56];
        $cdr->destdevicename = $row[57];
        $cdr->coment = (!empty($row['debug'])) ? json_encode($row['debug']) : null;
        $cdr->filename = $this->_currentFile;//$f[0];
        $success = $cdr->save();
        if ($success === false) {
            $this->_logger->error("Ошибка выполнения сохранения в базу", $cdr->getMessages());
            return false;
        }
        return true;
    }

    /**
     * обновление вызываемых номеров исключаеяя формат набора 08/0810 итп на основе маски  config->importcdr,
     * @param $PartyNumber Номер телефона
     * @param $info исходный вариант если изменен для дебага
     * @return возвращает измененный номер
     */
    private function UpdatePartyNumber($PartyNumber)
    {
        if (strlen($PartyNumber) > $this->config->importcdr->countCharExtension) {
            $PartyNumber = preg_replace(
                $this->_maskReplacePartyNumber,
                $this->_valueReplacePartyNumber,
                $PartyNumber,
                1);
        }
        return $PartyNumber;
    }


}