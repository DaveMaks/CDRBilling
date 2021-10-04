<?php
return [
    'logger' => [
        'name' => APP_PATH . '/log/' . date("Ymd") . '.log',
        'adapter' => 'file',
    ],
    'TimeZone' => 'Asia/Almaty',
    'CountryCIS' => [
        'Азербайджан',
        'Армения',
        'Белоруссия',
        'Беларусь',
        'Казахстан',
        'Киргизия',
        'Молдавия',
        'Россия',
        'Таджикистан',
        'Узбекистан'],
    'folderArchivePBX' => '/srv/cucm/archivePBX/',
    'tempDir'=>'/xampp/tmp',
    'baseUrl'=>'http://' . $_SERVER['SERVER_NAME'] . '/'
];

