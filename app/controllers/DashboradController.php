<?php
use Phalcon\Mvc\Controller;
use Phalcon\Mvc\View;
use Phalcon\Db\Enum;
use \App\LocalClass\viewCdrData;
use \Phalcon\Mvc\Model\Query;
use \Phalcon\Db;

class DashboradController extends Controller
{
    public function indexAction()
    {

    }

    public function getInfoBoxAction($type = null, $rangeName = 'day')
    {
        if ($this->request->isAjax()) {
            $this->view->setRenderLevel(
                View::LEVEL_ACTION_VIEW
            );
        };
        $d = new DateTime('now', new DateTimeZone($this->config->TimeZone));
        $d->setTime(0, 0, 0);
        switch ($rangeName) {
            case'week':
                $dateStart = $d->getTimestamp() - 604800;
                break;
            case'month':
                $dateStart = $d->getTimestamp() - 2419200;
                break;
            default: //case'day':
                $dateStart = $d->getTimestamp();
                break;
        }

        $param = array(
            'content' => '',
            'value' => 'Н/Д',
            'bg' => 'bg-success',
            'iconclass' => 'fas fa-sign-out-alt',
        );

        $viewData = new viewTopCall();
        switch ($type) {
            case 'countIn':
                $cnt = $viewData->getCountIn($dateStart, time());
                $param['iconclass'] = 'fas fa-sign-in-alt';
                $param['content'] = 'Внешние входящие';
                $param['value'] = (!empty($cnt) ? $cnt : 'Н/Д');
                break;
            case 'countOut':
                $cnt = $viewData->getCountOut($dateStart, time());
                $param['iconclass'] = 'fas fa-sign-out-alt';
                $param['bg'] = 'bg-warning';
                $param['content'] = 'Внешние исходящие';
                $param['value'] = (!empty($cnt) ? $cnt : 'Н/Д');
                break;
            case 'countDuration':
                $cnt = $viewData->getSumDuration($dateStart, time());
                $param['iconclass'] = 'fas fa-clock';
                $param['bg'] = 'bg-info';
                $param['content'] = 'Суммарная длительность';
                $dc = 'сек';
                if (!empty($cnt)) {
                    if ((int)$cnt > 60) {
                        $cnt = round((int)$cnt / 60, 2);
                        $dc = 'мин';
                    }
                    if ((int)$cnt > 60) {
                        $cnt = round((int)$cnt / 60, 2);
                        $dc = 'ч';
                    }
                }
                $param['value'] = (!empty($cnt) ? $cnt . ' <small>' . $dc . '</small>' : 'Н/Д');
                break;
            case 'summ':
                $param['iconclass'] = 'fas fa-dollar-sign';
                $param['bg'] = 'bg-orange';
                $param['content'] = 'Стоимость';
                break;
            default:
                $param['iconclass'] = 'fas fa-dollar-sign';
                $param['bg'] = 'bg-orange';
                $param['content'] = '';
                $param['value'] = '';
                break;
        }
        $this->view->setVars($param, true);
        $this->view->pick("Index/infoBox");
        return true;
    }

    public function GetTOPTableAction($direction = 'in', $sort = 'duration', $rangeName = 'day')
    {
        if ($this->request->isAjax()) {
            $this->view->setRenderLevel(
                View::LEVEL_ACTION_VIEW
            );
        };
        $d = new DateTime('now', new DateTimeZone($this->config->TimeZone));
        $d->setTime(0, 0, 0);
        switch ($rangeName) {
            case'week':
                $dateStart = $d->getTimestamp() - 604800;
                break;
            case'month':
                $dateStart = $d->getTimestamp() - 2419200;
                break;
            default: //case'day':
                $dateStart = $d->getTimestamp();
                break;
        }
        $top = new viewTopCall();
        $getrow = $top->getTop($direction, $sort, $dateStart);
        $topCallIn = '<table class="table table-striped table-valign-middle">';
        $topCallIn .= '
                  <thead>
                      <tr>
                        <th>Абонент</th>
                        <th>Количество</th>
                        <th>Длительность</th>
                      </tr>
                  </thead>
                  <tbody>';
        foreach ($getrow as $row) {
            $topCallIn .= '<tr>';
            $topCallIn .= '<td>' . $row->user . ' <span class="badge bg-green"><small>' . $row->NumberCall . '</small></span></td>';
            $topCallIn .= '<td>' . $row->countCall . '</td>';
            $topCallIn .= '<td>' . Sec2String($row->durationCall) . '</td>';
            $topCallIn .= '</tr>';
        }
        $topCallIn .= '</tbody></table>';
        echo $topCallIn;
    }

    public function GetChartTableAction($rangeName = 'month')
    {
        if ($this->request->isAjax()) {
            $this->view->setRenderLevel(
                View::LEVEL_ACTION_VIEW
            );
        };
        $dateEnd = time();
        switch ($rangeName) {
            case 'day':
                $dateStart = $dateEnd - 86400; //-24 часа
                $timeStep = 3600; //шаг в час
                $groupFormatSQL = '%Y-%m-%d-%H';
                $groupFormatPHP = 'Y-m-d-H';
                break;
            case 'week':
                $groupFormatSQL = '%Y-%m-%d';
                $groupFormatPHP = 'Y-m-d';
                $timeStep = 86400; //шаг в дни
                $dateStart = $dateEnd - 604800; // -7дней
                break;
            default: // month
                $groupFormatSQL = '%Y-%m-%d';
                $groupFormatPHP = 'Y-m-d';
                $timeStep = 86400; //шаг в дни
                $dateStart = $dateEnd - 2592000;// -30 дней
                break;
        }
        $sql = 'SELECT FROM_UNIXTIME(datetimeconnect,"' . $groupFormatSQL . '") AS dategroup,SUM(duration) AS duration ,COUNT(pkid) AS `count`
                  FROM `' . TABLE_CDR_LOGS . '`
                  WHERE datetimeconnect>=' . $dateStart . ' and datetimeconnect<=' . $dateEnd . '
                  GROUP BY Dategroup ORDER BY dategroup';
        $result = $this->di->getShared('db')->fetchAll($sql, Enum::FETCH_ASSOC);

        if (empty($result))
            return json_encode('none');

        $ResultS = array();
        foreach ($result as $row) {
            $ResultS[$row['dategroup']] = [
                'duration' => $row['duration'],
                'count' => $row['count'],
            ];
        }
        $result = $ResultS;
        unset($ResultS);

        // Форматируем Вывод
        $labels = array(); // подписи
        $dataChar = array(); // список данных
        for ($timeA = $dateStart; $timeA <= $dateEnd; $timeA += $timeStep) {
            $strTime = date($groupFormatPHP, $timeA);
            $labels[] = $strTime;
            $dataChar[0][] = (isset($result[$strTime])) ? (int)$result[$strTime]['duration'] : 0;
            $dataChar[1][] = (isset($result[$strTime])) ? (int)$result[$strTime]['count'] : 0;
        }

        return json_encode(
            [$labels,
                $dataChar
            ]);
    }

}