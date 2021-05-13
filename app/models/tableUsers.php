<?php


use Phalcon\Di;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;
use Phalcon\Mvc\Model\Query;

class tableUsers extends Model
{
    var $id;
    var $id_unit;
    var $cn;
    var $fullName;
    var $description;
    var $tel;
    var $title;
    var $department;
    var $email;
    var $overdata;

    public function initialize()
    {
        $this->setSource(TABLE_USERS);
        $this->belongsTo('id_unit', 'tableUnits', 'id', ['alias' => 'Unit']);
    }

    public static function getUsersByUnit($idUnit, $day = null)
    {
        if (is_null($day))
            $day = time();
        $timeStart = mktime(0, 0, 0, date("m", $day), date("d", $day), date("Y", $day));
        $timeEnd = mktime(23, 59, 59, date("m", $day), date("d", $day), date("Y", $day));
        $sql = 'SELECT id, id_unit, cn, fullName, description, tel, title, department, email, overdata,
                       CONCAT("[",(SELECT GROUP_CONCAT(
                                  CONCAT(\'{"timeconnect":\', datetimeconnect,
                                   \', "duration":\',duration,
                                   \', "direction":\',IF(callingpartynumber=usr.tel,0,1),
                                  \'}\'))
                       FROM tableCDRLogs
                        WHERE datetimeconnect between '.$timeStart.' AND '.$timeEnd.'
                           and (callingpartynumber=usr.tel OR  finalcalledpartynumber=usr.tel) 
                           ORDER BY datetimeconnect DESC ),
                           "]") 
                           as Json
        FROM tableUsers AS usr WHERE id_unit=' . (int)$idUnit;
        //echo $sql;
        /**
         * @var $manager Phalcon\Mvc\Model\Manager
         */
        $container = Di::getDefault();
        $manager = $container->getShared("modelsManager");
        return $manager->executeQuery($sql);
    }

}

