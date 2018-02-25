<?php

$config = [
    // phonebook
    'phonebook' => [
        'id' => 0,
        'name' => 'Telefonbuch'
    ],

    // or server
    'server' => [
        'url' => '',
        'user' => '',
        'password' => '',
    ],

    // or fritzbox
    'fritzbox' => [
        'url' => '192.168.178.1',
        'user' => '',
        'password' => '',
    ],
    'reply' => [
            'url'      => 'smtp.strato.de',
            'port'     => 587,             // alternativ 465
            'secure'   => 'tls',           // alternativ 'ssl'
            'user'     => '',              // your account
            'password' => '',
            'receiver' => '',              // your email adress
            'debug'    => 0,               // 0 = off (for production use) 1 = client messages 2 = client and server messages
        ],

    'fritzadrpath' => [                      // if not empty FRITZadr Database will be written to this location
        '../FritzAdr.dbf'
    ],
    
    'filters' => [
        'include' => [                       // if empty include all by default
        ],

        'exclude' => [
            'categories' => [''
            ],
        ],
    ],

    'conversions' => [
        'vip' => [
            'categories' => ['VIP'
            ],
        ],
        'realName' => [
            '{lastname}, {prefix} {firstname}',
            '{lastname}, {firstname}',
            '{organization}',
            '{fullname}'
        ],
        'phoneTypes'  => [
            'WORK'    => 'work',
            'HOME'    => 'home',
            'CELL'    => 'mobile',
            'MAIN'    => 'work',
            'default' => 'work',
            'other'      => 'work'
        ],
        'emailTypes' => [
            'WORK' => 'work',
            'HOME' => 'home'
        ],
        'phoneReplaceCharacters' => [
            '+491'  => '01',
            '+492'  => '02',
            '+493'  => '03',
            '+494'  => '04',
            '+495'  => '05',
            '+496'  => '06',
            '+497'  => '07',
            '+498'  => '08',
            '+499'  => '09',
            '+49 1' => '01',
            '+49 2' => '02',
            '+49 3' => '03',
            '+49 4' => '04',
            '+49 5' => '05',
            '+49 6' => '06',
            '+49 7' => '07',
            '+49 8' => '08',
            '+49 9' => '09',
            '+49'   => '',
            '('     => '',
            ')'     => '',
            '/'     => '',
            '-'     => '',
            '+'     => '00'
        ]    
    ]
];
