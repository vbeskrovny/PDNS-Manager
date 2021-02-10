<?php


use GuzzleHttp\Client;



class PDNS_Helper {
	
	private $client;
	
	function __construct() {
		$this->client = new GuzzleHttp\Client(['base_uri' => 'http://localhost:8081/api/v1/']);
	}
	
	
	function content_filter_test($str = null) {
		return $str;
	}
	
	
	function prepare($var) {
		
		if (is_string($var)) {
			
			$var = trim($var);

			if (!preg_match('/\.$/', $var)) {
				$var = $var . '.';
			}

		} else if (is_array($var)) {
			
			// 2DO
			
		}
		
		return $var;
	}
	
	
	
	function do_ddns($params) {
		
		$status = '+OK';
		$remove = false;		## Do we want to remove the record?
		$all_valid = true;	## We are optimistic and think that everything is good by default :)))
		$valid_params = array('keep' => 0, 'token' => null, 'zone' => null, 'type' => 'A', 'name' => null, 'content' => null, 'ttl' => 60);
		
		

		## Assign URL params to the internal (valid_params) parameters array
		foreach ($params as $key => $val) {
			if (array_key_exists($key, $valid_params)) {
				
				switch ($key) {
					case 'zone':
					case 'name':
						$val = $this->prepare($val);
					break;
					case 'type':
						$val = strtoupper($val);
					break;
				}
				

				$valid_params[$key] = $val;
			
			}
		}
		
		

		## Validate params
		foreach ($valid_params as $key => $val) {
			if ($val === null) {
				if ($key == 'zone') {
					if ($valid_params['name'] !== null) {
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


			## Check if the content is (str)'null' -> remove then...
			foreach($params['content'] as $idx => $val) {
				if ($val == 'null') {
					
					$this->remove_record($params['zone'], $params['type'][0], $params['name'][0]);
					return $status;

				}
			}


			
			
			// If we have to keep the existing records -> append the existing records (do not forget to apply filters) + do not use any 'content' fitlers
			if ($valid_params['keep']) {
			
				// $existing_records = array_values(json_decode($this->get_records($params['zone'], ['type' => $params['type'][0], 'name' => $params['name'][0], 'content' => 'content_filter_test']), true));
				// $existing_records = array_values(json_decode($this->get_records($params['zone'], ['type' => $params['type'][0], 'name' => $params['name'][0], 'content' => 'htmlspecialchars']), true));
				$existing_records = array_values(json_decode($this->get_records($params['zone'], ['type' => $params['type'][0], 'name' => $params['name'][0], 'content' => null]), true));
				

				foreach ($existing_records[0]['records'] as $record_ary) {
					$params['type'][] = $params['type'][0];
					$params['name'][] = $params['name'][0];
					
					
					if ($params['content'][0] == $record_ary['content']) {	// If the content is already there -> do nothing, just return the '+OK' status code
						return $status;
					} else {
						$params['content'][] = $record_ary['content'];
					}
					
					
					$params['ttl'][] = $params['ttl'][0];
				}
				
				
			

			}



			$this->save_records($params);
			

		} else {
			$status = '-ERR';
		}
		
		
		
		return $status;

	}
	
	
	
	
	function remove_zone($zone) {
		
		$request_url = 'servers/localhost/zones/' . $this->prepare($zone);
		$res = $this->client->request('DELETE', $request_url, ['headers' => ['X-API-Key' => API_KEY]]);

	}
	
	
	function remove_record($zone, $type, $name) {
		
		$zone = $this->prepare($zone);
		
		$change_ary = [
			array(
				'changetype' => 'DELETE',
				'name' => $name,
				'type' => $type,
				'records' => []
			)
		];
		


		// UPDATE
		$res = $this->client->request('PATCH', 'servers/localhost/zones/' . $zone, [
			'debug' => false, 
			'headers' => ['X-API-Key' => API_KEY],
			'json' => ['rrsets' => array_values($change_ary)]
		]);

		
		// NOTIFY
		$res = $this->client->request('PUT', 'servers/localhost/zones/' . $zone . '/notify', ['debug' => false, 'headers' => ['X-API-Key' => API_KEY]]);
		
		
	}
	
	
	function save_records($params) {
		

		$zone = $this->prepare($params['zone']);
		
		$change_ary = array();
		
		
		foreach ($params['type'] as $idx => $type) {

			$name = $params['name'][$idx];
			
			$key = md5($name . ':' . $type);
			
			$change_ary[$key]['changetype'] = 'REPLACE';
			$change_ary[$key]['name'] = $this->prepare($name);
			$change_ary[$key]['type'] = $type;
			$change_ary[$key]['ttl'] = $params['ttl'][$idx];
			$change_ary[$key]['records'][] = [ 'content' => urldecode($params['content'][$idx]), 'disabled' => false ];

		}
		
		
		// UPDATE
		$res = $this->client->request('PATCH', 'servers/localhost/zones/' . $zone, [
			'debug' => false, 
			'headers' => ['X-API-Key' => API_KEY],
			'json' => ['rrsets' => array_values($change_ary)]
		]);
		
		
		// ADD SERIAL TRIGGER
		$res = $this->client->request('PATCH', 'servers/localhost/zones/' . $zone, [
			'debug' => false, 
			'headers' => ['X-API-Key' => API_KEY],
			'json' => [
				'rrsets' => [
					[
						'changetype' => 'REPLACE',
						'name' => 'serial-trigger.' . $zone,
						'type' => 'TXT',
						'ttl' => 60,
						'records' => [
							[ 'content' => '"serial-trigger-'.date('Y-m-d H:i:s').'"', 'disabled' => false ]
						]
					]
				]
			]
		]);

		
		// REMOVE SERIAL TRIGGER
		$res = $this->client->request('PATCH', 'servers/localhost/zones/' . $zone, [
			'debug' => false, 
			'headers' => ['X-API-Key' => API_KEY],
			'json' => [
				'rrsets' => [
					[
						'changetype' => 'DELETE',
						'name' => 'serial-trigger.' . $zone,
						'type' => 'TXT'
					]
				]
			]
		]);
		
		

		// NOTIFY
		$res = $this->client->request('PUT', 'servers/localhost/zones/' . $zone . '/notify', ['debug' => false, 'headers' => ['X-API-Key' => API_KEY]]);
		
		
		
	}
	
	
	function add_zone($params) {
		
		$serial = date('YmdH');
		
		$zone = $this->prepare($params['zone']);
		$dns = $this->prepare($params['dns']);
		$hostmaster = $this->prepare($params['hostmaster']);
		$nameservers = $this->prepare($params['nameservers']);
		$masters = $this->prepare($params['masters']);
		$refresh = $params['refresh'];
		$retry = $params['retry'];
		$expire = $params['expire'];
		$ttl = $params['ttl'];
		
		
		// CREATE
		$res = $this->client->request('POST', 'servers/localhost/zones', [
			'debug' => false,
			'headers' => ['X-API-Key' => API_KEY],
			'json' => [
				'name' => $zone,
				'kind' => 'master',
				'nameservers' => $nameservers,
				'rrsets' => [
						[
							'name' => $zone,
							'records' => [
								['content' => sprintf('%s %s %s %s %s %s %s', $dns, $hostmaster, $serial, $refresh, $retry, $expire, $ttl), 'disabled' => false]
							],
							'type' => 'SOA',
							'ttl' => $ttl
						]
					],
				'masters' => $masters,
				'dnssec' => false,
				'soa-edit' => 'INCEPTION-INCREMENT',
			]
		]);
		
		
		// NOTIFY
		$res = $this->client->request('PUT', 'servers/localhost/zones/' . $zone . '/notify', ['debug' => false, 'headers' => ['X-API-Key' => API_KEY]]);

	}
	
	

	function get_zones() {
		
		$zones = array();
		$request_url = 'servers/localhost/zones';
		
		$res = $this->client->request('GET', $request_url, ['debug' => false, 'headers' => ['X-API-Key' => API_KEY]]);
		$json = json_decode(sprintf('%s', $res->getBody()), true);
		
		
		
		foreach ($json as $zone) {
			$zones[] = $zone['name'];
		}
		
		

		return json_encode($zones);
		
	}
	
	
	function get_records($zone, $filters = null) {
		
		
		$request_url = 'servers/localhost/zones/' . $this->prepare($zone);
		
		$res = $this->client->request('GET', $request_url, ['debug' => false, 'headers' => ['X-API-Key' => API_KEY]]);
		$body = json_decode(sprintf('%s', $res->getBody()), true);
		
		foreach($body['rrsets'] as $rrset_idx => $rrset_ary) {
			
			
			$include = true;
			
			// Filters check section
			if ($filters && array_key_exists('type', $filters) && $filters['type'] != $rrset_ary['type']) { $include = false; }
			if ($filters && array_key_exists('name', $filters) && $filters['name'] != $rrset_ary['name']) { $include = false; }
			

			
			if ($include) {
			
				foreach ($rrset_ary['records'] as $record_idx => $record_ary) {
					
					
					if ($filters && array_key_exists('content', $filters)) {	// Content filter enabled?
						if ($filters['content']) {	// Yes, enabled -> will use the provided content filter callback
							$user_defined_content_filter = $filters['content'];
							if (function_exists($user_defined_content_filter)) {	// Invoking the existing function: e.g. htmlspecialchars
								$record_ary['content'] = call_user_func($user_defined_content_filter, $record_ary['content']);
							} else {	// Invoking user defined function: e.g. $this->content_filter_test('foo')
								$record_ary['content'] = call_user_func('\PDNS_Helper::'.$filters['content'], $record_ary['content']);
							}
						} else {	// No -> fetch content as is
							$record_ary['content'] = $record_ary['content'];
						}
					} else {	// No content filtering -> apply htmlspecialchars by default
						$record_ary['content'] = htmlspecialchars($record_ary['content']);
					}
					
					
					$rrset_ary['records'][$record_idx] = $record_ary;
				}
				$body['rrsets'][$rrset_idx] = $rrset_ary;
				
			} else {
				
				unset($body['rrsets'][$rrset_idx]);

			}

		}
		
		return json_encode($body['rrsets']);

	}
	
	
	
}

