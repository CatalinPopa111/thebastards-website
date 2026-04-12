<?php
namespace JET_ABAF\Components\Blocks_Views;

use Crocoblock\Blocks_Style\Manager as Style_Manager;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Manager {

	/**
	 * Style manager module instance.
	 *
	 * @var Style_Manager|null
	 */
	public $style_manager = null;

	public function __construct() {

		// Register booking related block categories.
		add_filter( 'block_categories_all', [ $this, 'register_categories' ], 10, 2 );
		// Disable specific blocks in the editor.
		add_filter( 'allowed_block_types_all', [ $this, 'maybe_disable_blocks' ], 10, 2 );

		// Register booking block types.
		add_action( 'init', [ $this, 'register_block_types' ] );
		// Enqueue scripts and styles for editor blocks.
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );

		$module_data = jet_abaf()->framework->get_included_module_data( 'style-manager.php' );

		$this->style_manager = new Style_Manager( [
			'path' => $module_data['path'],
			'url'  => $module_data['url'],
		] );

	}

	/**
	 * Register categories.
	 *
	 * Register booking related block categories.
	 *
	 * @since 3.2.0
	 *
	 * @param array[]                  $block_categories Array of categories for block types.
	 * @param \WP_Block_Editor_Context $editor_context   The current block editor context.
	 *
	 * @return array
	 */
	public function register_categories( $block_categories, $editor_context ) {

		$block_categories[] = [
			'slug'  => 'jet-booking',
			'title' => __( 'JetBooking', 'jet-booking' ),
			'icon'  => null,
		];

		return $block_categories;

	}

	/**
	 * Conditionally disable specific blocks in the editor.
	 *
	 * This function checks if the current post type is 'jet-form-builder' and if so,
	 * removes specific blocks from the list of allowed blocks.
	 *
	 * @since 3.6.5
	 *
	 * @param array|bool               $allowed_blocks The list of allowed block types or true if all block types are
	 *                                                 allowed.
	 * @param \WP_Block_Editor_Context $editor_context The current block editor context.
	 *
	 * @return array|bool Modified list of allowed block types or the original input if conditions are not met.
	 */
	public function maybe_disable_blocks( $allowed_blocks, $editor_context ) {

		if ( isset( $editor_context->post ) && 'jet-form-builder' === $editor_context->post->post_type ) {
			$blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();

			unset( $blocks['jet-booking/calendar'] );

			return array_keys( $blocks );
		}

		return $allowed_blocks;

	}

	/**
	 * Register block types.
	 *
	 * Initialize and register all block types.
	 *
	 * @since  4.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_block_types() {
		new Block_Types\Calendar();
		new Block_Types\Settings_Configuration();
		new Block_Types\Settings_Schedule();
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * Register and enqueue assets for exclusive usage within the Site Editor.
	 *
	 * @since 3.2.0
	 * @since 4.0.0 Added registration of widgets script.
	 */
	public function enqueue_block_editor_assets() {

		jet_abaf()->assets->register_assets();

		wp_register_style(
			'jquery-date-range-picker-styles',
			JET_ABAF_URL . 'assets/lib/jquery-date-range-picker/css/daterangepicker.css',
			[],
			JET_ABAF_VERSION
		);

	}

}