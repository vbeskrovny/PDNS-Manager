<?php


## empty or URL where the main API index.php app is located. If full url is: 'https://example.com/pdns/api' , then URL_PREFIX is '/pdns/api'
define('URL_PREFIX', '/api2');							


## Authentication bruteforce protection. Requires: memcached (server + php module)
define('MEMCACHED_KEY_PREFIX', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX-');
define('LOGIN_MAX_TRIES', 3);
define('LOGIN_BLOCK_TIME', 300);


## OTP enabled/disabled
define('OTP_ENABLED', true);


## periodical checks (in seconds)
define('AUTH_INTERVAL', 300);														


## when the auth will expire (if not renewed) => 3x AUTH_INTERVAL (in seconds)
define('AUTH_LIFE', 900);																


## Replace with your own by running: php -f ./auth_helper.php
define('AUTH_KEY', 'def0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000936a8ed840e6b8cdcf2c41e89331dabf7b1a13b51bcc93133f5ff5d31a36fe5146720e4390c9e98376e1ade741dc7dd8834d3cb6ea98b7bd8a29c6ef315ea42f');


## PDNS Api key
define('API_KEY', '00000000000000000000000000000000');


define('ZONE_DEFAULTS', [
		'dns' =>					'dns1.mydns.com.',
		'hostmaster' =>		'hostmaster.mydns.com.',
		'nameservers' =>	['dns1.mydns.com.', 'dns2.mydns.com.'],
		'masters' =>			['dns1.mydns.com.'],
		'refresh' =>			14400,
		'retry' =>				3600,
		'expire' =>				604800,
		'ttl' =>					3600,
	]);

