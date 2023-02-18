<?php

// Prevent direct access to this file
if (!defined('ABSPATH')) exit;

class Swift_Train_Webp_Converter_Activator
{
	/** @var array */
	const REQUIRED_FUNCTIONS = [
		'exif_imagetype',
		'imagecreatefromjpeg',
		'imagecreatefrompng',
		'imagewebp',
		'imagedestroy',
	];

	/**
	 * Swift_Train_Webp_Converter_Activator constructor.
	 */
	public function __construct() {
		// Check if GD extension is loaded
		if (!extension_loaded('gd')) {
			wp_die(__('The Swift Train WebP Converter plugin requires the PHP GD extension to be enabled on your server. Please enable it and try again.', SWIFT_TRAIN_WEBP_CONVERTER_SLUG));
		}

		// Register the activation hook
		register_activation_hook(__FILE__, array($this, 'activate'));
	}

	/**
	 * Activate
	 */
	public function activate() {
		// Check if all required functions exist
		foreach (self::REQUIRED_FUNCTIONS as $function) {
			if (!function_exists($function)) {
				wp_die(sprintf(__('The Swift Train WebP Converter plugin requires the %s function to be available on your server. Please check your PHP installation and try again.', SWIFT_TRAIN_WEBP_CONVERTER_SLUG), $function));
			}
		}
	}
}

// Instantiate the activator
new Swift_Train_Webp_Converter_Activator();
