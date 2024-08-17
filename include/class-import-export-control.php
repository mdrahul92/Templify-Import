<?php
/**
 * The Import Export customize control extends the WP_Customize_Control class.
 *
 * @package Templify Importer Templates
 */


 namespace TemplifyWP\TemplifyImporterTemplates;

 if ( ! defined( 'ABSPATH' ) ) {
	 exit;
 }

use WP_Customize_Control;

if ( ! class_exists( 'WP_Customize_Control' ) ) {
	return;
}

/**
 * Class Templify_Importer_Control_Import_Export 
 *
 * @access public
 */
class Templify_Importer_Control_Import_Export extends WP_Customize_Control {
	/**
	 * Control type
	 *
	 * @var string
	 */
	public $type = 'templify_importer_import_export_control';
	/**
	 * Empty Render Function to prevent errors.
	 */
	public function render_content() {
		?>
			<span class="customize-control-title">
				<?php esc_html_e( 'Export', 'templify' ); ?>
			</span>
			<span class="description customize-control-description">
				<?php esc_html_e( 'Click the button below to export the customization settings for this theme.', 'templify' ); ?>
			</span>
			<input type="button" class="button templify-import-export templify-import-button" name="templify-import-export-button" value="<?php esc_attr_e( 'Export', 'templify' ); ?>" />

			<hr class="kt-theme-hr" />

			<span class="customize-control-title">
				<?php esc_html_e( 'Import', 'templify' ); ?>
			</span>
			<span class="description customize-control-description">
				<?php esc_html_e( 'Upload a file to import customization settings for this theme.', 'templify' ); ?>
			</span>
			<div class="templify-importer-import-controls">
				<input type="file" name="templify-importer-import-file" class="templify-importer-import-file" />
				<?php wp_nonce_field( 'templify-importer-importing', 'templify-importer-import' ); ?>
			</div>
			<div class="templify-import-uploading"><?php esc_html_e( 'Uploading...', 'templify' ); ?></div>
			<input type="button" class="button templify-importer-import templify-import-button" name="templify-importer-import-button" value="<?php esc_attr_e( 'Import', 'templify' ); ?>" />

			<hr class="kt-theme-hr" />
			<span class="customize-control-title">
				<?php esc_html_e( 'Reset', 'templify' ); ?>
			</span>
			<span class="description customize-control-description">
				<?php esc_html_e( 'Click the button to reset all theme settings.', 'templify' ); ?>
			</span>
			<input type="button" class="components-button is-destructive templify-import-reset templify-import-button" name="templify-import-reset-button" value="<?php esc_attr_e( 'Reset', 'templify' ); ?>" />
			<?php
	}
}



