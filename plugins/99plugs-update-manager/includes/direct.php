<?php
// Ensure backup folder exists
if (!file_exists(WP_CONTENT_DIR . '/99backup/')) {
    wp_mkdir_p(WP_CONTENT_DIR . '/99backup/');
}

// Register AJAX action for admin
add_action('wp_ajax_p99_download_plugin_update', 'p99_download_plugin_update');

function p99_download_plugin_update() {
    // Permission check
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Sanitize input data
    $plugin_slug = sanitize_text_field($_POST['plugin_slug']);
    $download_file = sanitize_text_field($_POST['download_file']);
    $download_url = sanitize_text_field($_POST['download_url']);
    $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($plugin_slug);
    $local_dir = WP_CONTENT_DIR . '/99backup';

    // Ensure backup directory exists
    $backup_folder = WP_CONTENT_DIR . '/99backup/' . dirname($plugin_slug);
    if (!file_exists($backup_folder)) {
        wp_mkdir_p($backup_folder);
    }

    // Plugin data for versioning in backup filename
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug);
    $backup_filename = $backup_folder . '/' . basename($plugin_folder) . '-' . $plugin_data['Version'] . '.zip';

    // Create ZIP archive of the plugin
    $zip = new ZipArchive();
    if ($zip->open($backup_filename, ZipArchive::CREATE)) {
        $zip->addEmptyDir(basename($plugin_folder));
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin_folder), RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $file_path = $file->getRealPath();
                $zip->addFile($file_path, basename($plugin_folder) . '/' . substr($file_path, strlen($plugin_folder) + 1));
            }
        }
        $zip->close();
    }

    // Manage backup retention
    $backups = glob($backup_folder . '/*.zip');
    usort($backups, function($a, $b) { return filemtime($a) < filemtime($b); });
    while (count($backups) > get_option('p99_backups_to_keep')) {
        unlink(array_pop($backups));
    }

    // Prepare for new backup file download
    $zip_file = $local_dir . '/' . $download_file;
    if (file_exists($zip_file)) {
        unlink($zip_file);
    }

    // Securely download the update
    $resource = fopen($zip_file, "w");
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $download_url,
        CURLOPT_FAILONERROR => true,
        CURLOPT_HEADER => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_AUTOREFERER => true,
        CURLOPT_BINARYTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_FILE => $resource,
    ]);
    $download = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    curl_close($ch);
    fclose($resource);

    // Extract, replace, and cleanup
    $zip = new ZipArchive();
    if ($zip->open($zip_file) === true) {
        deactivate_plugins($plugin_slug);
        delete_plugins([$plugin_slug]);
        $zip->extractTo(WP_PLUGIN_DIR);
        $zip->close();
        unlink($zip_file);
        wp_clean_plugins_cache(false);
        $result = activate_plugin($plugin_slug);
        if (is_wp_error($result)) {
            wp_send_json_error();
        } else {
            wp_send_json_success();
        }
    } else {
        wp_die('Error opening ZIP file.');
    }
}
