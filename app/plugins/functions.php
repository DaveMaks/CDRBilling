<?php

function Sec2String(int $sec)
{
    $seconds = $sec % 60;
    $minutes = ($sec / 60) % 60;
    $hours = (int)($sec / 3600);
    return $hours . 'ч ' . $minutes . 'm ' . $seconds . 'c';
}