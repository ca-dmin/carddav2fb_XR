# CardDAV contacts import for AVM FRITZ!Box

Purpose of the software is the (automatic) uploading of contact data from CardDAV servers as a phone book into an AVM Fritz! Box.

This is an extendeded version of https://github.com/andig/carddav2fb. Changes are:

  * the phone book can be built from more than one source
  * FAX numbers of selected contacts can be written as FritzAdr.dbf file for Fritz!FAX (fax4box)
  * the parser file has been added to include nicknames
  * the config example file was supplemented with recommendable default values

## Requirements

  * PHP 7.0 (`apt-get install php7.0 php7.0-cli php7.0-curl php7.0-mbstring php7.0-xml`)
  * Composer (follow the installation guide at https://getcomposer.org/download/)
  * if you want to write a FritzAdr.dbf: dBase library for php 7.0

## Installation

Install carddav2fb:

    cd /
    git clone https://github.com/andig/carddav2fb.git
    cd carddav2fb
    
Install composer (see https://getcomposer.org/download/ for newer instructions):

    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    mv composer.phar /usr/local/bin/composer
    composer install

If you want to use the DBF-file output you must install the dBase library (see instructions at https://github.com/mote0230/dbase-pecl-php7).
After compiling the `dbase.so` on your system you will find it in `/usr/lib/php/20151012`

    sudo nano /etc/php/7.0/mods-available/dbase.ini
  add
  
    extension=dbase.so
  save file
  
    sudo ln -s /etc/php/7.0/mods-available/dbase.ini /etc/php/7.0/fpm/conf.d/20-dbase.ini
    sudo ln -s /etc/php/7.0/mods-available/dbase.ini /etc/php/7.0/cli/conf.d/20-dbase.ini
    cd /etc/init.d
    service apache2 restart

Edit `config.example.php` and save as `config.php` or use an other name of your choice (but than keep in mind to use the -c option to define your renamed file)

## Usage

List all commands:

    php carddav2fb.php run -h

Complete processing:

    php carddav2fb.php run

Get help for a command:

    php carddav2fb.php download -h


## License
This script is released under Public Domain, some parts under MIT license. Make sure you understand which parts are which.

## Authors
Copyright (c) 2012-2018 Christian Putzke, Gregor Nathanael Meyer, Karl Glatz, Jan-Philipp Litza, Jens Maus, Andreas Götz, Volker Püschel and maybe others
