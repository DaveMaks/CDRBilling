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
    const TypeKaztelecomV2 = 'Kaztelecom_Ver2';
    const TypeKaztelecomCity = 'Kaztelecom_City';
    const TypeBeeline = 'Beeline';
    const TypeBeelineV2 = 'Beeline ver2';

    public static $listType = [
        self::TypeKcell,
        self::TypeKaztelecom,
        self::TypeKaztelecomV2,
        self::TypeKaztelecomCity,
        self::TypeBeeline,
        self::TypeBeelineV2
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
            case self::TypeKaztelecomV2:
                $this->dataclass = FormatImportKazTelecomV2::class;
                break;
            case self::TypeKaztelecomCity:
                $this->dataclass = FormatImportKazTelecomCity::class;
                break;
            case self::TypeBeeline:
                $this->dataclass = FormatImportBeeline::class;
                break;
            case self::TypeBeelineV2:
                $this->dataclass = FormatImportBeelinev2::class;
                break;
        }
    }

    /**
     * Плучаем массив RowPBXModel;
     * @return null|App\PBX\RowPBXModel[]
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