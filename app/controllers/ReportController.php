<?php


use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\View;
use Phalcon\Di;
use Phalcon\Db\Result\Pdo;
use App\LocalClass\Files;
use App\HTML\HelperPlugin;
use App\LocalClass\viewCdrData;

//use App\XLS\SpreadsheetReader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\{
    Font, Border, Alignment
};
use Phalcon\Mvc\Model\ManagerInterface;

//use App\LDAP;

class ReportController extends Controller
{
    public function indexAction()
    {

    }


    //#####################################################################

    /** Вывод основной страницы отчета звонков в разрезе 1дня ввиде timeLine
     */
    public function TimeLineAction()
    {
        $this->assets->addCss('js/plugins/fancytree/skin-win8/ui.fancytree.css');
        $this->assets->addJs('js/plugins/fancytree/jquery.fancytree-all-deps.js');
        //$this->assets->addJs('js/ReportLib.js');
    }

    /*Формирует Timeline для сотрудников департамента */
    public function GetUserTimeLineAction($idUnit, $day = null)
    {
        if ($this->request->isAjax()) {
            $this->view->setRenderLevel(
                View::LEVEL_ACTION_VIEW
            );
        };
        if (empty($day) || $day < 99999)
            $day = time();
        $this->view->setVar('ListUser', tableUsers::getUsersByUnit((int)$idUnit, (int)$day));
    }

    /** вывод ajax орзанизационной струтуры, в разрезе департаментов
     */
    public function GetUnitsAction($id_parent = 0)
    {
        if ($this->request->isAjax() == true) {
            $this->view->disable();
            $this->response->setContentType('application/json', 'UTF-8');
            $this->response->setContent(json_encode($this->GetUnitsArray($id_parent)));
            return $this->response;
        }
    }

    /** Возвращает рекурсивный массив организационных едениц*/
    public function GetUnitsArray($id_parent = 0)
    {
        $units = tableUnits::find("id_parent=" . $id_parent);
        /**
         * @var $units tableUnits[]
         */
        $html = '';

        if ($units) {
            $json = array();
            $i = 0;
            foreach ($units as $unit) {
                $subhtml = $this->GetUnitsArray($unit->id);
                //$json[$i]['folder'] = true;
                if (!empty($subhtml)) {
                    $json[$i]['children'] = $subhtml;
                    $json[$i]['folder'] = true;
                }
                //$subhtml = '<ul id="">' . $subhtml . '</ul>';
                $json[$i]["title"] = (!empty($unit->Description)) ? $unit->Description : $unit->Name;
                $json[$i]["key"] = $unit->id;
                $json[$i++]["extraClasses"] = "ws-pre";
                //$html .= '<li data-key="' . $unit->id . '"  class="ws-pre">' . $unit->Name . $subhtml . '</li>';
            }
            return $json;
            //return '<ul id="">' .$html.'</ul>';
        }
        return "";
    }


    //#####################################################################

    /**
     * Вывод основной страницы отчета по стоимости звонка на сторудника
     */
    public function PBXAction()
    {
        $this->assets->addCss('js/plugins/select2/css/select2.min.css');
        $this->assets->addJs('js/plugins/select2/js/select2.full.min.js');
        //$this->assets->addCss('js/plugins/fancytree/skin-bootstrap/ui.fancytree.css');
        $this->assets->addCss('js/plugins/fancytree/skin-win8/ui.fancytree.css');

        $this->assets->addJs('js/plugins/fancytree/jquery.fancytree-all-deps.js');
        $this->assets->addJs('js/plugins/fancytree/modules/jquery.fancytree.gridnav.js');
        $this->assets->addJs('js/plugins/fancytree/modules/jquery.fancytree.table.js');

        $this->assets->addCss('js/plugins/DataTables/datatables.min.css');
        $this->assets->addJs('js/plugins/DataTables/datatables.min.js');
        $this->assets->addJs('js/ReportLib.js');

    }

    /**
     * Вывод ajax в формате json для формировния таблицы отчета о стоимости звонка ня сотрудника/департамент
     */
    public function GetCostPBXFromUnitAction($dateStart = null, $dateEnd = null, $isNullView = false)
    {
        if (empty($dateStart) || empty($dateEnd)) {
            return null;
        }
        $this->view->disable();
        $this->response->setContentType('application/json', 'UTF-8');

        /// проверка на слишком большой диапазон и дату меньше 2х лет
        if ((($dateEnd - $dateStart) > 5184000) || $dateStart < (time() - 63072000))
            return '';
        $summaryCall = (new viewPBX2CDR())->getSummaryCallUser(0, $dateStart, $dateEnd);
        $retArray = $this->getSummaryTableFromUnits(0, $summaryCall);

        // просчет неучтеных пользователй, которых нет в списке сотрудников
        if ($isNullView) {
            $tmp = array('duration' => 0,
                'cost' => 0,
                'count' => 0,
                'tel' => 'none',
                'title' => 'Другие'
            );
            foreach ($summaryCall as $row) {
                if (empty($row['cn']) || empty($row['fullName'])) {
                    $tmp["duration"] += (int)$row['duration'];
                    $tmp["cost"] += (float)$row['cost'];
                    $tmp["count"] += (int)$row['count'];
                }
            }
            $tmp["cost"] = ((isset($row['cost'])) ? round($row['cost'], 2) : 0);
            $retArray[] = $tmp;//array('title' => 'Другие', 'tel' => 'none');
        }
        //var_dump($retArray);
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setContent(json_encode($retArray));
        return $this->response;

    }

    /** расчет таблиы стоимости звонка ня сотрудника/департамент*/
    private function getSummaryTableFromUnits($id_parent = 0, &$tableCalled = array())
    {
        $units = tableUnits::find("id_parent=" . $id_parent);
        $json = array();
        if ($units) {
            $i = 0;
            $SumDuration = 0;
            $SumCost = 0;
            $SumCount = 0;
            foreach ($units as $unit) {
                $SumDuration = $SumCost = $SumCount = 0;
                $json[$i]["title"] = (!empty($unit->Description)) ? $unit->Description : $unit->Name;
                $json[$i]["key"] = $unit->id;

                $subUnit = $this->getSummaryTableFromUnits($unit->id, $tableCalled);
                if (!empty($subUnit)) {
                    $json[$i]['children'] = $subUnit;
                    foreach ($subUnit as $childrenNode) {
                        $json[$i]["duration"] = (isset($json[$i]["duration"]) ? (int)$json[$i]["duration"] : 0) + $childrenNode['duration'];
                        $json[$i]["cost"] = (isset($json[$i]["cost"]) ? $json[$i]["cost"] : 0) + $childrenNode['cost'];
                        $json[$i]["count"] = (isset($json[$i]["count"]) ? (int)$json[$i]["count"] : 0) + $childrenNode['count'];
                    }
                }
                $json[$i]['folder'] = true;
                foreach ($tableCalled as $rowUser) {
                    //var_dump($unit->id);
                    if ((int)$rowUser['id_unit'] == $unit->id) {
                        $json[$i]['folder'] = true;
                        $tmp = array();
                        $tmp['title'] = $rowUser['fullName'];
                        $tmp['key'] = $rowUser['id'];
                        $tmp['tel'] = $rowUser['tel'];
                        $tmp['position'] = (!empty($rowUser['title']) ? $rowUser['title'] : $rowUser['description']);
                        $tmp['duration'] = $rowUser['duration'];
                        $tmp['cost'] = $rowUser['cost'];
                        $tmp['count'] = $rowUser['cnt'];
                        $SumDuration += (int)$tmp['duration'];
                        $SumCost += (float)$tmp['cost'];
                        $SumCount += (int)$tmp['count'];
                        $json[$i]['children'][] = $tmp;
                    }
                }
                $json[$i]["duration"] = (isset($json[$i]["duration"]) ? (int)$json[$i]["duration"] : 0) + $SumDuration;
                $json[$i]["cost"] = (isset($json[$i]["cost"]) ? (float)$json[$i]["cost"] : 0) + round($SumCost, 2);
                $json[$i]["count"] = (isset($json[$i]["count"]) ? (int)$json[$i]["count"] : 0) + $SumCount;
                //$json[$i++]["extraClasses"] = "ws-pre";
                $i++;
            }

        }
        return $json;
    }

    /** Выгрузка в формате XLSX таблицы отчета о стоимости звонка ня сотрудника
     */
    public function GetPBXReportXLSAction($dateStart = null, $dateEnd = null)
    {
        $this->view->disable();
        $this->response->setContentType('application/vnd.ms-excel');
        $this->response->setFileToSend("report.xlsx");
        $summaryCall = (new viewPBX2CDR())->getSummaryCallUser(0, $dateStart, $dateEnd);
        if (empty($summaryCall)) {
            $this->response->setStatusCode(404);
            return;
        }

        //Создаем экземпляр класса электронной таблицы
        $spreadsheet = new Spreadsheet();
        //Получаем текущий активный лист
        $sheet = $spreadsheet->getActiveSheet();


        $sheet->setCellValue('A1', 'Отчет');
        $sheet->setCellValue('A2', 'за период ' . date("d.m.Y", $dateStart) . ' - ' . date("d.m.Y", $dateEnd) . '');
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A1:A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'italic' => false,
                'underline' => Font::UNDERLINE_NONE,
                'strikethrough' => false,
                'size' => 16,
                'color' => [
                    'rgb' => '000000'
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => false,
            ]]);
        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(20);

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);

        $startRowCount = 3;

        // Записываем в ячейку A1 данные
        $sheet->setCellValue('A' . $startRowCount, 'Департамент');
        $sheet->setCellValue('B' . $startRowCount, 'Сотрудник');
        $sheet->setCellValue('C' . $startRowCount, 'Должность');
        $sheet->setCellValue('D' . $startRowCount, 'Логин');
        $sheet->setCellValue('E' . $startRowCount, 'вн. тел');
        $sheet->setCellValue('F' . $startRowCount, 'Стоимость (тг.)');
        $sheet->setCellValue('G' . $startRowCount, 'Длительность (сек.)');
        $sheet->getStyle('A' . $startRowCount . ':G' . $startRowCount)->applyFromArray([
            'font' => [
                'bold' => true,
                'italic' => false,
                'underline' => Font::UNDERLINE_NONE,
                'strikethrough' => false,
                'color' => [
                    'rgb' => '000000'
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => false,
            ]]);
        $sheet->getRowDimension($startRowCount)->setRowHeight(28);
        foreach ($summaryCall as $row) {
            $startRowCount++;
            $row['unitname'] = (isset($row['unitname'])) ? $row['unitname'] : "";
            $sheet->setCellValue('A' . $startRowCount, (!empty($row['unitdescription'])) ? $row['unitdescription'] : $row['unitname']);
            $sheet->setCellValue('B' . $startRowCount, $row['fullName']);
            $sheet->setCellValue('C' . $startRowCount, $row['description']);
            $sheet->setCellValue('D' . $startRowCount, $row['cn']);
            $sheet->setCellValue('E' . $startRowCount, $row['callingnumber']);
            $sheet->setCellValue('F' . $startRowCount, $row['cost']);
            $sheet->setCellValue('G' . $startRowCount, $row['duration']);
        }
        $writer = new Xlsx($spreadsheet);
        //Сохраняем файл в текущей папке, в которой выполняется скрипт.
        //Чтобы указать другую папку для сохранения.
        //Прописываем полный путь до папки и указываем имя файла
        $writer->save('php://output');
        return; //$html;
    }


    /**
     *  Полный отчет в формате xls без группировки
     */
    public function GetFullPBXReportXLSAction($dateStart = null, $dateEnd = null)
    {
        $this->view->disable();
        $this->response->setContentType('application/vnd.ms-excel');
        $this->response->setFileToSend("FullReport.xlsx");
        $summaryCall = viewPBX2CDR::find(
            [
                'conditions' => 'datetime BETWEEN ?0 AND ?1',
                'bind' => [
                    0 => $dateStart,
                    1 => $dateEnd
                ],
                'bindTypes' => [
                    Phalcon\Db\Column::BIND_PARAM_INT,
                    Phalcon\Db\Column::BIND_PARAM_INT
                ]
                /* 'columns' => [
                     'datetime',
                     'CalledNumber',
                     'direction',
                     'pbxduration',
                     'tarif',
                     'pkid',
                     'cost',
                     'overinfo',
                     'callingpartynumber',
                     'originalcalledpartynumber',
                     'finalcalledpartynumber',
                     'datetimeconnect',
                     'datetimedisconnect',
                     'origdevicename',
                     'name',
                     'code',
                 ]*/
            ]
        );

        if (empty($summaryCall)) {
            $this->response->setStatusCode(404);
            return;
        }
        //var_dump($summaryCall->toArray());
        //Создаем экземпляр класса электронной таблицы
        $spreadsheet = new Spreadsheet();
        //Получаем текущий активный лист
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Полный отчет');
        $sheet->setCellValue('A2', 'за период ' . date("d.m.Y", $dateStart) . ' - ' . date("d.m.Y", $dateEnd) . '');
        $sheet->mergeCells('A1:M1');
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A1:A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'italic' => false,
                'underline' => Font::UNDERLINE_NONE,
                'strikethrough' => false,
                'size' => 16,
                'color' => [
                    'rgb' => '000000'
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => false,
            ]]);

        $sheet->getRowDimension(1)->setRowHeight(20);
        $sheet->getRowDimension(2)->setRowHeight(20);

        foreach (range('A', 'M') as $i) {
            $sheet->getColumnDimension($i)->setAutoSize(true);
        }
        $startRowCount = 4;
        // Записываем в ячейку A1 данные
        $head = [
            'Департамент',
            'Сотрудник',
            'Должность',
            'Логин',
            'вн. тел',
            'Набранный номер',
            'Финальный номер',
            'Стоимость (тг.)',
            'Длительность (сек.)',
            'Код',
            'Направление',
            'ID Вызова',
            'Устройство',
        ];
        reset($head);
        foreach (range('A', 'M') as $i) {
            $sheet->setCellValue($i . $startRowCount,current($head));
            next($head);
        }


        $sheet->getStyle('A' . $startRowCount . ':M' . $startRowCount)->applyFromArray([
            'font' => [
                'bold' => true,
                'italic' => false,
                'underline' => Font::UNDERLINE_NONE,
                'strikethrough' => false,
                'color' => [
                    'rgb' => '000000'
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => false,
            ]]);

        $sheet->getRowDimension($startRowCount)->setRowHeight(28);
        foreach ($summaryCall as $row) {
            $startRowCount++;
            //  $row['unitname'] = (isset($row['unitname'])) ? $row['unitname'] : "";
            //  $sheet->setCellValue('A' . $startRowCount, (!empty($row['unitdescription'])) ? $row['unitdescription'] : $row['unitname']);
            //  $sheet->setCellValue('B' . $startRowCount, $row['fullName']);
            //  $sheet->setCellValue('C' . $startRowCount, $row['description']);
            //  $sheet->setCellValue('D' . $startRowCount, $row['cn']);
            $sheet->setCellValue('A' . $startRowCount, date('d.m.Y h:i:s', $row->datetime));

            $sheet->setCellValue('E' . $startRowCount, $row->callingpartynumber);
            $sheet->setCellValue('F' . $startRowCount, $row->originalcalledpartynumber);
            $sheet->setCellValue('G' . $startRowCount, $row->CalledNumber);
            $sheet->setCellValue('H' . $startRowCount, $row->cost);
            $sheet->setCellValue('I' . $startRowCount, $row->pbxduration);
            $sheet->setCellValue('J' . $startRowCount, $row->code);
            $sheet->setCellValue('K' . $startRowCount, $row->name);
            $sheet->setCellValue('L' . $startRowCount, $row->pkid);
            $sheet->setCellValue('M' . $startRowCount, $row->origdevicename);
        }
        $writer = new Xlsx($spreadsheet);
        //Сохраняем файл в текущей папке, в которой выполняется скрипт.
        //Чтобы указать другую папку для сохранения.
        //Прописываем полный путь до папки и указываем имя файла
        $writer->save('php://output');
        return; //$html;
    }

    public function GetFailedPBXDataAction($dateStart = null, $dateEnd = null)
    {
        $this->view->disable();
        if (empty($dateStart) || empty($dateEnd)) {
            return null;
        }
        $viewNULL = viewPBX2CDR::find([
            'conditions' => 'datetime BETWEEN ?0 AND ?1 AND pkid IS NULL ',
            'bind' => [
                0 => $dateStart,
                1 => $dateEnd
            ],
            'bindTypes' => [
                Phalcon\Db\Column::BIND_PARAM_INT,
                Phalcon\Db\Column::BIND_PARAM_INT
            ],
            'columns' => [
                'pbx_id',
                'datetime',
                'CalledNumber',
                'direction',
                'pbxduration',
                'tarif',
                'cost',
                'overinfo',
                'pkid'
            ]
        ]);
        $html = '
        <table class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th style="width: 10px">#</th>
                      <th>Дата вызова</th>
                      <th>Вызываемый номер</th>
                      <th>Длительность (сек.)</th>
                      <th>Тариф</th>
                      <th>Стоимость (тг.)</th>
                    </tr>
                  </thead>
                  <tbody>';
        $pbxduration = $cost = 0;
        foreach ($viewNULL as $row) {
            $html .= '<tr>';
            $html .= '<td>' . $row->pbx_id . '</td>';
            $html .= '<td>' . date('d.m.Y h:i:s', $row->datetime) . '</td>';
            $html .= '<td>' . $row->CalledNumber . '</td>';
            $html .= '<td>' . $row->pbxduration . '</td>';
            $html .= '<td>' . $row->tarif . '</td>';
            $html .= '<td>' . $row->cost . '</td>';
            $html .= '</tr>';
            $pbxduration += (int)$row->pbxduration;
            $cost += (int)$row->cost;
        }
        $html .= '<tfoot>';
        $html .= '<tr class="table-danger ">
                      <td colspan="3" class="text-right"><span class="fw-bolder">Итого:</span></td>
                      <td>' . $pbxduration . '</td>
                      <td></td>
                      <td>' . $cost . '</td>
                  </tr>';
        $html .= '</tfoot>';


        $html .= '</tbody></table>';
        //var_dump($viewNULL->toArray());
        return $html;
    }

    public function GetPBXReportToUsersAction($tel, $dateStart = null, $dateEnd = null)
    {
        $this->view->disable();
        $this->response->setContentType('application/json', 'UTF-8');
        if (empty($dateStart) || empty($dateEnd) || empty($tel)) {
            return 'Ошибка передачи формата данных';
        }

        $ret = array('error' => null, 'data' => null);
        try {
            if (empty($dateStart) || empty($dateEnd) || empty($tel))
                throw new Exception('Ошибка передачи формата данных');
            /// проверка на слишком большой диапазон и дату меньше 2х лет
            if ((($dateEnd - $dateStart) > 5184000) || $dateStart < (time() - 63072000))
                throw new Exception('слишком большой диапазон и дат');
            $result = viewPBX2CDR::find([
                'conditions' => 'callingpartynumber=:callnumber: AND datetimeconnect>:dateStart: AND datetimeconnect<:dateEnd:',
                'bind' => [
                    'callnumber' => $tel,
                    'dateStart' => $dateStart,
                    'dateEnd' => $dateEnd,
                ],
                'columns' => [
                    'datetimeconnect',
                    'finalcalledpartynumber',
                    'pbxduration',
                    'tarif',
                    'cost',
                    'name',
                    'origdevicename',
                ],

                'order' => 'datetimeconnect desc'
            ])->toArray();
            if (empty($result)) {
                //$this->response->setContent('[]');
            } else {
                for ($i = 0; $i < count($result); $i++)
                    $result[$i]['datetimeconnect'] = date("d.m.Y H:i:s", $result[$i]['datetimeconnect']);

            }
            $ret['data'] = $result;
        } catch (Exception $ex) {
            $ret['error'] = $ex->getMessage();
        }
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setContent(json_encode($ret));
        return $this->response;
    }

    /**
     * Суммарный отчет по направлениям для пользователя зв период
     */
    public function GetSummaryDirectionAction($tel = null, $dateStart = null, $dateEnd = null)
    {
        $this->view->disable();
        $this->response->setContentType('application/json', 'UTF-8');
        if (empty($dateStart) || empty($dateEnd)) {
            return null;
        }
        $rep = (new viewTopCall())->GetSummaryDirection($dateStart, $dateEnd, $tel);
        foreach ($rep as $key => $row) {
            if ($row[1] == null) {
                $rep[$key][1] = 'Корпоративный';
                break;
            }
        }
        $this->response->setContent(json_encode($rep));
        return $this->response;
    }

    public function FindCallNumAction()
    {
        $this->assets->addCss('js/plugins/DataTables/datatables.min.css');
        $this->assets->addJs('js/plugins/DataTables/datatables.min.js');
        $this->assets->addJs('js/ReportLib.js');
    }

    public function CreateReportFromAction()
    {
        $MAX_ROW_VIEW = 100;
        $this->view->disable();
        $rep = array('error' => '', 'data' => array());
        try {
            if (!$this->request->isGet())
                throw new Exception('Не правельный запрос');
            if (empty($this->request->getQuery('DateStart'))
                || empty($this->request->getQuery('DateEnd')))
                throw new Exception('Ошибка в дате');
            $tmp = (int)$this->request->getQuery('DateEnd') - (int)$this->request->getQuery('DateStart');
            if ($tmp > 31536000 || $tmp < 90)
                throw new Exception('Ошибка диапазона дат');

            $rep['data'] = (new viewPBX2CDR())->getCallingList($this->request->getQuery('DateStart'),
                $this->request->getQuery('DateEnd')
                , [
                    'src' => explode(',', $this->request->getQuery('txtSearchOut')),
                    'act' => ($this->request->getQuery('swOperation') == 'AND') ? 'AND' : 'OR',
                    'dst' => explode(',', $this->request->getQuery('txtSearchIn'))], $MAX_ROW_VIEW);

            if (empty($rep['data']))
                throw new Exception('Данные не найдены');

            if (count($rep['data']) > $MAX_ROW_VIEW)
                $rep['error'] = 'Найдено много записей, в таблице будет отображены первые ' . $MAX_ROW_VIEW . ' записей.';

        } catch (Exception $ex) {
            $rep['error'] = $ex->getMessage();
        }
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setContent(json_encode($rep));
        return $this->response;
    }

}