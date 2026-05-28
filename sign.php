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

// Handle command line vs HTTP
if (is_cli()) {
	// Check for minimum command line arguments
	if ($argc < 3) {
		echo "Usage: $0 <source> <destination> [attest_level]\n";
		echo "Example: $0 1234567890 0987654321 A\n";
		exit(1);
	}

	// Get the command line arguments
	$source = $argv[1];
	$destination = $argv[2];
	$attest_level = $argv[3] ?? 'A';
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

	// Get the HTTP request values
	$source_number = $_REQUEST['source'] ?? '';
	$destination_number = $_REQUEST['destination'] ?? '';
	$attest_level = $_REQUEST['attest_level'] ?? 'A';
}

// Sanitize and clean the source and destination
$source_number = preg_replace('/[^0-9]/', '', $source_number);
$destination_number = preg_replace('/[^0-9]/', '', $destination_number);

// Initialize the identity object
$identity = new identity($setting_array);

// Sign a call
$result = $identity->sign(
	$source_number,        // Source phone number
	$destination_number,   // Destination phone number
	$attest_level          // Attestation level: A, B, or C
);

// Respond with an error if the request failed
if (!empty($result['error'])) {
	if ($debug != 'false' && is_cli()) {
		echo $result['error'];
		return;
	}
	echo 'TN-Validation-Failed';
	return;
}

// Get the PASSporT token
$passport = $result['passport'];

// Build and return the SIP Identity header
echo $identity->build_identity_header($passport);
