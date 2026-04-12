<?php
// Check if the user has the required permission
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have sufficient permissions to access this page.' );
}
include_once( ABSPATH . 'wp-admin/includes/update.php' ); // Include the update.php file
wp_maybe_auto_update(); // Check for updates and perform them automatically if available

// Get a list of all themes
$themes = wp_get_themes();
// Get an array of theme updates available for installed themes
$theme_updates = get_theme_updates();
?>
<div class="wrap">
	<div id="p99-overlay">
      <div class="p99-spinner-wrap">
        <span class="p99-spinner"></span>
      </div>
    </div>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th scope="col" style="width:35px;"></th>
				<th scope="col">Name</th>
				<th scope="col">Current Version</th>
				<th scope="col">Available Version</th>
				<th scope="col">Backup Version</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $themes as $theme_slug => $theme_data ) :
				// Check if an update is available for this theme
				$update_available = isset( $theme_updates[ $theme_slug ] );
                $p99_theme = file_exists( WP_CONTENT_DIR . '/themes/' . $theme_slug . '/99plugs' );
                $p99_items_to_show = get_option( 'p99_items_to_show' );
                
                if ( $p99_items_to_show == '99Plugs' && ! $p99_theme ) {
                	continue;
                } else {
					?>
					<tr>
						<td style="vertical-align:middle; height:25px;"><?php if ( $p99_theme ) { ?><div class='p99-icon'></div><?php } ?></td>
						<td style="vertical-align:middle;"><?php echo esc_html( $theme_data['Name'] ); ?></td>
						<td style="vertical-align:middle;"><?php echo esc_html( $theme_data['Version'] ); ?></td>
						<td style="vertical-align:middle;">
							<?php if ( $update_available ) : ?>
								<?php
								// Get the new version number
								$new_version = $theme_updates[ $theme_slug ]->update['new_version'];
								// Get the theme update link
								$update_link = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-theme&theme=' . urlencode( $theme_slug ) ), 'upgrade-theme_' . $theme_slug );
								?>
								<span style="font-weight:bold; color: #4CBB17; padding-right:5px;"><?php echo esc_html( $new_version ); ?></span>
								<a href="<?php echo esc_url( $update_link ); ?>">Update Now</a>
							<?php else : ?>
								<?php echo esc_html( $theme_data['Version'] ); ?>
							<?php endif; ?>
						</td>
						<td style="vertical-align:middle;">
							<?php
							// Get the backup folder for this theme
							$backup_folder = WP_CONTENT_DIR . '/99backup/' . $theme_slug;
							// Get a list of all backups for this theme
							$backups = glob( $backup_folder . '/*.zip' );
							// Sort the backups by modified time
							usort( $backups, function( $a, $b ) {
								return filemtime( $a ) < filemtime( $b );
							} );
							// Check if there are any backups
							if ( ! empty( $backups ) ) : ?>
								<select name="backup_version">
									<?php foreach ( $backups as $backup ) : ?>
										<?php $backup_version = substr( basename( $backup ), strrpos( basename( $backup ), '-' ) + 1, -4 ); ?>
										<option value="<?php echo esc_attr( $backup ); ?>"><?php echo esc_html( $backup_version ); ?></option>                       
									<?php endforeach; ?>
								</select>
								<button class="button p99-rollback-button" type="button" data-theme-slug="<?php echo esc_attr( $theme_slug ); ?>">Rollback</button>
							<?php endif; ?>
						</td>
					</tr>
                <?php } ?>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<script>
jQuery(function($) {
	$('.p99-rollback-button').click(function(e) {
		// Get the theme slug and the selected backup file
		var theme_slug = $(this).data('theme-slug');
		var backup_file = $(this).closest('td').find('select').val();
		// Confirm the rollback with the user
		if ( confirm('Are you sure you want to rollback this theme?') ) {
			// Send an AJAX request to rollback the theme
			$.ajax({
				type: 'POST',
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				data: {
					action: 'p99_rollback_theme',
					theme_slug: theme_slug,
					backup_file: backup_file
				},
				beforeSend: function() {
     				$('#p99-overlay').show();
 				},
				success: function(response) {
					//alert('The theme was successfully rolled back.');
					$('#p99-overlay').hide();
					location.reload()
				},
				error: function(error) {
					alert('There was an error rolling back the theme.');
				}
			});
		}
	});
});
</script>