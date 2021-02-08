<?php

use GuzzleHttp\Client;


class PDNS_Helper {
	
	private $client;
	
	function __construct() {
		$this->client = new GuzzleHttp\Client(['base_uri' => 'http://localhost:8081/api/v1/']);
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
	
	
	function remove_zone($zone) {
		
		$request_url = 'servers/localhost/zones/' . $this->prepare($zone);
		$res = $this->client->request('DELETE', $request_url, ['headers' => ['X-API-Key' => API_KEY]]);

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
	
	
	function get_records($zone) {
		
		
		$request_url = 'servers/localhost/zones/' . $this->prepare($zone);
		
		$res = $this->client->request('GET', $request_url, ['debug' => false, 'headers' => ['X-API-Key' => API_KEY]]);
		$body = json_decode(sprintf('%s', $res->getBody()), true);
		
		foreach($body['rrsets'] as $rrset_idx => $rrset_ary) {
			foreach ($rrset_ary['records'] as $record_idx => $record_ary) {
				$record_ary['content'] = htmlspecialchars($record_ary['content']);
				$rrset_ary['records'][$record_idx] = $record_ary;
			}
			$body['rrsets'][$rrset_idx] = $rrset_ary;
		}
		
		return json_encode($body['rrsets']);

	}
	
	
	
}

