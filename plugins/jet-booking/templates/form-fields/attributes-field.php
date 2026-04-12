<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly. ?>

<div class="jet-abaf-product-services">
	<?php foreach ( $attributes as $name => $attribute ) : ?>
		<h5 class="jet-abaf-product-services-heading">
			<?php echo esc_html( $attribute['label'] ); ?>
		</h5>

		<?php foreach ( $attribute['terms'] as $slug => $term ) : ?>
			<div class="form-field">
				<label>
					<input type="checkbox" name="<?php echo 'jet_abaf_product_' . esc_attr( $name ) . '[]';?>" value="<?php echo esc_attr( $slug ); ?>">
					<?php echo wp_kses_post( $term['label'] ); ?>
				</label>

				<?php if ( ! empty( $term['description'] ) ) : ?>
					<p class="description"><?php echo esc_html( $term['description'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	<?php endforeach; ?>
</div>
