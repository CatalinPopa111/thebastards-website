<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

if (class_exists('Plugs99_Plugin_Updater') && function_exists('p99_fetchkey')) {
    add_action('init', function() {
        if (!current_user_can('manage_options') && !(defined('DOING_CRON') && DOING_CRON)) {
            return;
        }
        $key = p99_fetchkey('WP Fastest Cache Premium');
        new Plugs99_Plugin_Updater('https://99plugs.com/', PMAIN_5172, [
            'version'   => "1.7.6",
            'license'   => $key,
            'item_id'   => "5172",
            'author'    => "Emre Vona",
            'url'       => home_url()
        ]);
    });
}

foreach (['pre_set_site_transient_update_plugins', 'pre_site_transient_update_plugins', 'site_transient_update_plugins'] as $hook) {
  add_filter($hook, function ($transient) {
    if (isset($transient) && is_object($transient) && isset($transient->response[plugin_basename(PMAIN_5172)])) {
      if (strpos($transient->response[plugin_basename(PMAIN_5172)]->package, '99plugs') === false) {
        unset($transient->response[plugin_basename(PMAIN_5172)]);
      }
    }
    return $transient;
  });
}

add_filter('http_request_args', function ($r, $url) {
    if (strpos($url, 'https://api.wordpress.org/plugins/update-check/1.1/') === 0) {
        $plugins = json_decode($r['body']['plugins'], true);
        unset($plugins['plugins'][plugin_basename(PMAIN_5172)]);
        $r['body']['plugins'] = json_encode($plugins);
    }
    return $r;
}, PHP_INT_MAX, 2);

register_activation_hook(PMAIN_5172, function() {
    $apikeys = get_option('apikeys', []);
    $keyname = 'WP Fastest Cache Premium';
    $existing_keys = array_column($apikeys, 'keyname');
    if (empty(array_intersect(["VIP Access Pass (365 Days)", "VIP Access Pass (Lifetime)", $keyname], $existing_keys))) {
        $apikeys[] = ['keyname' => $keyname, 'keyvalue' => '', 'keystatus' => ''];
        update_option('apikeys', $apikeys);
    }
});
