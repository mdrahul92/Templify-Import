<?php
/**
 * Plugin Name: Templify Import
 * Description: Import Templates through Templify import
 * Version: 1.0.0
 * Author: Templify Inner WP
 * License: GPLv2 or later
 * Text Domain: templify-importer-templates
 *
 * @package Templify Importer Templates
 */

// Block direct access to the main plugin file.



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TEMPLIFY_IMPORT_TEMPLATES_PATH', plugin_dir_path( __FILE__ ) );
define( 'TEMPLIFY_IMPORT_TEMPLATES_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'TEMPLIFY_IMPORT_TEMPLATES_VERSION', '1.0.0' );


//require_once plugin_dir_path( __FILE__ ) . 'vendor/vendor-prefixed/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'class-templify-import-templates.php';



// Enqueue scripts and styles
function templify_import_enqueue_scripts() {
    // Enqueue jQuery
    wp_enqueue_script('jquery');
    
    // Enqueue plugin scripts
    wp_enqueue_style('templify-import-style', plugins_url('assets/css/style.css', __FILE__));
    wp_enqueue_script('templify-import-script', plugins_url('assets/js/script.js', __FILE__), array('jquery'), TEMPLIFY_IMPORT_TEMPLATES_VERSION, true);
}
add_action('wp_enqueue_scripts', 'templify_import_enqueue_scripts');

// Activation hook
function templify_import_activate() { 
    // Activation code here
}

add_action('rest_api_init', function () {
    register_rest_route('theme-files/v1', '/list', array(
        'methods' => 'GET',
        'callback' => 'fetch_theme_files',
        'permission_callback' => '__return_true', // Adjust permissions as needed
    ));
});


register_activation_hook(__FILE__, 'templify_import_activate');


/**
 * Load the plugin textdomain
 */
function templify_importer_templates_lang() {
	load_plugin_textdomain( 'templify-importer-templates', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'templify_importer_templates_lang' );



function fetch_theme_files(WP_REST_Request $request) {
    $allowed_extensions = array('xml', 'json', 'dat');
    $theme_directory = get_stylesheet_directory()."/starter";
    $files = array();

    if (is_dir($theme_directory)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($theme_directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                if (in_array($extension, $allowed_extensions)) {
                    $files[] = str_replace($theme_directory, '', $file->getPathname());
                }
            }
        }
    } else {
        return new WP_REST_Response(array('error' => 'The starter subfolder does not exist.'), 404);
    }
    return new WP_REST_Response($files, 200);
}
// Deactivation hook
function templify_import_deactivate() {
    // Deactivation code here
}


add_action('rest_api_init', 'register_custom_api_endpoint');

function register_custom_api_endpoint() {
    register_rest_route('custom/v1', '/filter', array(
        'methods' => 'GET',
        'callback' => 'custom_api_callback',
    ));
}

function custom_api_callback($request) {
    // Apply the filter and get the data
    $data = apply_filters('kadence_starter_templates_custom_array', array());

    // Return the filtered data as JSON
    return new WP_REST_Response($data, 200);
}


add_action('rest_api_init', 'register_custom_config_api_endpoint');

function register_custom_config_api_endpoint() {
    register_rest_route('custom/v1', '/config_filter', array(
        'methods' => 'GET',
        'callback' => 'custom_config_api_callback',
    ));
}

function custom_config_api_callback($request) {
    // Apply the filter and get the data
    $data = apply_filters('kadence_blocks_custom_prebuilt_libraries', array());

    // Return the filtered data as JSON
    return new WP_REST_Response($data, 200);
}


register_deactivation_hook(__FILE__, 'templify_import_deactivate');








