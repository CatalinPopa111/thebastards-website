<?php
// Initialize custom plugin settings
function p99_settings_init() {
    register_setting('p99_settings', 'p99_items_to_show', ['default' => 'All']);
    register_setting('p99_settings', 'p99_enable_backups', ['default' => 'on']);
    register_setting('p99_settings', 'p99_items_to_backup', ['default' => 'All']);
    register_setting('p99_settings', 'p99_backups_to_keep', ['default' => '3']);
}
add_action('admin_init', 'p99_settings_init');

// Ensure backup directory exists
if (!file_exists(WP_CONTENT_DIR . '/99backup/')) {
    wp_mkdir_p(WP_CONTENT_DIR . '/99backup/');
}

// AJAX action for plugin rollback
add_action('wp_ajax_p99_rollback_plugin', 'p99_rollback_plugin');
function p99_rollback_plugin() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions.');
    }
    $plugin_slug = sanitize_text_field($_POST['plugin_slug']);
    $backup_file = sanitize_text_field($_POST['backup_file']);

    $zip = new ZipArchive;
    if ($zip->open($backup_file) !== true) {
        wp_die('Error Opening ZIP File');
    }
    deactivate_plugins($plugin_slug);
    delete_plugins([$plugin_slug]);
    $zip->extractTo(WP_PLUGIN_DIR);
    $zip->close();
    wp_clean_plugins_cache(false);
    $result = activate_plugin($plugin_slug);
    if (is_wp_error($result)) {
        wp_send_json_error();
    } else {
        wp_send_json_success();
    }
}

// Backup plugin before update
add_action('upgrader_pre_install', 'p99_backup_plugin_before_update', 10, 2);
function p99_backup_plugin_before_update($true, $args) {
    if (!empty($args['plugin']) && get_option('p99_enable_backups')) {
        $plugin = plugin_basename($args['plugin']);
        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($plugin);
        $backup_folder = WP_CONTENT_DIR . '/99backup/' . basename($plugin_folder);
        if (!file_exists($backup_folder)) wp_mkdir_p($backup_folder);
        $plugin_data = get_plugin_data($main_plugin_file = WP_PLUGIN_DIR . '/' . $plugin);
        $backup_filename = $backup_folder . '/' . basename($plugin_folder) . '-' . $plugin_data['Version'] . '.zip';
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
        manage_backup_retention($backup_folder, get_option('p99_backups_to_keep'));
    }
    return $true;
}

// AJAX action for theme rollback
add_action('wp_ajax_p99_rollback_theme', 'p99_rollback_theme');
function p99_rollback_theme() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions.');
    }
    $theme_slug = sanitize_text_field($_POST['theme_slug']);
    $backup_file = sanitize_text_field($_POST['backup_file']);

    $zip = new ZipArchive;
    if ($zip->open($backup_file) !== true) {
        wp_die('Error Opening ZIP File');
    }
    switch_theme(WP_DEFAULT_THEME);
    delete_theme($theme_slug);
    $zip->extractTo(get_theme_root());
    $zip->close();
    wp_clean_themes_cache(false);
    $result = switch_theme($theme_slug);
    if (is_wp_error($result)) {
        wp_send_json_error();
    } else {
        wp_send_json_success();
    }
}

// Backup theme before update
add_action('upgrader_pre_install', 'p99_backup_theme_before_update', 10, 2);
function p99_backup_theme_before_update($true, $args) {
    if (!empty($args['theme'])) {
        $theme = $args['theme'];
        $theme_folder = get_theme_root() . '/' . $theme;
        $backup_folder = WP_CONTENT_DIR . '/99backup/' . $theme;
        if (!file_exists($backup_folder)) wp_mkdir_p($backup_folder);
        $theme_data = wp_get_theme($theme);
        $backup_filename = $backup_folder . '/' . $theme . '-' . $theme_data->get('Version') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($backup_filename, ZipArchive::CREATE)) {
            $zip->addEmptyDir($theme);
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme_folder), RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $file_path = $file->getRealPath();
                    $zip->addFile($file_path, $theme . '/' . substr($file_path, strlen($theme_folder) + 1));
                }
            }
            $zip->close();
        }
        manage_backup_retention($backup_folder, get_option('p99_backups_to_keep'));
    }
    return $true;
}

// Helper function to manage backup retention
function manage_backup_retention($backup_folder, $backups_to_keep) {
    $backups = glob($backup_folder . '/*.zip');
    usort($backups, function($a, $b) {
        return filemtime($a) < filemtime($b);
    });
    while (count($backups) > $backups_to_keep) {
        unlink(array_pop($backups));
    }
}