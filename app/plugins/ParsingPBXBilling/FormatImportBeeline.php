<?php
/**
 * User: vanuykov.m
 * Date: 08.4.2022
 * Time: 21:40
 */

namespace App\PBX;

class FormatImportBeeline extends FormatImportAbstract
{
  var $countSkipRows = 6;
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
    /* if ($this->countRows>20)
         return null;*/

    if ($param[1] != 'ИСХОДЯЩИЙ ЗВОНОК')
      return null;

    if (!preg_match('/^\d{6,12}$/', $param[7])) {// первый стоолбец не номер тел. исх
      $this->SaveErrorLog('Ошибка проверки поля тел. назначения(0) (^\d{6,12}$):' .
        print_r($param, true));
      return null;
    }
    if (!preg_match('/^\d+$/', $param[4])) {//Длит.(мин:сек)
      $this->SaveErrorLog('Ошибка проверки поля Длительность(6) (^^\d+$):' .
        print_r($param, true));
      return null;
    }
    if (!preg_match('/^(\d+|\d+\.\d{1,2})$/', $param[6])) {//Сумма (тг.)
      $this->SaveErrorLog('Ошибка проверки поля Сумма(7) (^(\d+|\d+,\d{1,2})$):' .
        print_r($param, true));
      return null;
    }
    $date = $param[2] . ' ' . $param[3];
    if (!preg_match('/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}:\d{2}$/', $date)) {//Сумма (тг.)
      $this->SaveErrorLog('Ошибка проверки поля Даты(2) или время(3) (^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}:\d{2}$):' .
        print_r($param, true));
      return null;
    }
    $date = date_create_from_format('d.m.Y H:i:s', $date);
    if (mb_substr($param[7], 0, 1) == '7') {
      $param[7]='7'.$param[7];
    }
    $pbx = new RowPBXModel();
    $pbx->datetime = $date->getTimestamp();
    $pbx->CalledNumber = $param[7];
    $pbx->duration = (int)$param[4];
    $pbx->cost = (float)$param[6];
    $pbx->tarif = round(($pbx->cost / $pbx->duration), 2);
    $pbx->overinfo = json_encode([$param[9], $param[10]]);

    return $pbx;
  }

  protected function getIndexPage(): ?int
  {
    return 0;
  }
}