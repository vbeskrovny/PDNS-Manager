<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;


require __DIR__ . '/../vendor/autoload.php';


require __DIR__ . '/credentials.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/auth_helper.php';
require __DIR__ . '/pdns_helper.php';


$PDNS = new PDNS_Helper();
$AUTH = new AUTH_Helper();



$app = AppFactory::create();
$app->setBasePath(URL_PREFIX);


$app->get('/test[/{something}]', function (Request $request, Response $response, $args) {
	
	global $PDNS, $AUTH;
	
	
	// $payload = json_encode([$AUTH->test($args['something'])]);
	$payload = json_encode($AUTH->check_auth($request)['auth_status']);
	// $payload = json_encode($AUTH->generate_key());
	
	
	$response->getBody()->write($payload);
	return $response->withHeader('Content-Type', 'application/json');
	
});





$app->post('/signup', function (Request $request, Response $response) {
	
	global $AUTH;
	
	$params = $request->getParsedBody();


	$password_hash = md5($params['password']);
	list($otp_secret, $otp_url, $otp_qr) = $AUTH->signup($params['username']);

	
	$payload = json_encode(['password_hash' => $password_hash, 'otp_secret' => $otp_secret, 'otp_url' => $otp_url, 'otp_qr' => $otp_qr ]);


	$response->getBody()->write($payload);
	return $response->withHeader('Content-Type', 'application/json');	


});




$app->get('/check_auth', function (Request $request, Response $response, $args) {
	
	global $AUTH;
	
	
	list($auth_status, $auth_cookie) = $AUTH->check_auth($request);


	$payload = json_encode(['auth_status' => $auth_status, 'auth_cookie' => $auth_cookie]);


	

	$response->getBody()->write($payload);
	return $response->withHeader('Content-Type', 'application/json');	
	
	
});



$app->post('/do_auth', function (Request $request, Response $response) {
	
	global $AUTH;
	
	$params = $request->getParsedBody();

	$auth_status = false;
	$auth_cookie = null;
	
	if (array_key_exists('username', $params) && array_key_exists('password', $params) && array_key_exists('otp', $params)) {

		list($auth_status, $auth_cookie) = $AUTH->do_auth($params['username'], $params['password'], $params['otp']);

	}
	
	
	
	$payload = json_encode(['auth_status' => $auth_status, 'auth_cookie' => $auth_cookie]);


	$response->getBody()->write($payload);
	return $response->withHeader('Content-Type', 'application/json');	


});





$app->get('/ddns[/{get_params:.*}]', function (Request $request, Response $response, $args) {
	
	global $PDNS, $AUTH;
	
	$status = '+OK';
	$all_valid = true;	## We are optimistic and think that everything is good by default :)))
	$valid_params = array('token' => null, 'zone' => null, 'type' => 'A', 'name' => null, 'content' => null, 'ttl' => 60);
	
	
	$get_params = explode('/', $args['get_params']);
	
	
	## Assign URL params to the internal (valid_params) parameters array
	foreach ($get_params as $kv_pair) {
		if (preg_match('/^(.+)=(.+)$/', $kv_pair, $kv_ary)) {
			
			$key = $kv_ary[1];
			$val = $kv_ary[2];
			
			if (array_key_exists($key, $valid_params)) {
				
				switch ($key) {
					case 'zone':
					case 'name':
						$val = $PDNS->prepare($val);
					break;
					case 'type':
						$val = strtoupper($val);
					break;
				}
				

				$valid_params[$key] = $val;
			
			}
		
		}
	}
	
	
	
	## Validate params
	foreach ($valid_params as $key => $val) {
		if ($val == null) {
			if ($key == 'zone') {
				if ($valid_params['name'] != null) {
					$valid_params['zone'] = explode((explode('.', $valid_params['name'])[0]).'.', $valid_params['name'], 2)[1];
				}
			} else {
				$all_valid = false;
			}
		}
	}
	
	
	if ($all_valid && array_key_exists($valid_params['token'], DDNS_TOKENS)) {
		
		$params = array(
			'zone' => $valid_params['zone'],
			'type' => array($valid_params['type']),
			'name' => array($valid_params['name']),
			'content' => array($valid_params['content']),
			'ttl' => array($valid_params['ttl'])
		);
		
		$PDNS->save_records($params);
		
		
	} else {
		$status = '-ERR';
	}
	
	

	$response->getBody()->write($status);
	return $response->withHeader('Content-Type', 'text/plain');

	
});



$app->post('/globals_init', function (Request $request, Response $response) {
	
	global $PDNS, $AUTH;
	
	$settings = array();
	
	
	if ($AUTH->check_auth($request)['auth_status'] === true) {
		
		$settings['AUTH_INTERVAL'] = AUTH_INTERVAL;
		$settings['ZONE_DEFAULTS'] = ZONE_DEFAULTS;

	}
	
	
	$payload = json_encode($settings);
	
	
	$response->getBody()->write($payload);
	return $response->withHeader('Content-Type', 'application/json');	
	
	
});





$app->post('/save_records', function (Request $request, Response $response) {
	
	global $PDNS, $AUTH;
	
	if ($AUTH->check_auth($request)['auth_status'] === true) {
	
		$params = $request->getParsedBody();
	
		$PDNS->save_records($params);

		
		$payload = json_encode(['default' => true]);
	
	} else {
		
		$payload = json_encode(['auth_status' => false]);
		
	}
	
	
	$response->getBody()->write($payload);
	return $response->withHeader('Content-Type', 'application/json');	


});



$app->post('/remove_zone/{zone}', function (Request $request, Response $response, $args) {
	
	global $PDNS, $AUTH;


	if ($AUTH->check_auth($request)['auth_status'] === true) {
	
		$PDNS->remove_zone($args['zone']);

		
		$payload = json_encode(['default' => true]);
	
	} else {
		
		$payload = json_encode(['auth_status' => false]);
		
	}


	$response->getBody()->write($payload);
	return $response->withHeader('Content-Type', 'application/json');	
	
});




$app->post('/add_zone', function (Request $request, Response $response) {
	
	global $PDNS, $AUTH;
	
	
	if ($AUTH->check_auth($request)['auth_status'] === true) {
	
		$params = $request->getParsedBody();
		
		$PDNS->add_zone($params);
		
		$payload = json_encode(['default' => true]);
	
	} else {
		
		$payload = json_encode(['auth_status' => false]);
		
	}
	
	
	$response->getBody()->write($payload);
	return $response->withHeader('Content-Type', 'application/json');	
	
	
});


$app->post('/get_zones', function (Request $request, Response $response, $args) {
	
	global $PDNS, $AUTH;
	
	
	if ($AUTH->check_auth($request)['auth_status'] === true) {
	
		$payload = $PDNS->get_zones();
	
	} else {
		
		$payload = json_encode(['auth_status' => false]);
		
	}


	$response->getBody()->write($payload);
	return $response->withHeader('Content-Type', 'application/json');	

});



$app->post('/get_records/{zone}', function (Request $request, Response $response, $args) {
	
	global $PDNS, $AUTH;
	
	if ($AUTH->check_auth($request)['auth_status'] === true) {
	
		$payload = $PDNS->get_records($args['zone']);
	
	} else {
		
		$payload = json_encode(['auth_status' => false]);
		
	}


	$response->getBody()->write($payload);
	return $response->withHeader('Content-Type', 'application/json');	
	
});



// DEFAULT - catch all
$app->get('/{path:.*}', function ($request, $response, array $args) {
	$payload = json_encode(['default' => true]);
	$response->getBody()->write($payload);
	return $response->withHeader('Content-Type', 'application/json');	
});




$app->run();