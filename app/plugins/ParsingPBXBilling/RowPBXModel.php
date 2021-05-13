<?php
/**
 * User: vanuykov.m
 * Date: 08.12.2020
 * Time: 21:40
 */
namespace App\PBX;

/**
 * Class RowPBXModel
 * Описывает модель строк из файлов импорта
 * @package App\PBX
 */
class RowPBXModel
{
    /**
     * @var дата время вызова в формате unixtime
     */
    var $datetime;
    /**
     * @var Номер Вызываемого Абонента
     */
    var $CalledNumber;
    /**
     * @var Направление
     */
    var $direction;
    /**
     * @var Длительность в сек
     */
    var $duration;
    /**
     * @var Тариф
     */
    var $tarif;
    /**
     * @var Сумма
     */
    var $cost;
    /**
     * @var Дополнительная информация ввиде json для дебага.
     */
    var $overinfo;
}