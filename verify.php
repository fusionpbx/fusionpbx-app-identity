<?php

// Include functions
require_once dirname(__DIR__, 2) . "/app/identity/resources/functions.php";

// Class auto loader
if (!class_exists('auto_loader')) {
	require_once dirname(__DIR__, 2) . "/resources/classes/auto_loader.php";
	$autoload = new auto_loader();
}

// Load config file
global $config;
$config = config::load();

// Get the config settings
$debug = $config->get('identity.debug', 'false');
$identity_path = $config->get('identity.path', '/etc/fusionpbx/certs');
$allowed_cidr = $config->get('identity.allowed_cidr', '127.0.0.1/32');
$setting_array['private_key_path'] = $identity_path . '/' . $config->get('identity.private_key_name', 'identity_private.key');
$setting_array['certificate_path'] = $identity_path . '/' . $config->get('identity.certificate_name', 'identity_certificate.pem');
$setting_array['certificate_url'] = $config->get('identity.certificate_url', '');
$setting_array['debug'] = $debug;

// Get the identity header
if (is_cli()) {
	$identity_header = $argv[1] ?? '';
}
else {
	// Get the client's IP address
	$remote_address = $_SERVER['REMOTE_ADDR'];
	if (empty($remote_address)) {
		return;
	}

	// Check if the CIDR is allowed
	$cidr_array = explode(',', $allowed_cidr);
	if (!check_cidr($cidr_array, $remote_address)) {
		return;
	}

	// Get the variables
	$identity_header = $_REQUEST['identity'] ?? '';
}

// Empty identity header
if (empty($identity_header)) {
	echo 'No-TN-Validation';
}

// Initialize the identity object
$identity = new identity($setting_array);

//$result = $identity->verify($identity_header);
$result = $identity->verify($identity_header);
if ($result === true) {
	echo 'TN-Validation-Passed-' . $identity->attest_level;
} else {
	if ($debug != 'false') {
		echo "PASSporT verification error " . $identity->error_message;
		return;
	}
	echo 'TN-Validation-Failed';
}
