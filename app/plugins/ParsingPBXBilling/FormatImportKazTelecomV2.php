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
            case 2:
                $ret = $this->ImportTab_2($param);
                break;
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
        if (empty($param[3]) || $param[3] == "НЕТ")
            return null;
        //Проверяем формат полей на соответствие шаблону, в противном случае пропускаем
        if ($this->isEmptyArray($param, [0, 1, 2, 4, 5, 6]) ||
            !preg_match('/^\d{6,10}$/', $param[0]) || // первый стоолбец не номер тел. исх
            !$this->isDate($param[1]) ||
            !$this->isTime($param[2]) ||
            !preg_match('/^\d+$/', $param[4]) || //Телефон
            !preg_match('/^\d+$/', $param[5]) || //Телефон
            !$this->isTime($param[6]) || //Длит.(мин:сек)
            !$this->isDecimal($param[7]) //Сумма (тг.)
        )
            return null;
        $pbx = new RowPBXModel();
        $pbx->datetime = $this->DateToTimestamp($param[1]) +
            $this->TimeToTimestamp($param[2]);
        $direction = array();// для сбора доп информации для дебага
        $pbx->CalledNumber = '7' . $param[4] . $param[5];
        $direction['id'] = $param[4];
        $direction['CityPBX'] = $param[3];
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
        if ($this->isEmptyArray($param, [0, 1, 2, 5, 6, 7]) ||
            !preg_match('/^\d{6,10}$/', $param[0]) || // первый стоолбец не номер тел. исх
            !$this->isDate($param[1]) ||
            !$this->isTime($param[2]) ||
            !preg_match('/^\d+$/', $param[5]) || //Телефон
            !$this->isTime($param[6]) || //Длит.(мин:сек)
            !$this->isDecimal($param[7]) //Сумма (тг.)
        )
            return null;
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
        )
            return null;
        $pbx = new RowPBXModel();
        $pbx->datetime = $this->TimeToTimestamp($param[1]);
        $direction = array();// для сбора доп информации для дебага
        $pbx->CalledNumber = $param[3];
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
        return [0, 1, 2];
    }

    private function DateToTimestamp($val): int
    {
        if (is_int($val))
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($val, 'UTC');

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

    private function isFullDateTimeStr($val): bool{
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