<?php


## empty or URL where the main API index.php app is located. If full url is: 'https://example.com/pdns/api' , then URL_PREFIX is '/pdns/api2'
define('URL_PREFIX', '/pdns/api2');							


## periodical checks
define('AUTH_INTERVAL', 300);														


## when the auth will expire (if not renewed) => 3x AUTH_INTERVAL
define('AUTH_LIFE', 900);																


## AUTH_Helper()->generate_key();
define('AUTH_KEY', '0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000');


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

