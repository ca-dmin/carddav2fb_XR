<?php

$config = [
    'phonebook' => [
        'id'           => 0,                   // 0 is the first (standard phonebook) for test puposes use the next free ID...
        'name'         => 'Telefonbuch',       // ... befor you overwrite/replace one of the existing phonebooks
		'forcedupload' => 2,                   // 3 = CardDAV contacts overwrite phonebook on Fritz!Box
    ],                                         // 2 = like 3, but newer entries will send as VCF via eMail (-> reply)
                                               // 1 = like 2, but vCards are only downloaded if they are newer than the phonebook
	
    'server' => [
        [
            'url'      => 'https://...',
            'user'     => '',
            'password' => '',
            ],                                 /* define as many as you need
        [
            'url'      => '',
            'user'     => '',
            'password' => '',
            ],                                 */
    ],

	'fritzbox' => [
        'url'      => 'fritz.box',             // fritz.box or IP (typical 192.168.178.1)
        'user'     => 'dslf-config',           // e.g. dslf-config AVM standard user for usual login
        'password' => '',
    ],

    'reply' => [
	    'url'      => 'smtp...',
		'port'     => 587,                     // alternativ 465
		'secure'   => 'tls',                   // alternativ 'ssl'
        'user'     => '',                      // your sender email adress e.g. account
        'password' => '',
		'receiver' => '',                      // your email adress
		'debug'    => 2,                       // 0 = off (for production use)
	],	                                       // 1 = client messages
	    									   // 2 = client and server messages


    'fritzadrpath' => [                      // if not empty FRITZadr Database will be written to this location
        '../FritzAdr.dbf'                    // please consider to have a compiled dBase modul added to your php installation 
    ],
 
    'filters' => [
        'include' => [                         // if empty include all by default
        ],

        'exclude' => [
            'categories' => [
			    'A',
				'B',
				'C',
            ],
		    'group'     => [
			],
        ],
    ],

    'conversions' => [
        'vip' => [
            'categories' => ['VIP'
            ],
        ],
        'realName' => [
            '{lastname}, {prefix} {nickname}',        // are processed consecutively. Order decides!
            '{lastname}, {prefix} {firstname}',
            '{lastname}, {nickname}',
            '{lastname}, {firstname}',
            '{organization}',
            '{fullname}'
        ],
		
        'phoneTypes' => [                             // you mustn´t define 'fax' - this conversion is set fix in code
            'work'    => 'work',
            'home'    => 'home',
            'cell'    => 'mobile',
            'main'    => 'work',
            'default' => 'work',
            'other'   => 'work'
        ],
		
        'emailTypes' => [
            'WORK' => 'work',
            'HOME' => 'home'
        ],
		
        'phoneReplaceCharacters' => [  // are processed consecutively. Order decides!
            '+491'  => '01',           // domestic numbers without country code 
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
            '('     => '',             // delete separator
            ')'     => '',
            '/'     => '',
            '-'     => '',
            '+'     => '00'            // normalize foreign numbers
        ]
    ]
];
