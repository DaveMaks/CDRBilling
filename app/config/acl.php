<?php

return [
    // frontend
    'guest' => [
        'Auth' => [
            'index',
            'isUserValidation'
        ],
        'Index' => [
            'show404'
        ],
    ],
    'report' => [
        'Dashborad' => '*',
        'Index' => '*',
        'Report' => '*',
        'Auth' => [
            'index',
            'isUserValidation',
            'signout'
        ],

    ],
    'admin' => [
        'Dashborad' => '*',
        'Report' => '*',
        'Config' => '*',
        'Index' => '*',
        'Auth' => [
            'index',
            'isUserValidation',
            'signout'
        ],
    ],
];