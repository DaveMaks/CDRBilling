<?php
return
    [
        'folder' => '/srv/cucm/',
        'mask' => '^cdr_StandAloneCluster_\d{2}_\d{12}_\d+$',
        'folderLog' => '/srv/cucm/importlog/',
        'moveToArchive' => true,
        'maskInternational' => '^([0|9]810)|+', //формат набора межнар
        'maskLongDistance' => '^([0|9]8)', // вормат набора межгород
        'maskReplacePartyNumber' =>
            [
                '/^([0|9]810)/',
                '/^([0|9]8)/',
                '/^(0|9)(\d{6})$/',
                '/^(\+7)(\d+)/',

            ],
        'valueReplacePartyNumber' => [
            "",
            "7",
            "$2",
            "7$2"
        ],
        'countCharExtension' => 4, //колличество цифр вн. номера
    ];