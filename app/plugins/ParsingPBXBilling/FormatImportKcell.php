<?php
/**
 * User: vanuykov.m
 * Date: 08.12.2020
 * Time: 21:40
 */

namespace App\PBX;

class FormatImportKcell extends FormatImportAbstract
{
    protected function ParsingRowToModel($param = array()): ?RowPBXModel
    {
        if (empty($param[0]) ||
            empty($param[1]) ||
            empty($param[2]) ||
            empty($param[3]) ||
            !(preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $param[0])
                || is_int($param[0])) || //вариант когда поле типизировано в Excel возвращает число
            !(preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $param[1])
                || is_float($param[1])) || //вариант когда поле типизировано в Excel возвращает время ввиде числа с плавоющей точкой
            !preg_match('/^\d+$/', $param[4]) ||
            !preg_match('/^\d+$/', $param[5])
        ) {
            return null;
        }

        if (is_int($param[0]))
            $param[0] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($param[0],'UTC');
        else {
            $dateRow = explode('.', $param[0]);
            $param[0]=mktime(0,0,0,(int)$dateRow[1], (int)$dateRow[0], (int)$dateRow[2]);
        }
        if (is_float($param[1])) {
            $param[1] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($param[1]);
        } else {
            $timeRow = explode(':', $param[1]);
            $param[1]=((int)$timeRow[0]*3600)+((int)$timeRow[1]*60)+(int)$timeRow[2];
        }
        $dateTime = $param[0] + $param[1];

        $direction = array();
        $direction['id'] = (int)$param[3];
        /**
         * если в поле напраление есть текст 4(Россия), то выпосим его отдельно
         */
        $vals = null;
        if (strlen($param[3]) > 2) {
            preg_match('/^(\d)\((.*)\)/', $param[3], $vals);
            if (count($vals) == 3) {
                $direction['id'] = (int)$vals[1];
                $direction['country'] = $vals[2];
            }
        }
        $direction['CalledNumber'] = $param[4];
        // $param[4] = preg_replace('/^7{1}/', "", $param[4], 1);// убераем начальную 7 приводя к формату (код)(помер)
        $pbx = new RowPBXModel();
        $pbx->datetime = $dateTime;
        $pbx->CalledNumber = $param[4];
        $pbx->direction = $direction['id'];
        $pbx->duration = $param[5];
        $pbx->tarif = $param[6];
        $pbx->cost = $param[7];
        $pbx->overinfo = json_encode($direction);
        return $pbx;
    }

    protected function getIndexPage(): ?int
    {
        return 0;
    }
}