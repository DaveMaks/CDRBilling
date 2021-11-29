<?php
/**
 * User: vanuykov.m
 * Date: 08.12.2020
 * Time: 21:40
 */

namespace App\PBX;

use App\HTML\HelperPlugin;

class FormatImportKazTelecomV2 extends FormatImportAbstract
{


    /** 2ая вкладка сотов опер
     * Телефон
     * Дата
     * Время
     * Направление
     * Код
     * Телефон
     * Кол-во/Продолж.
     * Сумма (тг.)
     */

    /** 3ая вкладка город
     * Станции
     * Дата
     * Телефон
     * Набор
     * Кол-во/Продолж.
     * Сумма (тг.)
     */

    protected function ParsingRowToModel($param = array()): ?RowPBXModel
    {

        $ret = null;
        switch ($this->curentPage) {
            case 0:
                $ret = $this->ImportTab_0($param);
                break;
            case 1:
                $ret = $this->ImportTab_1($param);
                break;
            /* case 2:
                 $ret = $this->ImportTab_2($param);
                 break;*/
        }
        return $ret;
    }

    /** импорт строки из вкладки "Все услуги (VIP)_1"
     * @param array $param Ожидаются следующие поля по порядку:
     *  0  Телефон исходящий
     *  1  Дата
     *  2  Время
     *  3  Направление
     *  4  Код
     *  5  Телефон
     *  6  Кол-во/Продолж.
     *  7  Сумма (тг.)
     * @return RowPBXModel | null
     */
    private function ImportTab_0(&$param = array()): ?RowPBXModel
    {
        if (empty($param[3]) || $param[3] == "НЕТ") {
            $this->SaveErrorLog('Строка не соответстует данным:' .
                print_r($param, true));
            return null;
        }
        //Проверяем формат полей на соответствие шаблону, в противном случае пропускаем
        /*if ($this->isEmptyArray($param, [0, 1, 2, 4, 5, 6]) ||
            !preg_match('/^\d{6,10}$/', $param[0]) || // первый стоолбец не номер тел. исх
            !$this->isDate($param[1]) ||
            !$this->isTime($param[2]) ||
            !preg_match('/^\d+$/', $param[4]) || //Телефон
            !preg_match('/^\d+$/', $param[5]) || //Телефон
            !$this->isTime($param[6]) || //Длит.(мин:сек)
            !$this->isDecimal($param[7]) //Сумма (тг.)
        )
            return null;*/

        if ($this->isEmptyArray($param, [0, 1, 2, 4, 5, 6])) {
            $this->SaveErrorLog('Пустые столбцы 0, 1, 2, 4, 5, 6:' .
                print_r($param, true));
            return null;
        }
        if (!preg_match('/^\d{6,10}$/', $param[0])) {// первый стоолбец не номер тел. исх
            $this->SaveErrorLog('Ошибка проверки поля тел. исх(0) (^\d{6,10}$):' .
                print_r($param, true));
            return null;
        }
        if (!$this->isDate($param[1])) {
            $this->SaveErrorLog('Ошибка проверки поля Дата(1) (^\d{2}\.\d{2}\.\d{2}$):' .
                print_r($param, true));
            return null;
        }
        if (!$this->isTime($param[2])) {
            $this->SaveErrorLog('Ошибка проверки поля Время вызова(2) (^\d{2}\:\d{2}\$):' .
                print_r($param, true));
            return null;
        }
        if (!preg_match('/^\d+$/', $param[4])) {//Телефон
            $this->SaveErrorLog('Ошибка проверки поля Код(4) (^\d+\$):' .
                print_r($param, true));
            return null;
        }
        if (!preg_match('/^\d+$/', $param[5])) {//Телефон
            $this->SaveErrorLog('Ошибка проверки поля Телефон(5) (^\d+\$):' .
                print_r($param, true));
            return null;
        }
        if (!$this->isTime($param[6])) {//Длит.(мин:сек)
            $this->SaveErrorLog('Ошибка проверки поля Длительность(6) (^\d{2}:\d{2}$):' .
                print_r($param, true));
            return null;
        }
        if (!$this->isDecimal($param[7])) {//Сумма (тг.)
            $this->SaveErrorLog('Ошибка проверки поля Сумма(7) (^\d*[\.,]?\d*$):' .
                print_r($param, true));
            return null;
        }

        $pbx = new RowPBXModel();
        $pbx->datetime = $this->DateToTimestamp($param[1]) +
            $this->TimeToTimestamp($param[2]);
        $direction = array();// для сбора доп информации для дебага
        /*  if (substr($param[4],1,2)=='10')
              $pbx->CalledNumber = '7' . $param[4] . $param[5];
          else
              $pbx->CalledNumber = '7' . $param[4] . $param[5];*/
        $direction['id'] = $param[4];
        $direction['CityPBX'] = $param[3];

        if (mb_substr($param[4], 0, 2) == '10') {
            $param[4] = mb_substr($param[4], 2);
            $pbx->CalledNumber = $param[4] . $param[5];
            $direction['far_abroad'] = $param[4];
        } else
            $pbx->CalledNumber = '7' . $param[4] . $param[5];
        $pbx->duration = $this->TimeToTimestamp($param[6], 'M');
        $pbx->cost = (float)str_replace(',', '.', $param[7]);
        $pbx->tarif = round(($pbx->cost / $pbx->duration) * 60, 0);
        $pbx->overinfo = json_encode($direction);
        return $pbx;
    }

    /** импорт строки из вкладки "сотов опер"
     * @param array $param Ожидаются следующие поля по порядку:
     * 0 Телефон
     * 1 Дата
     * 2 Время
     * 3 Направление
     * 4 Код
     * 5 Телефон
     * 6 Кол-во/Продолж.
     * 7 Сумма (тг.)
     * @return RowPBXModel | null
     */
    private function ImportTab_1(&$param = array()): ?RowPBXModel
    {
        //Проверяем формат полей на соответствие шаблону, в противном случае пропускаем
        // if ($this->isEmptyArray($param, [0, 1, 2, 5, 6, 7]) ||
        //     !preg_match('/^\d{6,10}$/', $param[0]) || // первый стоолбец не номер тел. исх
        //     !$this->isDate($param[1]) ||
        //     !$this->isTime($param[2]) ||
        //     !preg_match('/^\d+$/', $param[5]) || //Телефон
        //    !$this->isTime($param[6]) || //Длит.(мин:сек)
        //    !$this->isDecimal($param[7]) //Сумма (тг.)
        //)
        //    return null;

        if ($this->isEmptyArray($param, [0, 1, 2, 5, 6, 7])) {
            $this->SaveErrorLog('Пустые столбцы 0, 1, 2, 5, 6, 7:' .
                print_r($param, true));
            return null;
        }
        if (!preg_match('/^\d{6,10}$/', $param[0])) {// первый стоолбец не номер тел. исх
            $this->SaveErrorLog('Ошибка проверки поля тел. исх(0) (^\d{6,10}$):' .
                print_r($param, true));
            return null;
        }
        if (!$this->isDate($param[1])) {
            $this->SaveErrorLog('Ошибка проверки поля Дата(1) (^\d{2}\.\d{2}\.\d{2}$):' .
                print_r($param, true));
            return null;
        }
        if (!$this->isTime($param[2])) {
            $this->SaveErrorLog('Ошибка проверки поля Время вызова(2) (^\d{2}\:\d{2}\$):' .
                print_r($param, true));
            return null;
        }
        if (!preg_match('/^\d+$/', $param[5])) {//Телефон
            $this->SaveErrorLog('Ошибка проверки поля Телефон(5) (^\d+\$):' .
                print_r($param, true));
            return null;
        }
        if (!$this->isTime($param[6])) {//Длит.(мин:сек)
            $this->SaveErrorLog('Ошибка проверки поля Длительность(6) (^\d{2}:\d{2}$):' .
                print_r($param, true));
            return null;
        }
        if (!$this->isDecimal($param[7])) {//Сумма (тг.)
            $this->SaveErrorLog('Ошибка проверки поля Сумма(7) (^\d*[\.,]?\d*$):' .
                print_r($param, true));
            return null;
        }

        $pbx = new RowPBXModel();
        $pbx->datetime = $this->DateToTimestamp($param[1]) +
            $this->TimeToTimestamp($param[2], 'H');
        $direction = array();// для сбора доп информации для дебага
        $pbx->CalledNumber = $param[5];
        $direction['id'] = $param[4];
        $direction['CityPBX'] = $param[3];
        $pbx->duration = $this->TimeToTimestamp($param[6], 'M');
        $pbx->cost = (float)str_replace(',', '.', $param[7]);
        $pbx->tarif = round(($pbx->cost / $pbx->duration) * 60, 0);
        $pbx->overinfo = json_encode($direction);
        return $pbx;
    }

    /** импорт строки из вкладки "город"
     * @param array $param Ожидаются следующие поля по порядку:
     * 0 Станции
     * 1 Дата
     * 2 Телефон
     * 3 Набор
     * 4 Кол-во/Продолж.
     * 5 Сумма (тг.)
     * @return RowPBXModel | null
     * @deprecated Не используется, перенесена в отдльный файл
     */
    private function ImportTab_2(&$param = array()): ?RowPBXModel
    {
        //Проверяем формат полей на соответствие шаблону, в противном случае пропускаем
        if ($this->isEmptyArray($param, [1, 3, 4, 5]))
            return null;
        if (!$this->isFullDateTimeStr($param[1]) ||
            !preg_match('/^\d+$/', $param[3]) || //Телефон
            !$this->isTime($param[4]) || //Длит.(мин:сек)
            !$this->isDecimal($param[5]) //Сумма (тг.)
        ) {
            return null;
        }
        $pbx = new RowPBXModel();
        $pbx->datetime = $this->FullDateToTimestamp($param[1]);
        $direction = array();// для сбора доп информации для дебага
        $pbx->CalledNumber = '7' . $param[3];
        $direction['id'] = $param[0];
        $direction['CityPBX'] = 'ASTANA';
        $pbx->duration = $this->TimeToTimestamp($param[4], 'M');
        $pbx->cost = (float)str_replace(',', '.', $param[5]);
        $pbx->tarif = round(($pbx->cost / $pbx->duration) * 60, 0);
        $pbx->overinfo = json_encode($direction);
        return $pbx;
    }

    protected function getIndexPage(): ?array
    {
        return [0, 1];
    }

    private function DateToTimestamp($val): int
    {
        if (is_int($val))
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($val);
        $dateRow = explode('.', $val);
        return mktime(0, 0, 0, (int)$dateRow[1], (int)$dateRow[0], (int)('20' . $dateRow[2]));
    }


    /** Преобразовать время в формат UnixTime
     * @param $val
     * @param string $type Может принимать 2 знаения
     *  H-из часы:мин
     *  M-из мин:сек
     */
    private function TimeToTimestamp($val, string $type = 'H'): int
    {
        if (is_float($val))
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($val);
        $timeRow = explode(':', $val);
        return ((int)$timeRow[0] * (($type == 'H') ? 3600 : 1)) + ((int)$timeRow[1] * (($type == 'H') ? 60 : 1));
    }

    private function isDate($val): bool
    {
        return (preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $val) == 1 ||
            is_int($val));//вариант когда поле типизировано в Excel возвращает число
    }

    private function isFullDateTimeStr($val): bool
    {
        return (preg_match('/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}$/', $val) == 1
            || is_float($val));  //вариант когда поле типизировано в Excel возвращает время ввиде числа с плавоющей точкой
    }

    private function isTime($val): bool
    {
        return (preg_match('/^\d{2}:\d{2}$/', $val) == 1
            || is_float($val));  //вариант когда поле типизировано в Excel возвращает время ввиде числа с плавоющей точкой
    }

    private
    function isDecimal($val): bool
    {
        return (preg_match('/^\d*[\.,]?\d*$/', $val) == 1);
    }

}