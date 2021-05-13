<?php
/**
 * User: vanuykov.m
 * Date: 08.12.2020
 * Time: 21:40
 */

namespace App\PBX;

use App\HTML\HelperPlugin;

class FormatImportKazTelecom extends FormatImportAbstract
{
    protected function ParsingRowToModel($param = array()): ?RowPBXModel
    {
        if (empty($param[0]) ||
            empty($param[1]) ||
            empty($param[2]) ||
            //empty($param[3]) ||
            empty($param[7]) ||
            empty($param[5]) ||
            empty($param[6]) ||
            empty($param[7]) ||
            !preg_match('/^\d{6,10}$/', $param[0]) ||
            !(preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $param[1])
                || is_int($param[1])) || //вариант когда поле типизировано в Excel возвращает число
            !(preg_match('/^\d{2}:\d{2}$/', $param[2])
                || is_float($param[1])) || //вариант когда поле типизировано в Excel возвращает время ввиде числа с плавоющей точкой
            !preg_match('/^\d+$/', $param[5]) || //Телефон
            !preg_match('/^\d{2}:\d{2}$/', $param[6]) || //Длит.(мин:сек)
            !preg_match('/^\d*[\.,]?\d*$/', $param[7]) //Сумма (тг.)
        ) {
            return null;
        }

        if (is_int($param[1]))
            $param[1] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($param[1], 'UTC');
        else {
            $dateRow = explode('.', $param[1]);
            $param[1] = mktime(0, 0, 0, (int)$dateRow[1], (int)$dateRow[0], (int)('20' . $dateRow[2]));
        }
        if (is_float($param[2])) {
            $param[2] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($param[2]);
        } else {
            $timeRow = explode(':', $param[2]);
            $param[2] = ((int)$timeRow[0] * 3600) + ((int)$timeRow[1] * 60);
        }

        $pbx = new RowPBXModel();
        $pbx->datetime = $param[1] + $param[2]; // получили дату время в unixtime

        $direction = array();// для сбора доп информации для дебага

        $pbx->CalledNumber=$param[5];
        if (preg_match('/^\d+$/', $param[4]))
            $pbx->CalledNumber = '7'.$param[4] . $param[5];
        /*if (preg_match('/^D\d+$/', $param[4]))
            $pbx->CalledNumber = preg_replace('/^7{1}/', "", $param[5], 1);// убераем начальную 7 приводя к формату (код)(помер)*/
        if (preg_match('/^710\d+$/', $pbx->CalledNumber)){
            $pbx->CalledNumber = preg_replace('/^710{1}/', "", $pbx->CalledNumber, 1);// убераем начальную 7 приводя к формату (код)(помер)*/
        }

        $direction['id'] = $param[4];

        // переводим длительность в минуты
        if (is_float($param[6])) {
            $param[6] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($param[6]);
        } else {
            $timeRow = explode(':', $param[6]);
            $param[6] = ((int)$timeRow[0] * 60) + (int)$timeRow[1];
        }
        $pbx->duration = (int)$param[6];
        $pbx->cost = (float)str_replace(',','.',$param[7]);
        $pbx->tarif = round(($pbx->cost / $pbx->duration) * 60, 0);
        $pbx->overinfo = json_encode($direction);
        return $pbx;
    }

    protected function getIndexPage(): ?int
    {
        return 1;
    }


}