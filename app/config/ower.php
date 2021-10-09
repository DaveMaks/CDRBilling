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
    'folderArchivePBX' => '/xampp/htdocs/biling/archivePBX/',
    'tempDir'=>session_save_path(),
    'baseUrl'=>'http://' . ((isset($_SERVER['SERVER_NAME']))?$_SERVER['SERVER_NAME']:'') . '/'
];

