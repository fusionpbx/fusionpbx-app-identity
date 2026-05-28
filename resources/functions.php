<?php

/**
 * Checks if the current script is running from the command line interface.
 *
 * @return bool True if running from CLI, false otherwise.
 */
function is_cli(): bool {
	if (defined('STDIN')) {
		return true;
	}
	if (php_sapi_name() == 'cli' && !isset($_SERVER['HTTP_USER_AGENT']) && is_numeric($_SERVER['argc'])) {
		return true;
	}
	return false;
}

/**
 * Checks if the $ip_address is within the range of the given $cidr
 * @param string|array $cidr
 * @param string $ip_address
 *
 * @return bool return true if the IP address is in CIDR or if it is empty
 */
function check_cidr($cidr, string $ip_address): bool {

	//no cidr restriction
	if (empty($cidr)) {
		return false;
	}

	//check to see if the user's remote address is in the cidr array
	if (is_array($cidr)) {
		//cidr is an array
		foreach ($cidr as $value) {
			if (check_cidr($value, $ip_address)) {
				return true;
			}
		}
	} else {
		//cidr is a string
		[$subnet, $mask] = explode('/', $cidr);
		return (ip2long($ip_address) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
	}

	//value not found in cidr
	return false;
}

/**
 * Checks if a given string is a valid UUID (Universally Unique Identifier).
 *
 * @param mixed $str The input string to be checked.
 *
 * @return bool True if the input string is a valid UUID, false otherwise.
 */
function is_uuid($str): bool {
    $is_uuid = false;
    if (gettype($str) == 'string') {
        if (substr_count($str, '-') != 0 && strlen($str) == 36) {
            $regex = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i';
            $is_uuid = preg_match($regex, $str);
        } else if (strlen(preg_replace("#[^a-fA-F0-9]#", '', $str)) == 32) {
            $regex = '/^[0-9A-F]{32}$/i';
            $is_uuid = preg_match($regex, $str);
        }
    }
    return $is_uuid;
}