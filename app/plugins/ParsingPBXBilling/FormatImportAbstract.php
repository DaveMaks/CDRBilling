<?php
/**
 * Created by PhpStorm.
 * User: vanuykov.m
 * Date: 08.12.2020
 * Time: 21:35
 */

namespace App\PBX;

use \Exception;

abstract class FormatImportAbstract implements \Iterator, \Countable
{
    private $table = array();
    public $countRows = 0;
    public $SumCost=0;
    public $countSkipRows = 0;
    public $curentPage = 0;
    private $_maxRow=null;

    public function __construct($inputFileName, $maxRow = null)
    {
        $this->_maxRow=$maxRow;
        if (!is_file($inputFileName)) {
            throw new Exception('Файл "' . $inputFileName . '" не существует! ');
        }

        if (!is_readable($inputFileName)) {
            throw new Exception('Не возможно открыть файл "' . $inputFileName . '". ');
        }

        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($inputFileName);
        /**  Create a new Reader of the type that has been identified  **/
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);
        $indexPage = $this->getIndexPage();
        if ($inputFileType == 'Csv') // по умолчанию, при экспорте из xls сохратяется в win-1252
            $reader->setInputEncoding('CP1252');
        $spreadsheet = $reader->load($inputFileName);
        $indexPage = (is_array($indexPage) && count($indexPage) > 0) ? $indexPage : array($indexPage);
        foreach ($indexPage as $currentPage) {
            $this->curentPage = $currentPage;
            $this->ParsingSheet($spreadsheet, $currentPage);
        }
        unset($spreadsheet);
        unset($reader);
    }

    private function ParsingSheet(&$spreadsheet, $indexPage)
    {
        $indexPage = ($spreadsheet->getSheetCount() - 1 >= $indexPage) ? $indexPage : 0; // если не существует листа
        $data = $spreadsheet->setActiveSheetIndex($indexPage)->toArray("", false, false);
        if (is_array($data) && !empty($data)) {
            $i = 0;
            foreach ($data as $row) {
                $obj = $this->ParsingRowToModel($row);
                $this->countRows++;
                if ($this->_maxRow != null && $i++ > $this->_maxRow)
                    break;
                if (empty($obj)) {
                    $this->countSkipRows++;
                    continue;
                }
                $this->table[] = $obj;
            }
        }
        unset($data);
    }

    public function SaveErrorLog($message){
        $error=new \tablePBXError();
        $error->message='Лист:'.$this->curentPage.' Строка:'.$this->countRows.' '.$message;
        $error->save();
    }

    /** Проверка определенных элементов на пустые значения
     * @param array $arr ссылка на проверяемы массив
     * @param array $key массив индексов для проверки
     * @return boolean
     */
    public function isEmptyArray(&$arr = array(), $key = array()): bool
    {
        foreach ($key as $k) {
            if (empty($arr[$k])) return true;
        }
        return false;
    }

    abstract protected function ParsingRowToModel($arrayRow = array()): ?RowPBXModel;

    abstract protected function getIndexPage();

    public function current()
    {
        return current($this->table);
    }

    public function next()
    {
        next($this->table);
    }

    public function key()
    {
        return key($this->table);
    }

    public function valid()
    {
        $key = key($this->table);
        return ($key !== NULL && $key !== FALSE);
    }

    public function rewind()
    {
        reset($this->table);
    }

    public function count()
    {
        return count($this->table);
    }
}