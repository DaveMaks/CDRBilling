<?php

/*
log_CDRID DECIMAL(20, 0),
globalCallID_callManagerId INT(1),
globalCallID_callId INT(11),
origLegCallIdentifier BIGINT(11),
dateTimeOrigination DATETIME,
origIpAddr INT(11),
callingPartyNumber VARCHAR(30),
origMediaCap_payloadCapability INT(3),
destLegCallIdentifier INT(3),
originalCalledPartyNumber VARCHAR(30),
finalCalledPartyNumber VARCHAR(30),
dateTimeConnect DATETIME,
dateTimeDisconnect DATETIME,
lastRedirectDn VARCHAR(30),
duration BIGINT(10),
origCallTerminationOnBehalfOf INT(11),
destCallTerminationOnBehalfOf INT(11),
outpulsedCallingPartyNumber VARCHAR(50),
outpulsedCalledPartyNumber VARCHAR(50),
filename VARCHAR(255),
pkid VARCHAR(37)
*/

use Phalcon\Db\Enum;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class viewPBX2CDR extends Model
{
    var $pbx_id;
    var $datetime;
    var $CalledNumber;
    var $direction;
    var $pbxduration;
    var $tarif;
    var $cost;
    var $overinfo;
    var $pkid;
    var $callingpartynumber;
    var $originalcalledpartynumber;
    var $finalcalledpartynumber;
    var $datetimeconnect;
    var $datetimedisconnect;
    var $cdrduration;
    var $filename;
    var $coment;
    var $idPhoneCode;
    var $cdrid;
    var $id;
    var $name;
    var $code;
    var $symb;
    var $origdevicename;

    public function initialize()
    {
        $this->setSource(TABLE_VIEW_PBX2CDR);
    }

    public function getReportFromUnit($idUnit){

    }

    /**
     *
     * @param  $direction string in/out Напрвление звонков
     * @param  $sort string duration/count Сортировать
     * @param  $countRow int количество строк
     * @param  $dateUnixTimeA int начальное время
     * @param  $dateUnixTimeB int конечное время
     * @return Resultset
     */
    function getTop($direction, $sort, $dateUnixTimeA, $dateUnixTimeB = null, $countRow = 10)
    {
        if (empty($dateUnixTimeB))
            $dateUnixTimeB = time();
        $param = ['direction' => ($direction == 'out') ? 'callingpartynumber' : 'finalcalledpartynumber',
            'DateA' => (int)$dateUnixTimeA,
            'DateB' => (int)$dateUnixTimeB,
            'sort' => (($sort == 'duration') ? 'durationCall' : 'countCall'),
            'lim' => (int)$countRow];
        $sql = 'SELECT cals.*,tb_users.fullname as user FROM 
                        (SELECT ' . $param['direction'] . ' AS NumberCall,
                          COUNT(pkid) AS countCall, 
                          SUM(duration) AS durationCall 
                          FROM ' . TABLE_CDR_LOGS . '
                          WHERE datetimeconnect AND datetimeconnect BETWEEN ' . $param['DateA'] . ' AND ' . $param['DateB'] . '
                          GROUP BY ' . $param['direction'] . ' 
                          LIMIT ' . $param['lim'] . ' ) AS cals 
                            LEFT JOIN ' . TABLE_USERS . ' ON cals.NumberCall=tb_users.tel
                        ORDER BY ' . $param['sort'] . ' DESC';
//        echo $sql;
        return new Resultset(null, $this, $this->getReadConnection()->query($sql));
    }

    public function getCountIn($dateUnixTimeA, $dateUnixTimeB)
    {
        $sql = 'SELECT COUNT(pkid) as cnt FROM `' . TABLE_CDR_LOGS . '` WHERE LENGTH(callingpartynumber)>4 AND datetimeconnect BETWEEN ' . $dateUnixTimeA . ' AND ' . $dateUnixTimeB . ' ';
        return $this->getOneCell($sql);
    }

    public function getCountOut($dateUnixTimeA, $dateUnixTimeB)
    {
        $sql = 'SELECT COUNT(pkid) as cnt FROM `' . TABLE_CDR_LOGS . '` WHERE LENGTH(finalcalledpartynumber)>4 AND datetimeconnect BETWEEN ' . $dateUnixTimeA . ' AND ' . $dateUnixTimeB . ' ';
        return $this->getOneCell($sql);
    }

    public
    function getSumDuration($dateUnixTimeA, $dateUnixTimeB)
    {
        $sql = 'SELECT SUM(duration)as cnt FROM `' . TABLE_CDR_LOGS . '` WHERE (LENGTH(callingpartynumber)>4 OR LENGTH(finalcalledpartynumber)>4) AND datetimeconnect BETWEEN ' . $dateUnixTimeA . ' AND ' . $dateUnixTimeB . ' ';
        return $this->getOneCell($sql);
    }

    private
    function getOneCell($sql)
    {
        $cnt = $this->getReadConnection()->fetchOne($sql, Enum::FETCH_NUM);
        return (!empty($cnt) ? (int)$cnt[0] : null);
    }

    public function getSummaryCallUser($units = 0, $dateUnixTimeA = 0, $dateUnixTimeB = 0)
    {
        if (is_array($units)) {

        } elseif (is_numeric($units)) {

        } else return null;

        $sql = 'SELECT * FROM
                (SELECT IF (LENGTH(callingpartynumber)=4,callingpartynumber,originalcalledpartynumber) AS callingnumber, 
                SUM(pbxduration) as duration,
                SUM(cost) as cost,
                COUNT(id) as cnt                
                FROM 
                cucmdb.vv_pbx2cdr
                WHERE datetimeconnect BETWEEN ' . (int)$dateUnixTimeA . ' AND ' . (int)$dateUnixTimeB . '
                GROUP BY callingnumber) AS summaryCallUser
                LEFT JOIN tb_users AS usr ON summaryCallUser.callingnumber=usr.tel
                ORDER BY summaryCallUser.cost DESC ;';
        return  $this->getReadConnection()->fetchAll($sql);
    }

    /**
     * Вывод всех звонков с дополнительной информацией, и пользователями за период и строкой запроса
     * @param int $dateUnixTimeA c unix_time
     * @param int $dateUnixTimeB по unix_time
     * @param array $searchArray Строка запроса array['src','dst','act']
     * @param int $limit
     * @return mixed array|string
     */
    public function getCallingList($dateUnixTimeA = 0, $dateUnixTimeB = 0, array $searchArray, $limit = 100)
    {
        $where = '';
        if (!empty($searchArray['src'])) {
            /*Запрос источника*/
            $srcWhere = $this->CreateWhereString('callingpartynumber', $searchArray['src']);
            $where = $srcWhere;
        }
        if (!empty($searchArray['dst'])) {
            /*Запрос назанчения*/
            $dstWhere = $this->CreateWhereString('finalcalledpartynumber', $searchArray['dst']);
            if (!empty($where))
                $where .= ' ' . $searchArray['act'] . ' ';
            $where .= $dstWhere;
        }
        if (empty($where))
            $where = '1';

        /**@var $pdo Phalcon\Db\Adapter\Pdo\Mysql */


        //`finalcalledpartynumber`="2223" OR `callingpartynumber`="2223"
        //$this->getDI()->getShared('modelsManager');

        $sqlBase = 'SELECT 
                  cdr.`cdrid`,
                  cdr.`pkid`,
                  cdr.`calling`,
                  cdr.`originalcalledpartynumber`,
                  cdr.`called`,
                  FROM_UNIXTIME(cdr.`datetimeconnect`,"%Y.%m.%d %h:%i:%s") as `datetimeconnect`,
                  cdr.`datetimedisconnect`,
                  cdr.`cdrduration`,
                  cdr.`PhoneCodeID`,
                  cdr.`srcdevicename`,
                  cdr.`dstdevicename`,
                  cdr.`PhoneCodeName`,
                  pbx.tarif,
                  pbx.cost,
                  callingusr.fullName AS callingusr_fullName,
                  callingusr.title  AS callingusr_title,
                  callingusr.department  AS callingusr_department,
                  calledusr.fullName AS calledusr_fullName,
                  calledusr.title  AS calledusr_title,
                  calledusr.department  AS calledusr_department
                FROM(
                SELECT
                  `id` AS `cdrid`,
                  `pkid` AS `pkid`,
                  `callingpartynumber` AS `calling`,
                  `originalcalledpartynumber` AS `originalcalledpartynumber`,
                  `finalcalledpartynumber` AS `called`,
                  `datetimeconnect` AS `datetimeconnect`,
                  `datetimedisconnect` AS `datetimedisconnect`,
                  `duration` AS `cdrduration`,
                  `id_PhoneCode` AS `PhoneCodeID`,
                  `origdevicename` AS `srcdevicename`,
                  `destdevicename` AS `dstdevicename`,
                  IF(ISNULL(`id_PhoneCode`),NULL,(SELECT name FROM tb_phoneCode WHERE id=`id_PhoneCode` LIMIT 1)) AS PhoneCodeName
                FROM `' . TABLE_CDR_LOGS . '` WHERE datetimeconnect BETWEEN ' . $dateUnixTimeA . ' AND ' . $dateUnixTimeB . '
                  AND (' . $where . ') LIMIT ' . ((int)$limit + 1) . ') AS cdr
                  LEFT JOIN `' . TABLE_TBX . '` pbx ON 
                   `cdr`.`datetimeconnect` BETWEEN `pbx`.`datetime` - 120 AND `pbx`.`datetime` + 120
                        AND `cdr`.`called` = `pbx`.`CalledNumber`
                  LEFT JOIN `' . TABLE_USERS . '` calledusr ON cdr.called=calledusr.tel
                  LEFT JOIN `' . TABLE_USERS . '` callingusr ON cdr.calling =callingusr.tel';
        //var_dump($sqlBase);

        /** @var $pdo Phalcon\Db\Adapter\Pdo\Mysql */
        $pdo = $this->getReadConnection();
        try {
            $result = $pdo->query($sqlBase);
            $result->setFetchMode(Phalcon\Db\Enum::FETCH_ASSOC);
            return $result->fetchAll();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function CreateWhereString($collum, $Value): string
    {
        $arrValue = array();
        if (is_array($Value))
            $arrValue = $Value;
        else
            $arrValue[] = $Value;
        if (count($arrValue) == 0)
            return "";
        $pdo = $this->getReadConnection();
        /** @var $WhereValue [] список сформированных условий */
        $WhereValue = array();
        foreach ($arrValue as $Row) {
            if (empty($Row)) continue;
            $cntSearch = 0;
            $Row = str_replace(['*', '?'], ['%', '_'], $Row, $cntSearch);
            if ($cntSearch > 0)
                $WhereValue[] = "`" . $collum . "` LIKE " . "" . $pdo->escapeString($Row) . "";
            else
                $WhereValue[] = "`" . $collum . "`=" . "" . $pdo->escapeString($Row) . "";
        }
        if (count($WhereValue) > 0)
            return '(' . implode(" OR ", $WhereValue) . ')';
        else return '';
    }


}

