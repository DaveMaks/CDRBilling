<?php
/**
 * Created by PhpStorm.
 * User: vanuykov.m
 * Date: 08.12.2020
 * Time: 21:35
 */

namespace App\CDR;

use \Exception;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
use Phalcon\Di;

abstract class FormatCDRAbstract
{
    public $countRows = 0;
    public $countSkipRows = 0;
    public $logger = null;
    private $currentFile = "";
    public $callbackSave = null;

    protected $_dependencyInjector;

    public function __construct(DiInterface $dependencyInjector = null)
    {
        if (!is_object($dependencyInjector)) {
            $this->_dependencyInjector = Di::getDefault();
        }
        $this->_dependencyInjector = $dependencyInjector;
    }

    protected function log($message, $id_row)
    {
        echo $this->currentFile . ' ' . $id_row . ' ' . $message;
    }

    function Load($inputFileName)
    {
        $this->currentFile = $inputFileName;
        $handle = $this->ReadFile($inputFileName);
        //$manager = new TxManager(); // создаем объект менеджер транзакций, для записи разом.
        //$transaction = $manager->get();
        $i = 0;
        while (!feof($handle)) {
            $buffer = fgets($handle, 4096);
            $colums = explode(',', $buffer);//$content[$i]);
            if (!$this->validRow($colums)) {
                $this->log("Ошибка валидации формата строки;", $i);
                continue;
            }
            if (!$this->updateRow($colums)) {
                $this->log("Ошибка обновления полей строки;", $i);
                continue;
            }
            if (!is_null($this->callbackSave))
                if (!$this->callbackSave($colums)) {
                    $this->log("Ошибка обновления полей строки;", $i);
                    continue;
                }
            /*if (is_($this->save)){

            }*/
            $i++;
        }
        @fclose($handle);
        //$transaction->commit();
        return true;
    }

    function ReadFile($inputFileName)
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

    //abstract protected function ParsingRowToModel($arrayRow = array()):?tableCDRMemory;

    /**
     * выполянем проверку корректности полей колличество и формат
     */
    abstract protected function validRow(&$row): bool;

    /**
     * выполняем необходимые видоизменения в полях строки
     */
    abstract protected function updateRow(&$row): bool;


}