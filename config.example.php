<?php

$config = [
	// phonebook
	'phonebook' => [
		'id' => 0,
		'name' => 'Telefonbuch'
	],

	// or server
	'server' => [
		[
			'url' => 'https://...',
			'user' => '',
			'password' => '',
			],								/* add as many as you need
		[
			'url' => 'https://...',
			'user' => '',
			'password' => '',
			],								*/
		],

		
	// or fritzbox
	'fritzbox' => [
		'url' => 'fritz.box',               // or change it to your routers IP address
		'user' => 'dslf-config',            // default user; change name if you are using a dedicated user 
		'password' => '',
	],

	'fritzadrpath' => [                     // if not empty FRITZadr Database will be written to this location
		'../FRITZ/mediabox/FritzAdr.dbf'	// suggestion: save to accessible memory on the Fritz!Box
	],
	
    'filters' => [
        'include' => [                       // if empty include all by default
        ],

        'exclude' => [
            'categories' => ['A', 'B'
            ],
			'group' => [
                'C', 'D'
            ],
        ],
    ],

	'conversions' => [
		'vip' => [
			'categories' => ['VIP'
			],
		],
		'realName' => [
			'{lastname}, {prefix} {nickname}',
			'{lastname}, {prefix} {firstname}',
			'{lastname}, {nickname}',
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
			'other'	  => 'work'
		],
		'emailTypes' => [
			'WORK' => 'work',
			'HOME' => 'home'
		],
		'phoneReplaceCharacters' => [
            '+491'	=> '01',
            '+492'  => '02',
            '+493'  => '03',
            '+494'  => '04',
            '+495'  => '05',
            '+496'  => '06',
            '+497'  => '07',
            '+498'  => '08',
            '+499'  => '09',
			'+49 1'	=> '01',
            '+49 2'	=> '02',
            '+49 3'	=> '03',
            '+49 4'	=> '04',
            '+49 5'	=> '05',
            '+49 6'	=> '06',
            '+49 7'	=> '07',
            '+49 8'	=> '08',
            '+49 9'	=> '09',
			'+49'	=> '',
            '('		=> '',
            ')'		=> '',
			'/'		=> '',
			'-'		=> '',
			'+'		=> '00'
		]	
	]
];
