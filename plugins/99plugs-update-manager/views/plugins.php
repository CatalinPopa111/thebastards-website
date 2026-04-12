<?php
// Check if the user has the required permission
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have sufficient permissions to access this page.' );
}
include_once( ABSPATH . 'wp-admin/includes/update.php' ); // Include the update.php file
wp_maybe_auto_update(); // Check for updates and perform them automatically if available

// Get a list of all plugins
$plugins = get_plugins();
// Get an array of plugin updates available for installed plugins
$plugin_updates = get_plugin_updates();
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
			<?php foreach ( $plugins as $plugin_slug => $plugin_data ) :
				// Check if an update is available for this plugin
				$update_available = isset( $plugin_updates[ $plugin_slug ] );
                $p99_plugin = file_exists( WP_PLUGIN_DIR . '/' . dirname( $plugin_slug) . '/99plugs' ) || ($plugin_data['Name'] == "99Plugs Update Manager");
                $p99_items_to_show = get_option( 'p99_items_to_show' );
                
                if ( $p99_items_to_show == '99Plugs' && ! $p99_plugin ) {
                	continue;
                } else {
					$p99_package = '';
					$transient = get_option( '_site_transient_update_plugins' );
					if ( isset( $transient ) && is_object( $transient ) ) {
						if ( isset( $transient->response[ $plugin_slug ] ) ) {
							if ( strpos( $transient->response[ $plugin_slug ]->package, '99plugs' ) !== false ) {
								$p99_package = $transient->response[ $plugin_slug ]->package;
							}
						}
					}
					// Set the plugin_slug variable for the download button
					$download_file = dirname( $plugin_slug ) . '.zip';
					$download_data_attr = 'data-download-url="' . esc_attr( $p99_package ) . '" data-plugin-slug="' . esc_attr( $plugin_slug ) . '"';
					?>
					<tr>
						<td style="vertical-align:middle; height:25px;"><?php if ( $p99_plugin ) { ?><div class='p99-icon'></div><?php } ?></td>
						<td style="vertical-align:middle;"><?php echo esc_html( $plugin_data['Name'] ); ?></td>
						<td style="vertical-align:middle;"><?php echo esc_html( $plugin_data['Version'] ); ?></td>
						<td style="vertical-align:middle;">
							<?php if ( $update_available ) : 
								// Get the new version number
								$new_version = $plugin_updates[ $plugin_slug ]->update->new_version;
								// Get the plugin update link
								$update_link = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . urlencode( $plugin_slug ) ), 'upgrade-plugin_' . $plugin_slug );
								?>
								<?php if ( $p99_plugin &&  $p99_package != '' ) : ?>
									<span style="font-weight:bold; color: #4CBB17; padding-right:5px;"><?php echo esc_html( $new_version ); ?></span>
									<button class="p99-download-button" type="text" <?php echo $download_data_attr; ?> download="<?php echo esc_attr( $download_file ); ?>">Update Now</button>
								<?php else : ?>
									<span style="font-weight:bold; color: #4CBB17; padding-right:5px;"><?php echo esc_html( $new_version ); ?></span>
									<a href="<?php echo esc_url( $update_link ); ?>">Update Now</a>
								<?php endif; ?>
							<?php else : ?>
								<?php echo esc_html( $plugin_data['Version'] ); ?>
							<?php endif; ?>
						</td>
						<td style="vertical-align:middle;">
							<?php
							// Get the backup folder for this plugin
							$backup_folder = WP_CONTENT_DIR . '/99backup/' . dirname( $plugin_slug );
							// Get a list of all backups for this plugin
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
								<button class="button p99-rollback-button" type="button" data-plugin-slug="<?php echo esc_attr( $plugin_slug ); ?>">Rollback</button>
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
		// Get the plugin slug and the selected backup file
		var plugin_slug = $(this).data('plugin-slug');
		var backup_file = $(this).closest('td').find('select').val();
		// Confirm the rollback with the user
		if ( confirm('Are you sure you want to rollback this plugin?') ) {
			// Send an AJAX request to rollback the plugin
			$.ajax({
				type: 'POST',
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				data: {
					action: 'p99_rollback_plugin',
					plugin_slug: plugin_slug,
					backup_file: backup_file
				},
				beforeSend: function() {
     				$('#p99-overlay').show();
 				},
				success: function(response) {
					//alert('The plugin was successfully rolled back.');
					$('#p99-overlay').hide();
					location.reload()
				},
				error: function(error) {
					alert('There was an error rolling back the plugin.');
				}
			});
		}
	});
	$('.p99-download-button').click(function(e) {
		// Get the plugin slug, download URL and plugin filename
		var plugin_slug = $(this).data('plugin-slug');
		var download_url = $(this).data('download-url');
		var download_file = $(this).attr('download');
		// Confirm the update download with the user
		if ( confirm('Are you sure you want to update this plugin?') ) {
			// Send an AJAX request to download the file
			$.ajax({
				type: 'POST',
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				data: {
					action: 'p99_download_plugin_update',
					plugin_slug: plugin_slug,
					download_url: download_url,
					download_file: download_file
				},
				beforeSend: function() {
     				$('#p99-overlay').show();
 				},
				success: function(response) {
					//alert('The plugin was successfully updated.');
					$('#p99-overlay').hide();
					location.reload()
				},
				error: function(error) {
					alert('There was an error downloading the update.');
				}
			});
		}
	});
});
</script>