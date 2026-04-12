<?php
namespace JET_ABAF\Dashboard\Post_Meta;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Base_Vue_Meta_Box {

	/**
	 * Post meta key holder.
	 *
	 * @var string|null
	 */
	protected $meta_key = null;

	/**
	 * Post types holder.
	 *
	 * @var array
	 */
	protected $post_types = [];

	/**
	 * Current post types holder.
	 *
	 * @var array
	 */
	private $current_post_types = [];

	public function __construct( $current_post_types = [] ) {

		if ( empty( $current_post_types ) || ! is_array( $current_post_types ) ) {
			return;
		}

		$this->current_post_types = $current_post_types;

		if ( ! $this->meta_key ) {
			$this->meta_key = 'jet_abaf_post_meta';
		}

		// Registers a meta-box for displaying booking related data in the post-edit screen.
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );

		// Initializes the bookings meta functionality.
		add_action( 'admin_enqueue_scripts', [ $this, 'vendor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ], 11 );

		// Render Vue templates.
		add_action( 'admin_footer', [ $this, 'render_templates' ] );

	}

	/**
	 * Is CPT page.
	 *
	 * Checks if the post page is booking instance custom post type.
	 *
	 * @since 2.2.5
	 *
	 * @return boolean
	 */
	protected function is_cpt_page() {
		return is_admin() && in_array( get_current_screen()->id, $this->current_post_types );
	}

	/**
	 * Register a meta-box.
	 *
	 * Registers a meta-box for displaying data in the post-edit screen.
	 *
	 * @since  2.2.5
	 * @access public
	 *
	 * @return void
	 */
	protected function add_meta_box() {}

	/**
	 * Default meta.
	 *
	 * Returns default meta values list.
	 *
	 * @since  2.2.5
	 * @access public
	 *
	 * @return array List on default values.
	 */
	protected function get_default_meta() {
		return [];
	}

	/**
	 * Retrieve and process meta information for a post.
	 *
	 * @since  2.2.5
	 * @access protected
	 *
	 * @return array Processed meta information including defaults.
	 */
	protected function get_meta() {

		$default_meta = $this->get_default_meta();
		$post_ID      = get_the_ID();
		$meta         = get_post_meta( $post_ID, $this->meta_key, true );
		$meta         = is_array( $meta ) ? $meta : [];
		$meta         = array_replace_recursive( $default_meta, $meta );
		$meta         = $this->parse_settings( $meta );

		$meta['ID']     = $post_ID;
		$meta['action'] = $this->meta_key;
		$meta['nonce']  = wp_create_nonce( $this->meta_key );

		return $meta;

	}

	/**
	 * Parse settings.
	 *
	 * Parsed data before written to the database and after get from the database.
	 *
	 * @since  2.2.5
	 * @access public
	 *
	 * @return array List of parsed settings.
	 */
	protected function parse_settings( $settings ){
		return $settings;
	}

	/**
	 * Save post meta.
	 *
	 * Saves metadata to the database.
	 *
	 * @since  1.0.0
	 * @since  2.6.2 Added `nonce` security check.
	 * @since  4.0.0 Added booking vendor capability check.
	 * @access public
	 *
	 * @return void
	 */
	public function save_post_meta() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], $this->meta_key ) ) { // phpcs:ignore
			wp_send_json_error( [ 'message' => __( 'Page is expired. Please reload it and try again.', 'jet-booking' ) ] );
		}

		if ( ! current_user_can( \JET_ABAF\Capabilities::CAP_MANAGE_POST ) ) {
			wp_send_json_error( [ 'message' => __( 'Access denied. Not enough permissions.', 'jet-booking' ) ] );
		}

		$meta = ! empty( $_REQUEST['meta'] ) ? jet_abaf()->tools->sanitize_array_recursively( wp_unslash( $_REQUEST['meta'] ) ) : []; // phpcs:ignore

		if ( empty( $meta ) || ! isset( $meta['ID'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Empty data or post ID not found!', 'jet-booking' ) ] );
		}

		$meta   = $this->parse_settings( $meta );
		$result = update_post_meta( $meta['ID'], $this->meta_key, $meta );

		// Needed for backward compatibility.
		$this->backward_save_post_meta( $meta );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => __( 'Failed to save data!', 'jet-booking' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Settings saved!', 'jet-booking' ) ] );

	}

	/**
	 * Save post meta data backward compatibility.
	 *
	 * Ensures that post meta data is saved in a way that maintains backward compatibility with older versions.
	 *
	 * @since 2.2.5
	 *
	 * @param array $meta An associative array of meta data to be saved.
	 *
	 * @return void
	 */
	protected function backward_save_post_meta( $meta ) {}

	/**
	 * Enqueue vendor assets for the custom post type page.
	 *
	 * This method ensures that necessary scripts and assets required for
	 * the admin custom post type page are registered and loaded.
	 *
	 * @since 2.2.5
	 *
	 * @return void
	 */
	public function vendor_assets() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		$ui_data = jet_abaf()->framework->get_included_module_data( 'cherry-x-vue-ui.php' );
		$ui      = new \CX_Vue_UI( $ui_data );

		$ui->enqueue_assets();

		wp_enqueue_script(
			'vuejs-datepicker',
			JET_ABAF_URL . 'assets/js/lib/vuejs-datepicker.min.js',
			[],
			JET_ABAF_VERSION,
			true
		);

		wp_enqueue_script(
			'moment-js',
			JET_ABAF_URL . 'assets/lib/moment/js/moment.js',
			[],
			JET_ABAF_VERSION,
			true
		);

		wp_localize_script( 'cx-vue', $this->meta_key, $this->get_meta() );

	}

	/**
	 * Enqueue assets for the custom post type page.
	 *
	 * Loads necessary JavaScript and CSS files for the admin interface.
	 *
	 * @since  2.2.5
	 * @access public
	 *
	 * @return void
	 */
	public function assets() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		wp_enqueue_script(
			'jet-abaf-meta-extras',
			JET_ABAF_URL . 'assets/js/admin/meta-extras.js',
			[ 'cx-vue-ui', 'moment-js' ],
			JET_ABAF_VERSION,
			true
		);

		wp_enqueue_style(
			'jet-abaf-meta',
			JET_ABAF_URL . 'assets/css/admin/jet-abaf-admin-style.css',
			[],
			JET_ABAF_VERSION
		);

	}

	/**
	 * Vue templates.
	 *
	 * Return vue templates.
	 *
	 * @since  2.2.5
	 * @access public
	 *
	 * @return array List of vue templates.
	 */
	protected function get_vue_templates() {
		/*[
			[
				'dir'  => '',
				'file' => '',
			]
		]*/

		return [];
	}

	/**
	 * Render Vue templates.
	 *
	 * @since   2.2.5
	 * @access  public
	 *
	 * @return void
	 */
	public function render_templates() {

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		$templates = $this->get_vue_templates();

		if ( empty( $templates ) ) {
			return;
		}

		foreach ( $this->get_vue_templates() as $template ) {
			if ( is_array( $template ) ) {
				$this->render_template( $template['file'], $template['dir'] );
			} else {
				$this->render_template( $template );
			}
		}

	}

	/**
	 * Render a template file into a script block.
	 *
	 * @param string      $template The name of the template file.
	 * @param string|null $path     Subdirectory path relative to the base template directory.
	 *
	 * @return void
	 */
	private function render_template( $template, $path = null ) {

		if ( ! $path ) {
			$path = $this->meta_key;
		}

		$file = JET_ABAF_PATH . 'templates/admin/' . $path . '/' . $template . '.php';

		if ( ! is_readable( $file ) ) {
			return;
		}

		ob_start();

		include $file;

		printf( '<script type="text/x-template" id="jet-abaf-%s">%s</script>', $template, ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

}
