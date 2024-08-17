<?php
/**
 * Static functions used in the Templify Import Templates plugin.
 *
 * @package Templify Importer Templates
 */


 namespace TemplifyWP\TemplifyImporterTemplates;

 use function request_filesystem_credentials;
 if ( ! defined( 'ABSPATH' ) ) {
	 exit;
 }





/**
 * Class with static helper functions.
 */
class Helpers {
	/**
	 * Holds the date and time string for demo import and log file.
	 *
	 * @var string
	 */
	public static $demo_import_start_time = '';


    /**
	 * Append content to the file.
	 *
	 * @param string $content content to be saved to the file.
	 * @param string $file_path file path where the content should be saved.
	 * @param string $separator_text separates the existing content of the file with the new content.
	 * @return boolean|WP_Error, path to the saved file or WP_Error object with error message.
	 */
	public static function append_to_file( $content, $file_path, $separator_text = '' ) {
		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}

		// By this point, the $wp_filesystem global should be working, so let's use it to create a file.
		global $wp_filesystem;

		$existing_data = '';
		if ( file_exists( $file_path ) ) {
			$existing_data = $wp_filesystem->get_contents( $file_path );
		}


		
		// Style separator.
		$separator = PHP_EOL . '---' . $separator_text . '---' . PHP_EOL;

		if ( ! $wp_filesystem->put_contents( $file_path, $existing_data . $separator . $content . PHP_EOL ) ) {
			return new \WP_Error(
				'failed_writing_file_to_server',
				sprintf(
					__( 'An error occurred while writing file to your server! Tried to write a file to: %s%s.', 'templify-importer-templates' ),
					'<br>',
					$file_path
				)
			);
		}

		return true;
	}

    /**
	 * Get data from a file
	 *
	 * @param string $file_path file path where the content should be saved.
	 * @return string $data, content of the file or WP_Error object with error message.
	 */
	public static function data_from_file( $file_path ) {
		//  // Include the necessary file for WP_Filesystem
		//  if (!function_exists('WP_Filesystem')) {
		// 	require_once ABSPATH . 'wp-admin/includes/file.php';
		// }

	
		// global $wp_filesystem;
	
		// // Initialize the WP_Filesystem
		// if (!WP_Filesystem()) {
		// 	return new \WP_Error('filesystem_initialization_failed', __('Failed to initialize WP_Filesystem.', 'templify-importer-templates'));
		// }
	
		// // Verify the file is readable
		// if (!$wp_filesystem->exists($file_path) || !$wp_filesystem->is_readable($file_path)) {
		// 	return new \WP_Error('file_not_readable', __('The file is not readable.', 'templify-importer-templates'));
		// }
	
		// // Get the file contents
		// $data = $wp_filesystem->get_contents($file_path);
	
		// if ($data === false) {
		// 	return new \WP_Error('failed_reading_file', __('Failed to read the file.', 'templify-importer-templates'));
		// }
	
		// // Return the file data
		// echo $data;


		 // Ensure the file exists and is readable
		 if (!is_readable($file_path)) {
			error_log("File is not readable: " . $file_path);
			return false;
		}
	
		// Get the file contents
		$data = file_get_contents($file_path);
	
		if ($data === false) {
			error_log("Failed to read the file: " . $file_path);
			return false;
		}
	
		// Return the file data
		return $data;
	}
	/**
	 * Helper function: check for WP file-system credentials needed for reading and writing to a file.
	 *
	 * @return boolean|WP_Error
	 */
	private static function check_wp_filesystem_credentials() {
		// Check if the file-system method is 'direct', if not display an error.
		$file_system_method = apply_filters( 'templify-importer-templates/file_system_method', 'direct' );
		if ( ! ( $file_system_method === get_filesystem_method() ) ) {
			return new \WP_Error(
				'no_direct_file_access',
				sprintf(
					__( 'This WordPress page does not have %sdirect%s write file access. This plugin needs it in order to save the demo import xml file to the upload directory of your site. You can change this setting with these instructions: %s.', 'kadence-starter-templates' ),
					'<strong>',
					'</strong>',
					'<a href="http://gregorcapuder.com/wordpress-how-to-set-direct-filesystem-method/" target="_blank">How to set <strong>direct</strong> filesystem method</a>'
				)
			);
		}
		// Get plugin page settings.
		$plugin_page_setup =  array(
				'parent_slug' => 'themes.php',
				'page_title'  => esc_html__( 'One Click Demo Import' , 'templify-importer-templates' ),
				'menu_title'  => esc_html__( 'Import Demo Data' , 'templify-importer-templates' ),
				'capability'  => 'import',
				'menu_slug'   => 'pt-one-click-demo-import',
			
		);

		// Get user credentials for WP file-system API.
		$demo_import_page_url = wp_nonce_url( $plugin_page_setup['parent_slug'] . '?page=' . $plugin_page_setup['menu_slug'], $plugin_page_setup['menu_slug'] );

		if ( false === ( $creds = request_filesystem_credentials( $demo_import_page_url, '', false, false, null ) ) ) {
			return new \WP_error(
				'filesystem_credentials_could_not_be_retrieved',
				__( 'An error occurred while retrieving reading/writing permissions to your server (could not retrieve WP filesystem credentials)!', 'kadence-starter-templates' )
			);
		}

		// Now we have credentials, try to get the wp_filesystem running.
		if ( ! WP_Filesystem( $creds ) ) {
			return new \WP_Error(
				'wrong_login_credentials',
				__( 'Your WordPress login credentials don\'t allow to use WP_Filesystem!', 'kadence-starter-templates' )
			);
		}
		return true;
	}


	/**
	 * Download import files. Content .xml and widgets .wie|.json files.
	 *
	 * @param  array  $import_file_info array with import file details.
	 * @return array|WP_Error array of paths to the downloaded files or WP_Error object with error message.
	 */
	public static function download_import_files(  ) {
		$downloaded_files = array(
			'content'    => '',
			'widgets'    => '',
			'customizer' => '',
		);
	//	$downloader = new Downloader();
		// // if ( empty( $import_file_info['content'] ) ) {
		// 	if ( isset( $import_file_info['local_content'] ) && file_exists( $import_file_info['local_content'] ) ) {
		// 		$downloaded_files['content'] = $import_file_info['local_content'];
		// 	}
		// } else {
			// Set the filename string for content import file.
		//	$content_filename = 'demo-content-import-file_' . self::$demo_import_start_time . '.xml';
		$content_file = 'content.xml';

		// Get the theme directory path
		$theme_directory = get_stylesheet_directory();
		
		// Define the subdirectory
		$subdirectory = 'starter';
		
		// Construct the file path using consistent directory separators
		$file_path = realpath($theme_directory . DIRECTORY_SEPARATOR . $subdirectory . DIRECTORY_SEPARATOR . $content_file);
		
		// Output the normalized path for debugging purposes
		 $file_path;

		 // Check if file exists
		 if (!file_exists($file_path)) {
			error_log("File does not exist: " . $file_path);
			return;
		}
	
		$content_file =  self::data_from_file($file_path);

		$content_file2 =	'widget_data.json';


		$file_path2 = realpath($theme_directory . DIRECTORY_SEPARATOR . $subdirectory . DIRECTORY_SEPARATOR . $content_file2);
		$content_file2 =  self::data_from_file($file_path2);


		$content_file3 =	'theme_options.json';


		$file_path3 = realpath($theme_directory . DIRECTORY_SEPARATOR . $subdirectory . DIRECTORY_SEPARATOR . $content_file3);
		$content_file3 =  self::data_from_file($file_path3);
//  $import_file_info['content'];
	
			// Download the content import file.
		//$downloaded_files['content'] = $downloader->download_file( $import_file_info['content'], $content_filename );
		$downloaded_files['content']  = $file_path;
		$downloaded_files['widgets']  = $content_file2;
		$downloaded_files['customizer']  = $file_path3;
		
		// // Return from this function if there was an error.
			// if ( is_wp_error( $downloaded_files['content'] ) ) {
			// 	return $downloaded_files['content'];
			// }
	//	}

		// ----- Set widget file path -----
		// // Get widgets file as well. If defined!
		// if ( ! empty( $import_file_info['widget_data'] ) ) {
		// 	// Set the filename string for widgets import file.
		// 	$widget_filename = 'demo-widgets-import-file_'  . self::$demo_import_start_time . '.json' ;

		// 	// Download the widgets import file.
		// 	$downloaded_files['widgets'] = $downloader->download_file( $import_file_info['widget_data'], $widget_filename );

		// 	// Return from this function if there was an error.
		// 	if ( is_wp_error( $downloaded_files['widgets'] ) ) {
		// 		return $downloaded_files['widgets'];
		// 	}
		// } else if ( ! empty( $import_file_info['local_widget_data'] ) ) {
		// 	if ( file_exists( $import_file_info['local_widget_data'] ) ) {
		// 		$downloaded_files['widgets'] = $import_file_info['local_widget_data'];
		// 	}
		// }

		// // ----- Set customizer file path -----
		// // Get customizer import file as well. If defined!
		// if ( ! empty( $import_file_info['theme_options'] ) ) {
		// 	// Setup filename path to save the customizer content.
		// 	$customizer_filename = 'demo-customizer-import-file_'  . self::$demo_import_start_time . '.dat';

		// 	// Download the customizer import file.
		// 	$downloaded_files['customizer'] = $downloader->download_file( $import_file_info['theme_options'], $customizer_filename );

		// 	// Return from this function if there was an error.
		// 	if ( is_wp_error( $downloaded_files['customizer'] ) ) {
		// 		return $downloaded_files['customizer'];
		// 	}
		// } else if ( ! empty( $import_file_info['local_theme_options'] ) ) {
		// 	if ( file_exists( $import_file_info['local_theme_options'] ) ) {
		// 		$downloaded_files['customizer'] = $import_file_info['local_theme_options'];
		// 	}
		// }
		

		return $downloaded_files;
	}


	/**
	 * Download import files. Content .xml and widgets .wie|.json files.
	 *
	 * @param  array  $import_file_info array with import file details.
	 * @return array|WP_Error array of paths to the downloaded files or WP_Error object with error message.
	 */
	public static function download_import_file( $import_file_info, $page_id ) {
		$downloaded_files = array(
			'content'    => '',
			'customizer' => '',
		);
		$downloader = new Downloader();

		// ----- Set content file path -----
		// Set the filename string for content import file.
		$content_filename =  'demo-content-import-file_'. self::$demo_import_start_time . '.xml';

		// Download the content import file.
		$downloaded_files['content'] = $downloader->download_file( $import_file_info['pages'][ $page_id ]['content'], $content_filename );

		// Return from this function if there was an error.
		if ( is_wp_error( $downloaded_files['content'] ) ) {
			return $downloaded_files['content'];
		}
		// ----- Set customizer file path -----
		// Get customizer import file as well. If defined!
		if ( ! empty( $import_file_info['theme_options'] ) ) {
			// Setup filename path to save the customizer content.
			$customizer_filename = 'demo-customizer-import-file_' . self::$demo_import_start_time . '.dat';

			// Download the customizer import file.
			$downloaded_files['customizer'] = $downloader->download_file( $import_file_info['theme_options'], $customizer_filename );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['customizer'] ) ) {
				return $downloaded_files['customizer'];
			}
		}

		return $downloaded_files;
	}


	public static function do_action( $hook, ...$arg ) {
		do_action( $hook, ...$arg );

		$args = [];
		foreach ( $arg as $argument ) {
			$args[] = $argument;
		}

		do_action_deprecated( "pt-$hook", $args, '3.0.0', $hook );
	}


	public static function set_templify_import_data_transient( $data ) {
		set_transient( 'templify_importer_data', $data, 0.1 * HOUR_IN_SECONDS );
	}



	/**
	 * Backwards compatible has_action helper.
	 * With 3.0 we changed the action prefix from 'pt-ocdi/' to just 'ocdi/',
	 * but we needed to make sure backwards compatibility is in place.
	 * This method should be used for all has_action calls.
	 *
	 * @param string        $hook              The name of the action hook.
	 * @param callable|bool $function_to_check Optional. The callback to check for. Default false.
	 *
	 * @return bool|int If $function_to_check is omitted, returns boolean for whether the hook has
	 *                  anything registered. When checking a specific function, the priority of that
	 *                  hook is returned, or false if the function is not attached. When using the
	 *                  $function_to_check argument, this function may return a non-boolean value
	 *                  that evaluates to false (e.g.) 0, so use the === operator for testing the
	 *                  return value.
	 */
	public static function has_action( $hook, $function_to_check = false ) {
		if ( has_action( $hook ) ) {
			return has_action( $hook, $function_to_check );
		} else if ( has_action( "pt-$hook" ) ) {
			return has_action( "pt-$hook", $function_to_check );
		}

		return false;
	}


	/**
	 * Get the category list of all categories used in the predefined demo imports array.
	 *
	 * @param  array $demo_imports Array of demo import items (arrays).
	 * @return array|boolean       List of all the categories or false if there aren't any.
	 */
	public static function get_all_demo_import_categories( $demo_imports ) {
		$categories = array();

		foreach ( $demo_imports as $item ) {
			if ( ! empty( $item['categories'] ) && is_array( $item['categories'] ) ) {
				foreach ( $item['categories'] as $category ) {
					$categories[ sanitize_key( $category ) ] = $category;
				}
			}
		}

		if ( empty( $categories ) ) {
			return false;
		}

		return $categories;
	}


	/**
	 * Return the concatenated string of demo import item categories.
	 * These should be separated by comma and sanitized properly.
	 *
	 * @param  array  $item The predefined demo import item data.
	 * @return string       The concatenated string of categories.
	 */
	public static function get_demo_import_item_categories( $item ) {
		$sanitized_categories = array();

		if ( isset( $item['categories'] ) ) {
			foreach ( $item['categories'] as $category ) {
				$sanitized_categories[] = sanitize_key( $category );
			}
		}

		if ( ! empty( $sanitized_categories ) ) {
			return implode( ',', $sanitized_categories );
		}

		return false;
	} 
	/**
	 * Write the error to the log file and send the AJAX response.
	 *
	 * @param string $error_text text to display in the log file and in the AJAX response.
	 * @param string $log_file_path path to the log file.
	 * @param string $separator title separating the old and new content.
	 */
	public static function log_error_and_send_ajax_response( $error_text, $log_file_path, $separator = '' ) {
		if ( false ) {
			// Add this error to log file.
			$log_added = self::append_to_file(
				$error_text,
				$log_file_path,
				$separator
			);
		}

		// Send JSON Error response to the AJAX call.
		wp_send_json( $error_text );
	}

		/**
	 * Write content to a file.
	 *
	 * @param string $content content to be saved to the file.
	 * @param string $file_path file path where the content should be saved.
	 * @return string|WP_Error path to the saved file or WP_Error object with error message.
	 */
	public static function write_to_file( $content, $file_path ) {
		// Verify WP file-system credentials.
		 $verified_credentials = self::check_wp_filesystem_credentials();

		// if ( is_wp_error( $verified_credentials ) ) {
		// 	return $verified_credentials;
		// }

		// By this point, the $wp_filesystem global should be working, so let's use it to create a file.
		global $wp_filesystem;

		if ( ! $wp_filesystem->put_contents( $file_path, $content ) ) {
			return new \WP_Error(
				'failed_writing_file_to_server',
				sprintf(
					__( 'An error occurred while writing file to your server! Tried to write a file to: %s%s.', 'templify-importer-templates' ),
					'<br>',
					$file_path
				)
			);
		}

		// Return the file path on successful file write.
		return $file_path;
	}




	

	
	/**
	 * Check if the AJAX call is valid.
	 */
	public static function verify_ajax_call() {
		check_ajax_referer( 'templify-ajax-verification', 'security' );

		// Check if user has the WP capability to import data.
		if ( ! current_user_can( 'import' ) ) {
			wp_die(
				sprintf(
					__( '%sYour user role isn\'t high enough. You don\'t have permission to import demo data.%s', 'templify-importer-templates' ),
					'<div class="notice  notice-error"><p>',
					'</p></div>'
				)
			);
		}
	}


	/**
	 * Get log file path
	 *
	 * @return string, path to the log file
	 */
	public static function get_log_path() {
		$upload_dir  = wp_upload_dir();
		$upload_path = self::apply_filters( 'templify-importer-templates/upload_file_path', trailingslashit( $upload_dir['path'] ) );

		$log_path = $upload_path . self::apply_filters( 'templify-importer-templates/log_file_prefix', 'log_file_' ) . self::$demo_import_start_time . self::apply_filters( 'templify-importer-templates/log_file_suffix_and_file_extension', '.txt' );

		self::register_file_as_media_attachment( $log_path );

		return $log_path;
	}


	/**
	 * Register file as attachment to the Media page.
	 *
	 * @param string $log_path log file path.
	 * @return void
	 */
	public static function register_file_as_media_attachment( $log_path ) {
		// Check the type of file.
		$log_mimes = array( 'txt' => 'text/plain' );
		$filetype  = wp_check_filetype( basename( $log_path ),  $log_mimes  );

		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => self::get_log_url( $log_path ),
			'post_mime_type' => $filetype['type'],
			'post_title'     =>  esc_html__( 'Templify Importer Templates Import - ', 'templify-importer-templates' )  . preg_replace( '/\.[^.]+$/', '', basename( $log_path ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Insert the file as attachment in Media page.
		$attach_id = wp_insert_attachment( $attachment, $log_path );
	}


/**
	 * Set the $demo_import_start_time class variable with the current date and time string.
	 */
	public static function set_demo_import_start_time() {
		self::$demo_import_start_time = date( self::apply_filters( 'templify-importer-templates/date_format_for_file_names', 'Y-m-d__H-i-s' ) );
	}

	
		/**
	 * Get log file url
	 *
	 * @param string $log_path log path to use for the log filename.
	 * @return string, url to the log file.
	 */
	public static function get_log_url( $log_path ) {
		$upload_dir = wp_upload_dir();
		$upload_url = self::apply_filters( 'templify-importer-templates/upload_file_url', trailingslashit( $upload_dir['url'] ) );

		return $upload_url . basename( $log_path );
	}


	/**
	 * Set the templify transient with the current importer data.
	 *
	 * @param array $data Data to be saved to the transient.
	 */
	public static function set_import_data_transient( $data ) {
		set_transient( 'templify_importer_data', $data, 0.1 * HOUR_IN_SECONDS );
	}


	
	/**
	 * Filter through the array of import files and get rid of those who do not comply.
	 *
	 * @param  array $import_files list of arrays with import file details.
	 * @return array list of filtered arrays.
	 */
	public static function validate_import_file_info( $import_files ) {
		$filtered_import_file_info = array();

		foreach ( $import_files as $import_file ) {
			if ( self::is_import_file_info_format_correct( $import_file ) ) {
				$filtered_import_file_info[] = $import_file;
			}
		}

		return $filtered_import_file_info;
	}

	/**
	 * Helper function: a simple check for valid import file format.
	 *
	 * @param  array $import_file_info array with import file details.
	 * @return boolean
	 */
	private static function is_import_file_info_format_correct( $import_file_info ) {
		if ( empty( $import_file_info['slug'] ) ) {
			return false;
		}

		return true;
	}


	public static function apply_filters( $hook, $default_data ) {
		$new_data = apply_filters( $hook, $default_data );

		if ( $new_data !== $default_data ) {
			return $new_data;
		}

		return $default_data;
	}


		/**
	 * Get the plugin page setup data.
	 *
	 * @return array
	 */
	public static function get_plugin_page_setup_data() {
		return Helpers::apply_filters( 'templify/plugin_page_setup', array(
			'parent_slug' => 'themes.php',
			'page_title'  => esc_html__( 'One Click Import' , 'templify-importer-templates' ),
			'menu_title'  => esc_html__( 'Import Demo Data' , 'templify-importer-templates' ),
			'capability'  => 'import',
			'menu_slug'   => 'templify-importer-templates',
		) );
	}



		/**
	 * Set the failed attachment imports.
	 *
	 * @since 3.2.0
	 *
	 * @param string $attachment_url The attachment URL that was not imported.
	 *
	 * @return void
	 */
	public static function set_failed_attachment_import( $attachment_url ) {

		// Get current importer transient.
		$failed_media_imports = self::get_failed_attachment_imports();

		if ( empty( $failed_media_imports ) || ! is_array( $failed_media_imports ) ) {
			$failed_media_imports = [];
		}

		$failed_media_imports[] = $attachment_url;

		set_transient( 'templify_importer_data_failed_attachment_imports', $failed_media_imports, HOUR_IN_SECONDS );
	}



	/**
	 * Get the failed attachment imports.
	 *
	 * @since 3.2.0
	 *
	 * @return mixed
	 */
	public static function get_failed_attachment_imports() {

		return get_transient( 'templify_importer_data_failed_attachment_imports' );
	}




}
