<?php
/**
 * Plugin Name: Swift Train Webp Converter
 * Plugin URI:  https://unicornboat.com/wordpress/plugins/swift-train-webp-converter
 * Description: A WordPress plugin to convert images to WebP format.
 * Version:     1.0.0
 * Author:      Unicorn Boat
 * Author URI:  https://unicornboat.com
 * License:     GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: swift-train-webp-converter
 * Domain Path: /languages
 * Tested up to: 6.1.1
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) exit;

// Define constants
define('SWIFT_TRAIN_WEBP_CONVERTER_VERSION', '1.0.0');
define('SWIFT_TRAIN_WEBP_CONVERTER_NAME', 'Swift Train Webp Converter');
define('SWIFT_TRAIN_WEBP_CONVERTER_SLUG', 'swift-train-webp-converter');
define('SWIFT_TRAIN_WEBP_CONVERTER_BASENAME', plugin_basename(__FILE__));
define('SWIFT_TRAIN_WEBP_CONVERTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SWIFT_TRAIN_WEBP_CONVERTER_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once(SWIFT_TRAIN_WEBP_CONVERTER_PLUGIN_DIR . 'inc/activator.php');
require_once(SWIFT_TRAIN_WEBP_CONVERTER_PLUGIN_DIR . 'inc/admin.php');

// Register the activation hook
register_activation_hook(__FILE__, ['Swift_Train_Webp_Converter_Activator', 'activate']);

/**
 * The main plugin class for the Swift Train Webp Converter plugin.
 */
class Swift_Train_Webp_Converter
{

	/** @var \Swift_Train_Webp_Converter_Admin */
	private $admin;

	/**
	 * Initializes the plugin.
	 */
	public function __construct()
	{
		// Load the admin class
		$this->admin = new Swift_Train_Webp_Converter_Admin();

		add_action('plugins_loaded', [$this, 'loadTextDomain']);

		// Add filter to modify attachment metadata for image files
		add_filter('wp_generate_attachment_metadata', [$this, 'convert'], 10, 2);
	}

	/**
	 * Check all image files in the attachment then convert to webp format if applicable
	 *
	 * @param array $metadata
	 * @param int   $attachmentId
	 *
	 * @return array
	 */
	public function convert(array $metadata, int $attachmentId): array
	{
		try {
			$uploadDir = wp_upload_dir();
			$baseDir = $uploadDir['basedir'].DIRECTORY_SEPARATOR;
			$baseUrl = $uploadDir['baseurl'].DIRECTORY_SEPARATOR;

			// Convert the original image file
			$webpFilename = $this->convertImageToWebp($baseDir, $metadata['file']);
			$metadata['file'] = $webpFilename;

			// Update the guid
			wp_update_post(array('ID' => $attachmentId, 'guid' => $baseUrl.$webpFilename));

			// Update _wp_attached_file in wp_postmeta table
			update_post_meta($attachmentId, '_wp_attached_file', $webpFilename);

			foreach ($metadata['sizes'] as $size => &$data) {
				$sizeFilename = $this->convertImageToWebp($baseDir, $data['file']);
				$data['file'] = basename($sizeFilename);
			}

			// Update the metadata
			wp_update_attachment_metadata($attachmentId, $metadata);
		}
		catch (\Exception $e) {
//			var_dump($e->getMessage());die;
			error_log('['.SWIFT_TRAIN_WEBP_CONVERTER_NAME.' ERROR] ' . $e->getMessage());
		}

		// Return the updated metadata
		return $metadata;
	}

	/**
	 * Convert image file to webp format and delete the source file if applicable
	 *
	 * @param string $path
	 * @param string $filename
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function convertImageToWebp(string $path, string $filename): string
	{
		if (!file_exists($path . $filename)) {
			throw new Exception(sprintf(__('File %s does not exist!', SWIFT_TRAIN_WEBP_CONVERTER_SLUG), $path . $filename));
		}

		// Get conversion quality
		$settings = $this->admin->getSettings();
		$quality  = intval($settings['quality']);
		if ($quality < 10 || $quality > 100) $quality = 60;

		// Check if the attachment is a support image format
		$filetype = wp_check_filetype($filename);
		if (!in_array($filetype['ext'], $settings['source_format'])) {
			throw new Exception(sprintf(__('Image format %s is not supported!', SWIFT_TRAIN_WEBP_CONVERTER_SLUG), $filetype['ext']));
		}

		$source   = $path . $filename;
		$fileType = exif_imagetype($source);
		switch ($fileType) {
			case IMAGETYPE_JPEG:
				$image = imagecreatefromjpeg($source);
				break;
			case IMAGETYPE_PNG:
				$image = imagecreatefrompng($source);
				break;
			default:
				throw new Exception(sprintf(__('Image format %s is not supported!', SWIFT_TRAIN_WEBP_CONVERTER_SLUG), $filetype['ext']));
		}

		// Get the file basename (no extension)
		$fileBasename = pathinfo($filename, PATHINFO_FILENAME);

		// Convert the image to WebP
		$webpFilename = "$fileBasename.webp";
		if (!imagewebp($image, $path . $webpFilename, $quality)) {
			throw new Exception(sprintf(__('Failed to convert %s to webp format', SWIFT_TRAIN_WEBP_CONVERTER_SLUG), $filename));
		}

		// Free up memory
		imagedestroy($image);

		// Delete the source file to free disk space
		if ($settings['delete_source_file'] == 1) unlink($source);

		// Return the full path to the new WebP file
		return $webpFilename;
	}

	/**
	 * Load plugin textdomain
	 */
	public function loadTextDomain()
	{
		load_plugin_textdomain(SWIFT_TRAIN_WEBP_CONVERTER_SLUG, false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}
}

// Instantiate the main plugin class
new Swift_Train_Webp_Converter();
