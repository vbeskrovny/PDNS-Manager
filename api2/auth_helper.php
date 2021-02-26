<?php

use Endroid\QrCode\QrCode;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;



class AUTH_Helper {
	
	private $key;

	function __construct() {
		$this->key = Key::loadFromAsciiSafeString(AUTH_KEY);
	}
	
	
	function test($something = null) {
		
		return $something;

	}
	
	
	function generate_key() {
		$key = Key::createNewRandomKey();
		return $key->saveToAsciiSafeString();
	}
	
	
	function encrypt($text)	{
		return Crypto::encrypt(sprintf('%s', $text), $this->key, $raw_binary = false);
	}
	
	function decrypt($text)	{
		return Crypto::decrypt(sprintf('%s', $text), $this->key, $raw_binary = false);
	}


	function check_auth($request) {
		
		$auth_status = false;

		
		
		$cookies = new \Slim\Psr7\Cookies($request->getCookieParams());
		$auth_cookie = $cookies->get('pdns_auth_cookie');
		
		if ($auth_cookie) {

			$auth_cookie_ary = json_decode($this->decrypt($auth_cookie), true);
			
			
			$ts = $auth_cookie_ary['ts'];
			$username = $auth_cookie_ary['username'];
			$password = $auth_cookie_ary['password'];
			
			
			## Check TS
			if (($ts + AUTH_LIFE + 1) >= time()) {	## TS is valid
				
				if (array_key_exists($username, AUTH_HASH) && AUTH_HASH[$username]['password'] == md5($password)) {
					
					$auth_status = true;
	
					$auth_cookie = 
						$this->encrypt(
							json_encode([
								'ts' => time(),
								'username' => $username,
								'password' => $password
							])
						);
	
				} else {
					
					$auth_cookie = null;
					
				}
				
				
			} else {
				
				$auth_cookie = null;
				
			}


			
		} else {
			
			$auth_cookie = null;
			
		}
		
		
		
		
		return [ $auth_status, $auth_cookie, 'auth_status' => $auth_status, 'auth_cookie' => $auth_cookie ];
		
	}
	
	
	
	function is_totp_ok($username, $otp) {
		if (array_key_exists('secret', AUTH_HASH[$username])) {
			if (AUTH_HASH[$username]['secret'] === null) {
				return true;
			} else {
				$totp = new \OTPHP\TOTP(null, AUTH_HASH[$username]['secret']);
				return $totp->verify($otp);
			}
		} else {
			return true;
		}
	}
	
	
	function signup($username) {
		
		if (class_exists('OTPHP\TOTP')) {
		
			$totp = new \OTPHP\TOTP();
			
			$otp_secret = $totp->getSecret();
			$totp = new \OTPHP\TOTP($username, $otp_secret);
			$otp_url = $totp->getProvisioningUri();
	
			
			$qrCode = new QrCode($otp_url);
			$otp_qr = $qrCode->writeDataUri();
	
	
			return [ $otp_secret, $otp_url, $otp_qr ];
		
		} else {
			
			return [ 'TOTP not installed', 'TOTP not installed', null ];
			
		}
		
	}


	
	function do_auth($username, $password, $otp) {
		
		$auth_status = false;
		$auth_cookie = null;
		
		if (array_key_exists($username, AUTH_HASH) && AUTH_HASH[$username]['password'] == md5($password) && $this->is_totp_ok($username, $otp) === true) {
			
			$auth_status = true;
			
			$auth_cookie = 
				$this->encrypt(
					json_encode([
						'ts' => time(),
						'username' => $username,
						'password' => $password
					])
				);
			
		}
		
		
		return [ $auth_status, $auth_cookie ];
		
	}

	
}


