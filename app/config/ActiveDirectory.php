<?php
/**
 * Created by PhpStorm.
 * User: vanuykov.m
 * Date: 05.10.2020
 * Time: 17:19
 */

return [
    'host' => "ldap://10.110.2.11",
    'user' => "ldap",
    'domainName'=>'domain.local',
    'password' => "PASSWORD",
    'baseDN' => "OU=UNIT,DC=domain,DC=local",
    'filterOU'=>'(description=*)', // фильтр для выбора определенных OU при построении справочника
    'filterUser'=>'(displayName=*)(ipPhone=*)', // фильтр для выбора определенных пользователей при построении справочника
    'ipPhone'=>'ipphone', // поле в АД где хранится вн. номер, для связи пользователя и логов CDR
    'title'=>'description',// Поле AD где хранится должность
    'adminGroupFromAD'=>'', //пользователи в группе АД имеющие права админа
    'reportGroupFromAD'=>'', //пользователи в группе АД имеющие доступ на отчеты
];
