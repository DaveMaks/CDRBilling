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
    public $countSkipRows = 0;

    public function __construct($inputFileName, $maxRow = null)
    {
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
        $indexPage = ($spreadsheet->getSheetCount() - 1 >= $indexPage) ? $indexPage : 0; // если не существует листа
        $data = $spreadsheet->setActiveSheetIndex($indexPage)->toArray("", false, false);
        unset($spreadsheet);
        unset($reader);
        if (is_array($data) && !empty($data)) {
            $i = 0;
            foreach ($data as $row) {
                $obj = $this->ParsingRowToModel($row);
                $this->countRows++;
                if ($maxRow != null && $i++ > $maxRow)
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

    abstract protected function ParsingRowToModel($arrayRow = array()): ?RowPBXModel;

    abstract protected function getIndexPage(): ?int;

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