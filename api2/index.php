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



## DDNS via POST
$app->post('/ddns', function (Request $request, Response $response) {
	
	global $PDNS, $AUTH;
	
	$params = $request->getParsedBody();


	$status = $PDNS->do_ddns($params);


	$response->getBody()->write($status);
	return $response->withHeader('Content-Type', 'text/plain');

});


## DDNS via GET
$app->get('/ddns[/{get_params:.*}]', function (Request $request, Response $response, $args) {
	
	global $PDNS, $AUTH;
	
	$params = array();
	$get_params = explode('/', $args['get_params']);
	

	foreach ($get_params as $kv_pair) {
		if (preg_match('/^(.+)=(.+)$/', $kv_pair, $kv_ary)) {
			$key = $kv_ary[1];
			$val = $kv_ary[2];
			$params[$key] = $val;
		}
	}
	

	$status = $PDNS->do_ddns($params);

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



$app->post('/remove_record', function (Request $request, Response $response) {
	
	global $PDNS, $AUTH;
	
	if ($AUTH->check_auth($request)['auth_status'] === true) {
	
		$params = $request->getParsedBody();
		
		$PDNS->remove_record($params['zone'], $params['type'], $params['name']);

		
		$payload = json_encode(['default' => true]);
	
	} else {
		
		$payload = json_encode(['auth_status' => false]);
		
	}
	
	
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