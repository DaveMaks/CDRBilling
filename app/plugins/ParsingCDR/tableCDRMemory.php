<?php

namespace App\CDR;

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

use Phalcon\Mvc\Model;

class tableCDRMemory extends Model
{

    public $pkid;
    public $globalcallid_callmanagerid;
    public $globalcallid_callid;
    public $origlegcallidentifier;
    public $datetimeorigination;
    public $origipaddr;
    public $callingpartynumber;
    public $originalcalledpartynumber;
    public $finalcalledpartynumber;
    public $datetimeconnect;
    public $datetimedisconnect;
    public $lastredirectdn;
    public $duration;
    public $outpulsedcallingpartynumber;
    public $outpulsedcalledpartynumber;
    public $origdevicename;
    public $destdevicename;
    public $coment;

    public function initialize()
    {
        $this->setSource(TABLE_CDR_CACHE_LOGS);
    }

}

