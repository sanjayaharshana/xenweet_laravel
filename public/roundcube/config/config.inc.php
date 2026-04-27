<?php

$config = [];
$config['db_dsnw'] = 'sqlite:////Users/mac/Documents/Projects/xenweet_laravel/storage/app/roundcube-central/roundcube.sqlite?mode=0640';
$config['default_host'] = 'localhost';
$config['default_port'] = 143;
$config['smtp_server'] = 'localhost';
$config['smtp_port'] = 587;
$config['smtp_user'] = '%u';
$config['smtp_pass'] = '%p';
$config['product_name'] = 'Webmail';
$config['des_key'] = '07dd32479e7b1688710f4149';
$config['base_uri'] = '/roundcube/';
$config['plugins'] = ['archive', 'zipdownload'];
$config['skin'] = 'elastic';
$config['enable_installer'] = false;
$config['temp_dir'] = __DIR__ . '/../temp';
$config['log_dir'] = __DIR__ . '/../logs';