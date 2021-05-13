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

class viewTopCall extends Model
{
    public $NumberCall;
    public $countCall;
    public $durationCall;
    public $user;


    public function initialize()
    {
        $this->setSource(TABLE_CDR_LOGS);
        $this->belongsTo('NumberCall', tableUsers::class, 'tel');
        //$this->belongsTo('NumberCall','tableUsers','tel',['alias'=>'Usr']);
    }

    /**
     *
     * @param  $direction string in/out Напрвление звонков
     * @param  $sort string duration/count Сортировать
     * @param  $countRow int количество строк
     * @param  $dateUnixTimeA int начальное время
     * @param  $dateUnixTimeВ int конечное время
     * @return viewTopCall[]
     */
    public function getTop($direction, $sort, $dateUnixTimeA, $dateUnixTimeB = null, $countRow = 10)
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

    public function getSumDuration($dateUnixTimeA, $dateUnixTimeB)
    {
        $sql = 'SELECT SUM(duration)as cnt FROM `' . TABLE_CDR_LOGS . '` WHERE (LENGTH(callingpartynumber)>4 OR LENGTH(finalcalledpartynumber)>4) AND datetimeconnect BETWEEN ' . $dateUnixTimeA . ' AND ' . $dateUnixTimeB . ' ';
        return $this->getOneCell($sql);
    }

    private function getOneCell($sql)
    {
        $cnt = $this->getReadConnection()->fetchOne($sql, Enum::FETCH_NUM);
        return (!empty($cnt) ? (int)$cnt[0] : null);
    }


    /** Отчет б общей сумме звонков по направлениям за период, */
    public function GetSummaryDirection($dateUnixTimeA, $dateUnixTimeB, $inTel = null,$isViewCorparative=true)
    {
        $sql = 'SELECT
              COUNT(*),
            pCode.name
            FROM (SELECT
                id_PhoneCode
              FROM `' . TABLE_CDR_LOGS . '`
              WHERE datetimeconnect BETWEEN '.(int)$dateUnixTimeA.' AND '.(int)$dateUnixTimeB.'
              '.(!empty($inTel)?'AND callingpartynumber ='.(int)$inTel:'').'
              '.(($isViewCorparative)?'AND NOT ISNULL(id_PhoneCode)':'').'
              ) AS cdr
            LEFT JOIN `' . TABLE_PHONE_CODE . '` pCode ON cdr.id_PhoneCode=pCode.id
            GROUP BY pCode.name';
        //echo $sql;
        return  $this->getReadConnection()->fetchAll($sql, Enum::FETCH_NUM);
        //return $ret;
    }
}

