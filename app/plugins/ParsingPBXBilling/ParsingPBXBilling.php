<?php
/**
 * Created by PhpStorm.
 * User: vanuykov.m
 * Date: 09.12.2020
 * Time: 16:57
 */

namespace App\PBX;

use \Exception;

class ParsingPBXBilling
{
    const TypeKcell = 'Kcell';
    const TypeKaztelecom = 'Kaztelecom';

    public static $listType = [
        self::TypeKcell,
        self::TypeKaztelecom
    ];

    private $filePath = null;
    private $dataclass = null;

    function __construct($FilePathToImport, $from = null)
    {
        $this->filePath = $FilePathToImport;
        switch ($from) {
            case self::TypeKcell:
                $this->dataclass = FormatImportKcell::class;
                break;
            case self::TypeKaztelecom:
                $this->dataclass = FormatImportKazTelecom::class;
                break;
        }

    }

    /**
     * Плучаем массив RowPBXModel;
     */
    public function getData()
    {
        if (!isset($this->dataclass)) {
            throw new Exception("Не зарегистрированниый класс");
        }
        $className = $this->dataclass;
        return new $className($this->filePath);
    }
}