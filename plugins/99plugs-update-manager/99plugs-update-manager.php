<?php
/*
Plugin Name: 99Plugs Update Manager
Plugin URI: https://99plugs.com
Description: Premium Wordpress Plugins and Themes Updater
Version: 2.4
Author: 99Plugs
Author URI: https://99plugs.com
*/

class p99_main {
    private static $instance = null;

    // Singleton pattern to get an instance of the class
    public static function get_instance() {
        return self::$instance ?? self::$instance = new self();
    }

    // Constructor to initialize the class
    private function __construct() {
        $this->define_constants();
        $this->include_files();
    }

    // Define necessary constants for the plugin
    private function define_constants() {
        define('P99_PLUGIN_PATH', plugin_dir_path(__FILE__));
        define('P99_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('P99_PLUGIN_BASE', plugin_basename(__FILE__));
        define('PMAIN_100_VER', '2.4');
        define('PMAIN_100', __FILE__);
    }

    // Include required files for the plugin
    private function include_files() {
        include_once 'includes/init.php';
        include_once 'includes/ajax.php';
        include_once 'includes/api.php';
        include_once 'includes/blocks.php';
        include_once 'includes/backup.php';
        include_once 'includes/direct.php';

        if (!class_exists('Plugs99_Plugin_Updater')) {
            include_once 'classes/plugin-class.php';
        }

        if (!class_exists('Plugs99_Theme_Updater')) {
            include_once 'classes/theme-class.php';
        }

        include_once 'updater/99updater.php';
    }
}

// Instantiate the main class of the plugin
p99_main::get_instance();

// Deactivation hook to remove scheduled events and actions
register_deactivation_hook(__FILE__, function() {
    $timestamp = wp_next_scheduled('check_keys_weekly');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'check_keys_weekly');
        remove_action('init', 'p99_check_keys_weekly_schedule');
    }
});
?>