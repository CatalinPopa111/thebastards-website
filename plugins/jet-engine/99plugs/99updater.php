<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

if (class_exists('Plugs99_Plugin_Updater') && function_exists('p99_fetchkey')) {
    add_action('init', function() {
        if (!current_user_can('manage_options') && !(defined('DOING_CRON') && DOING_CRON)) {
            return;
        }
        $key = p99_fetchkey('JetEngine For Elementor');
		$ns_hook = __NAMESPACE__ . '\\Plugs99_Plugin_Updater';
        new $ns_hook('https://99plugs.com/', PMAIN_1322, [
            'version'   => "3.8.6.2",
            'license'   => $key,
            'item_id'   => "1322",
            'author'    => "Crocoblock",
            'url'       => home_url()
        ]);
    });
}

foreach (['pre_set_site_transient_update_plugins', 'pre_site_transient_update_plugins', 'site_transient_update_plugins'] as $hook) {
  add_filter($hook, function ($transient) {
    if (isset($transient) && is_object($transient) && isset($transient->response[plugin_basename(PMAIN_1322)])) {
      if (strpos($transient->response[plugin_basename(PMAIN_1322)]->package, '99plugs') === false) {
        unset($transient->response[plugin_basename(PMAIN_1322)]);
      }
    }
    return $transient;
  });
}

add_filter('http_request_args', function ($r, $url) {
    if (strpos($url, 'https://api.wordpress.org/plugins/update-check/1.1/') === 0) {
        $plugins = json_decode($r['body']['plugins'], true);
        unset($plugins['plugins'][plugin_basename(PMAIN_1322)]);
        $r['body']['plugins'] = json_encode($plugins);
    }
    return $r;
}, PHP_INT_MAX, 2);

register_activation_hook(PMAIN_1322, function() {
    $apikeys = get_option('apikeys', []);
    $keyname = 'JetEngine For Elementor';
    $existing_keys = array_column($apikeys, 'keyname');

    if (empty(array_intersect(["VIP Access Pass (365 Days)", "VIP Access Pass (Lifetime)", $keyname], $existing_keys))) {
        $apikeys[] = ['keyname' => $keyname, 'keyvalue' => '', 'keystatus' => ''];
        update_option('apikeys', $apikeys);
    }
});
