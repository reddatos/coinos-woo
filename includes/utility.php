<?php

if (!defined('ABSPATH')) {
    die;
}

class WC_Utility_CoinosWoo
{
	// Generate the API URL for the Coinos API.
	public function api_url($endpoint)
	{
		$api_base_url = 'https://coinos.io/api';
		return $api_base_url . $endpoint;
	}

	// Convert decimal amount to satoshi amount.
	public function to_satoshi($amount)
	{
		return $amount * 100000000;
	}

	// Convert satoshi amount to decimal amount.
	public function to_decimal($amount)
	{
		return $amount / 100000000;
	}
}