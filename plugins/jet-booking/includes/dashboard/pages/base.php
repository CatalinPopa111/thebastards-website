<?php

namespace JET_ABAF\Dashboard\Pages;

use JET_ABAF\Dashboard\Helpers\Page_Config;

abstract class Base {

	/**
	 * Page slug.
	 *
	 * @return string
	 */
	abstract public function slug();

	/**
	 * Page title.
	 *
	 * @return string
	 */
	abstract public function title();

	/**
	 * Page capability.
	 *
	 * @return string
	 */
	abstract public function capability();

	/**
	 * Page render function.
	 *
	 * @return void
	 */
	abstract public function render();

	/**
	 * Return page config.
	 *
	 * @return Page_Config
	 */
	abstract public function page_config();

	/**
	 * Dashboard pages specific assets.
	 *
	 * @return void
	 */
	public function assets() {
		$this->enqueue_style( 'jet-abaf-dashboard', 'assets/css/admin/dashboard.css' );
	}

	/**
	 * Check if is setup page.
	 *
	 * @return boolean
	 */
	public function is_setup_page() {
		return false;
	}

	/**
	 * Check if is settings page.
	 *
	 * @return boolean
	 */
	public function is_settings_page() {
		return false;
	}

	/**
	 * Page components templates.
	 *
	 * @return array
	 */
	public function vue_templates() {
		return [];
	}

	/**
	 * Render vue templates.
	 *
	 * @return void
	 */
	public function render_vue_templates() {
		foreach ( $this->vue_templates() as $template ) {
			if ( is_array( $template ) ) {
				$this->render_vue_template( $template['file'], $template['dir'] );
			} else {
				$this->render_vue_template( $template );
			}
		}
	}

	/**
	 * Renders a Vue.js template.
	 *
	 * This method renders a Vue-compatible template script tag with a specified template and optional path.
	 * If no path is provided, the method uses a default slug path.
	 *
	 * @since  4.0.0 Updated path method.
	 * @access public
	 *
	 * @param string      $template The name of the template to be rendered.
	 * @param string|null $path     The path to the template file.
	 *
	 * @return void
	 */
	public function render_vue_template( $template, $path = null ) {

		if ( ! $path ) {
			$path = $this->slug();
		}

		$file = jet_abaf()->get_template( 'admin/' . $path . '/' . $template . '.php' );

		if ( ! is_readable( $file ) ) {
			return;
		}

		ob_start();

		include $file;

		printf( '<script type="text/x-template" id="jet-abaf-%s">%s</script>', $template, ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Enqueue script.
	 *
	 * @param string   $handle    Name of the script. Should be unique.
	 * @param string   $file_path Full URL of the script, or path of the script relative to the WordPress root
	 *                            directory.
	 * @param string[] $deps      An array of registered script handles this script depends on.
	 *
	 * @return void
	 */
	public function enqueue_script( $handle = null, $file_path = null, $deps = [] ) {
		wp_enqueue_script(
			$handle,
			JET_ABAF_URL . $file_path,
			wp_parse_args( $deps, [ 'wp-api-fetch', 'wp-i18n' ] ),
			JET_ABAF_VERSION,
			true
		);
	}

	/**
	 * Enqueue style.
	 *
	 * @param string   $handle    Name of the script. Should be unique.
	 * @param string   $file_path Full URL of the script, or path of the script relative to the WordPress root
	 *                            directory.
	 * @param string[] $deps      An array of registered script handles this script depends on.
	 *
	 * @return void
	 */
	public function enqueue_style( $handle = null, $file_path = null, $deps = [] ) {
		wp_enqueue_style(
			$handle,
			JET_ABAF_URL . $file_path,
			$deps,
			JET_ABAF_VERSION
		);
	}

	/**
	 * Set to true to hide page from admin menu.
	 *
	 * @return boolean
	 */
	public function is_hidden() {
		return false;
	}

	/**
	 * Returns current page url.
	 *
	 * @return string
	 */
	public function get_url() {
		return add_query_arg( [ 'page' => $this->slug() ], esc_url( admin_url( 'admin.php' ) ) );
	}

}
