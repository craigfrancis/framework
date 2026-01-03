<?php

	class connection_aws_base extends connection {

		//--------------------------------------------------
		// Variables

			protected $access_id = NULL;
			protected $access_secret = NULL;

			protected $service_ref = NULL;
			protected $service_region = NULL;
			protected $service_host = NULL;

		//--------------------------------------------------
		// Setup

			public function access_set($access_id, $access_secret = NULL) { // User details from IAM (Identity and Access Management)
				$this->access_id = $access_id;
				$this->access_secret = $access_secret;
// TODO [secret-keys]
			}

			public function service_set($service_ref, $service_region, $service_extra = NULL) {
				$this->service_ref = $service_ref;
				$this->service_region = $service_region;
				if ($service_ref == 's3') {
					$this->service_host = $service_extra . '.' . $service_ref . '-' . $service_region . '.amazonaws.com'; // e.g. 'bucket-name.s3-eu-west-1.amazonaws.com'
				} else {
					$this->service_host = $service_ref . '.' . $service_region . '.amazonaws.com'; // e.g. 'ec2.eu-west-1.amazonaws.com'
				}
			}

		//--------------------------------------------------
		// Signed request

			public function request($url, $method = 'GET', $data = '') {

				//--------------------------------------------------
				// Cleanup

					$request_headers = $this->headers_get();

					$this->reset();

					if (!$this->access_id)   exit_with_error('Missing call to $connection_aws->access_set()');
					if (!$this->service_ref) exit_with_error('Missing call to $connection_aws->service_set()');

				//--------------------------------------------------
				// URL

					//--------------------------------------------------
					// Parse

						if (!($url instanceof url)) {
							exit_with_error('When using "connection_aws", the URL must be provided a url() object.'); // Just so the path and query string can be easily extracted (separately)
						}

						$url_path = $url->path_get();
						$url_query = $url->params_get();

					//--------------------------------------------------
					// Query

						ksort($url_query); // Must be in ascending order

						$url_query = http_build_query($url_query, '', '&', PHP_QUERY_RFC3986); // Must use '%20' for spaces, not '+'

					//--------------------------------------------------
					// Final

						$url_final = 'https://' . $this->service_host . $url_path;
						if ($url_query) {
							$url_final .= '?' . $url_query;
						}

				//--------------------------------------------------
				// Time

					$request_date = gmdate('Ymd');
					$request_time = gmdate('Ymd\THis\Z');

				//--------------------------------------------------
				// Headers

					$request_headers = array_change_key_case($request_headers, CASE_LOWER); // Must be lowercase

					if (!isset($request_headers['content-type'])) {
						$request_headers['content-type'] = 'application/octet-stream';
					}

					$request_headers['date'] = $request_time;
					$request_headers['host'] = $this->service_host;

				//--------------------------------------------------
				// Data

					$data_hash = hash('sha256', $data); // Still hash an empty string (e.g. for GET)

					$request_headers['x-amz-content-sha256'] = $data_hash;

				//--------------------------------------------------
				// Headers

					ksort($request_headers);

					$headers_canonical = [];
					$headers_signed = [];
					$headers_send = [];

					foreach ($request_headers as $key => $value) {
						if ($key !== 'authorization') {
							$headers_canonical[] = $key . ':' . $value;
							$headers_signed[] = $key;
							if ($key !== 'host') {
								$headers_send[$key] = $value;
							}
						}
					}

					$headers_canonical = implode("\n", $headers_canonical) . "\n"; // "Add the canonical headers, followed by a newline character"
					$headers_signed = implode(';', $headers_signed);

				//--------------------------------------------------
				// Authorisation

						// https://docs.aws.amazon.com/AmazonS3/latest/API/sig-v4-header-based-auth.html
						// https://docs.aws.amazon.com/general/latest/gr/sigv4-create-canonical-request.html

					$canonical_request = implode("\n", [
							$method,
							$url_path,
							$url_query,
							$headers_canonical,
							$headers_signed,
							$data_hash,
						]);

					$scope = implode('/', [
							$request_date,
							$this->service_region,
							$this->service_ref,
							'aws4_request',
						]);

					$string_to_sign = implode("\n", [
							'AWS4-HMAC-SHA256',
							$request_time,
							$scope,
							hash('sha256', $canonical_request),
						]);

					$signing_key = 'AWS4' . $this->access_secret;
					$signing_key = hash_hmac('sha256', $request_date, $signing_key, true);
					$signing_key = hash_hmac('sha256', $this->service_region, $signing_key, true);
					$signing_key = hash_hmac('sha256', $this->service_ref, $signing_key, true);
					$signing_key = hash_hmac('sha256', 'aws4_request', $signing_key, true);

					$signature = hash_hmac('sha256', $string_to_sign, $signing_key);

					$authorisation = 'AWS4-HMAC-SHA256' . ' ' . implode(',', [
							'Credential=' . $this->access_id . '/' . $scope,
							'SignedHeaders=' . $headers_signed,
							'Signature=' . $signature,
						]);

					$headers_send['Authorization'] = $authorisation;

				//--------------------------------------------------
				// Headers (to send normally)

					$this->headers_set($headers_send);

				//--------------------------------------------------
				// Request

					return parent::request($url_final, $method, $data);

			}

	}

?>