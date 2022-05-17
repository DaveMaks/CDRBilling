<?php
/**
 * User: vanuykov.m
 * Date: 08.4.2022
 * Time: 21:40
 */

namespace App\PBX;

class FormatImportBeelinev2 extends FormatImportAbstract
{
    var $countSkipRows = 2;
    // var $_maxRow=10;

    /**
     * Лицевой счет
     * Направление звонка
     * Дата, время
     * Длительность, сек
     * Стоимость, тенге
     * Набранный номер
     * Номер звонящего
     * Страна оператор
     * Оператор
     */
    protected function ParsingRowToModel($param = array()): ?RowPBXModel
    {
        /*if ($this->countRows > 10)
            return null;*/

        if ($param[1] != 'Исходящий звонок')
            return null;

        if (!preg_match('/^\d+$/', $param[3])) { //
            $this->SaveErrorLog('Ошибка проверки поля Длительность (3) (^\d+$):' .
                print_r($param, true));
            return null;
        }
        if (!preg_match('/^\d+\.\d+$/', $param[4])) { //
            $this->SaveErrorLog('Ошибка проверки поля Стоимость (4) (^\d+\.\d+$):' .
                print_r($param, true));
            return null;
        }

        if (!preg_match('/^\d{6,12}$/', $param[5])) { //
            $this->SaveErrorLog('Ошибка проверки поля Набранный номер (5) (^\d{6,12}$):' .
                print_r($param, true));
            return null;
        }
        if (!is_float($param[2])) {
            $this->SaveErrorLog('Ошибка проверки поля даты и времени (2) (^\d{6,12}$):' .
                print_r($param, true));
            return null;
        }

        $param[2] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($param[2], 'Asia/Almaty');

        if (mb_substr($param[5], 0, 1) == '7') {
            $param[5] = '7' . $param[5];
        }

        $pbx = new RowPBXModel();
        $pbx->datetime =$param[2]; // $date->getTimestamp();
        $pbx->CalledNumber = $param[5];
        $pbx->duration = (int)$param[3];
        $pbx->cost = (float)$param[4];
        $pbx->tarif = round(($pbx->cost / $pbx->duration), 2);
        $pbx->overinfo = json_encode([$param[5], $param[6]]);
        return $pbx;
    }

    protected function getIndexPage(): ?int
    {
        return 0;
    }
}