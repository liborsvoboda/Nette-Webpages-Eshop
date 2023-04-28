<?php

namespace App\Model\Services;

use Exception;

class EkosystemService {

    /** @var string */
    private $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function loadData(string $ic)
    {
        // set_error_handler(
		// 	function ($severity, $message, $file, $line) {
		// 		throw new Exception($message);
		// 	}
		// );

		$url = 'https://autoform.ekosystem.slovensko.digital/api/corporate_bodies/search?q=cin:' . $ic . '&private_access_token=' . $this->apiKey;
		$json = file_get_contents($url);
		if ($json === '') throw new Exception('Not found', 404);
		$obj = json_decode($json);
		if ($obj === FALSE) throw new Exception(json_last_error());
		return $obj[0];
    }
}