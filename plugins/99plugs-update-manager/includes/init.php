<?php
// Hook to add submenu under Tools in WP admin
add_action('admin_menu', function() {
  // Adds a submenu page under Tools menu in WordPress dashboard
  add_submenu_page('tools.php', '99Plugs', '99Plugs', 'manage_options', 'p99-manager', function() {
    // Includes the tabs.php file from the plugin's views folder when the submenu page is accessed
    include( P99_PLUGIN_PATH . '/views/tabs.php' );
  });
});

// Hook to enqueue scripts and styles in the admin area
add_action('admin_enqueue_scripts', function() {
  // Enqueue custom style for the plugin
  wp_enqueue_style('p99-style-css', P99_PLUGIN_URL . 'assets/css/style.css',array(), PMAIN_100_VER, 'all');
  // Enqueue JavaScript files for AJAX form handling and custom functionality
  wp_enqueue_script('p99-ajaxForm-js', P99_PLUGIN_URL . 'assets/js/ajaxForm.js');
  wp_enqueue_script('p99-custom-js', P99_PLUGIN_URL . 'assets/js/custom.js', array(), PMAIN_100_VER, true);
  // Localize script for AJAX requests, providing URL and nonce for security
  wp_localize_script('p99-custom-js', 'jsObject', ['ajaxURL' => admin_url('admin-ajax.php'), 'p99_nonce' => wp_create_nonce('p99_notification_nonce')]);
});

// Hook to add custom links to the plugin action links
add_filter('plugin_action_links_'.P99_PLUGIN_BASE, function($links) {
  // Adds a custom link to the plugin action links array
  $links[] = '<a href="' . admin_url('tools.php?page=p99-manager') . '">' . __('Update Keys') . '</a>';
  return $links;
});

// Hook to prioritize the plugin to load it first after activation
add_action('activated_plugin', function() {
  // Constructs plugin path from the current file
  $path = plugin_basename(dirname(__FILE__)) . '/' . basename(__FILE__);
  // Get currently active plugins
  if ($plugins = get_option('active_plugins')) {
    // Find the plugin in the list and remove it
    if ($key = array_search($path, $plugins)) {
      array_splice($plugins, $key, 1);
      // Re-add the plugin at the beginning to prioritize its load order
      array_unshift($plugins, $path);
      update_option('active_plugins', $plugins);
    }
  }
});

// Function to fetch API keys, avoiding duplicates
if (!function_exists('p99_fetchkey')) {
  function p99_fetchkey($name) {
    if (empty($name)) return;
    $apikeys = get_option('apikeys');
    if (empty($apikeys)) return;
    foreach ($apikeys as $apikey) {
      if (in_array($name, $apikey) || in_array('VIP Access Pass (Lifetime)', $apikey) || in_array('VIP Access Pass (365 Days)', $apikey)) {
        return isset($apikey['keyvalue']) ? $apikey['keyvalue'] : '';
      }
    }
    return 'NOKEYFOUND';
  }
}

// Ensure the get_plugins function is available
if (!function_exists('get_plugins')) {
  require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Function to list plugins that include a specific folder
function p99_pluglist() {
  $pluglist = [];
  $plugins = get_plugins();
  foreach ($plugins as $key => $value) {
    if (file_exists(WP_PLUGIN_DIR . '/' . explode('/', $key)[0] . '/99plugs')) {
      $pluglist[] = $key;
    }
  }
  return $pluglist;
}

// Function to check for duplicates in an array or sub-array
if (!function_exists('p99_dupechk')) {
  function p99_dupechk($needle, $haystack, $strict = false) {
    if (!is_array($haystack)) return false;
    foreach ($haystack as $item) {
      if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && p99_dupechk($needle, $item, $strict))) {
        return true;
      }
    }
    return false;
  }
}
