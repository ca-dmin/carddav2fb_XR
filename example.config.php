<?php

$config = [
    
    'script' => [
        'cache' => '/media/[YOURUSBSTICK]/carddav2fb/cache',        // your stick, drive or share designated for caching 
        'log'   => '/media/[YOURUSBSTICK]/carddav2fb/cache',        // at you Raspberry, on your NAS or ...
    ], 

    'server' => [
        [
            'url'      => 'https://..',
            'user'     => '[ACCOUNT]',
            'password' => '[PASSWORD]',
            ],                                                      /* define as many as you need   
        [
            'url'      => 'https://..',
            'user'     => '',
            'password' => '',
            ],                                                      */
    ],

    'fritzbox' => [
        'url'      => 'fritz.box',
        'user'     => '[USER]',                                     // e.g. 'dslf-config' AVM standard user for usual login
        'password' => '[PASSWORD]',
        'fonpix'   => '/[YOURUSBSTICK]/FRITZ/fonpix',               // the additional usb memory at the Fritz! box
        'fritzadr' => '/media/fritzbox/FRITZ/mediabox/FritzAdr.dbf'    // a mounted storage; if not empty FRITZadr Database
                                                                    // will be written to this location
                                                                    // (will be changed to ftp soon)
    ],

    'phonebook' => [
        'id'           => 0,               // only '0' can store quick dial and vanity numbers as well as images 
        'name'         => 'Telefonbuch',
        'imagepath'    => 'file:///var/InternerSpeicher/FRITZSTICK/FRITZ/fonpix/', // mandatory if you use the -i option
        'forcedupload' => 3,               // 3 = CardDAV contacts overwrite phonebook on Fritz!Box
    ],                                     // 2 = like 3, but newer entries will send as VCF via eMail (-> reply)
                                           // 1 = like 2, but vCards are only downloaded if they are newer than the phonebook

    'reply' => [                                                    // mandatory if you use "forcedupload" < 3 ! 
        'url'      => 'smtp...',
        'port'     => 587,                                          // alternativ 465
        'secure'   => 'tls',                                        // alternativ 'ssl'
        'user'     => '[USER]',                                     // your sender email adress e.g. account
        'password' => '[PASSWORD]',
        'receiver' => 'volker.pueschel@anasco.de',                  // your email adress to receive the secured contacts  
        'debug'    => 0,                                            // 0 = off (for production use)
                                                                    // 1 = client messages
                                                                    // 2 = client and server messages
    ],

    'filters' => [
        'include' => [                                              /* if empty include all by default
            'categories' => [                                          if your server is iCloud, groups can be used (XOR)
            ],
            'group' => [
            ],                                                      */
        ],

        'exclude' => [
            'categories' => [
                'A',
                'B',
                'C',
            ],                                                      /*
            'group' => [                                               if your server is iCloud, groups can be used (XOR)
                'D',
                'E',
                'F',
            ],                                                      */
        ],

    'conversions' => [
        
        'substitutes' => [                                          // you must not change this! 
            'PHOTO',                                                // Otherwise image upload failed!
        ],        
        
        'vip' => [
            'categories' => ['VIP'                                  // the category / categories, which should be marked as VIP
            ],
        ],
        
        'realName' => [                                             // are processed consecutively. Order decides!
            '{lastname}, {prefix} {nickname}',
            '{lastname}, {prefix} {firstname}',
            '{lastname}, {nickname}',
            '{lastname}, {firstname}',
            '{organization}',
            '{fullname}'
        ],

        'phoneTypes' => [                                           // you mustn´t define 'fax'!
            'WORK'    => 'work',                                    // this conversion is set fix in code!
            'HOME'    => 'home',
            'CELL'    => 'mobile',
            'MAIN'    => 'work',
            'FAX'     => 'fax',
            'default' => 'work',
        ],

        'emailTypes' => [
            'WORK' => 'work',
            'HOME' => 'home'
        ],
        
        'phoneReplaceCharacters' => [                               // are processed consecutively. Order decides!
            '+491'  => '01',                                        // domestic numbers without country code
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
            '('     => '',                                          // delete separators
            ')'     => '',
            '/'     => '',
            '-'     => '',
            '+'     => '00'                                         // normalize foreign numbers
        ]
    ]
];