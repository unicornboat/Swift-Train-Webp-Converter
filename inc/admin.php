<?php
/**
 * Admin file for the Swift Train Webp Converter plugin.
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) exit;

/**
 * The admin class for the Swift Train Webp Converter plugin.
 */
class Swift_Train_Webp_Converter_Admin
{
	/** @var array */
	private $allowedFormats = ['jpg', 'png'];

	/** @var int */
	private $deleteSource = 0;

	/** @var int */
	private $quality = 60;

    /**
	 * Initializes the admin class.
	 */
	public function __construct()
	{
		add_action('admin_menu', [$this, 'registerSettingsPage']);
		add_action('wp_ajax_swift_train_webp_converter_save_settings', [$this, 'saveSettings']);
	}

	/**
	 * Get plugin settings
	 *
	 * @return array
	 */
	public function getSettings(): array
	{
		$settings = get_option('swift_train_webp_converter_settings');

		// Default value for source_format
		if (!isset($settings['source_format']) || is_null($settings['source_format'])) {
			$settings['source_format'] = $this->allowedFormats;
		}

		// Default value for delete_source_file
		if (!isset($settings['delete_source_file']) || is_null($settings['delete_source_file'])) {
			$settings['delete_source_file'] = $this->deleteSource;
		}

		// Default value for quality
		if (!isset($settings['quality']) || is_null($settings['quality'])) {
			$settings['quality'] = $this->quality;
		}

		return $settings;
	}

	/**
	 * Registers the plugin's settings page.
	 */
	public function registerSettingsPage()
	{
		add_submenu_page(
			'options-general.php',
			SWIFT_TRAIN_WEBP_CONVERTER_NAME,
			SWIFT_TRAIN_WEBP_CONVERTER_NAME,
			'manage_options',
			SWIFT_TRAIN_WEBP_CONVERTER_SLUG,
			[$this, 'renderSettingsPage']
		);

		add_filter('plugin_action_links_' . SWIFT_TRAIN_WEBP_CONVERTER_BASENAME, function ($links) {
			$newLinks = [
				'<a href="' . get_admin_url(null, 'options-general.php?page=' . SWIFT_TRAIN_WEBP_CONVERTER_SLUG) . '">' . __('Settings', SWIFT_TRAIN_WEBP_CONVERTER_SLUG) . '</a>',
			];
			foreach ($links as $link) {
				$newLinks[] = $link;
			}

			return $newLinks;
		});
	}

	/**
	 * Load the template file
	 */
	public function renderSettingsPage()
	{
		$formats = $this->allowedFormats;
		$quality = $this->quality;

		// Load the saved settings or use default values
		$settings = $this->getSettings();

		// Load the template file
		$template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/settings-page.php';

		// Load the stylesheet
		wp_enqueue_style('swift-train-webp-converter-settings', plugin_dir_url(dirname(__FILE__)) . 'css/settings.css');

		// Include the template file
		include $template_path;
	}

	/**
	 * Save settings
	 */
	public function saveSettings()
	{
        // Get only required fields
        $requiredKeys = array('source_format', 'conversion_quality', 'delete_source_file');
        $settings = array_intersect_key($_POST, array_flip($requiredKeys));

        // Check if the source format values are valid
        $formats = array_map(function ($format) {
            return sanitize_text_field(strtolower(str_replace(' ', '', $format)));
        }, !empty( $settings['source_format'] ) ? (array) $settings['source_format'] : array());
        $formats = array_intersect($formats, $this->allowedFormats);
        if (empty($formats)) {
            wp_send_json_error(__('Invalid source format values.', SWIFT_TRAIN_WEBP_CONVERTER_SLUG));
            return;
        }

        // Sanitize and validate the quality setting
        $quality = intval(sanitize_text_field($settings['conversion_quality']));
        if ($quality < 10 || $quality > 100) {
            wp_send_json_error(__('Invalid quality setting.', SWIFT_TRAIN_WEBP_CONVERTER_SLUG));
            return;
        }

        // Sanitize and validate the delete source file setting
        $deleteSourceFile = isset($settings['delete_source_file']) ? intval($settings['delete_source_file']) : 1;
        if ($deleteSourceFile !== 1 && $deleteSourceFile !== 0) $deleteSourceFile = 1;

		// Save the data
		update_option('swift_train_webp_converter_settings', [
			'source_format'      => $formats,
			'quality'            => $quality,
			'delete_source_file' => $deleteSourceFile,
		]);

		// Return a success response
		wp_send_json_success();
	}
}
