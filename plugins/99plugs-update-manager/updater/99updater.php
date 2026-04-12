<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

if (class_exists('Plugs99_Plugin_Updater') && function_exists('p99_fetchkey')) {
    add_action('init', function() {
        if (!current_user_can('manage_options') && !(defined('DOING_CRON') && DOING_CRON)) {
            return;
        }
        $key = p99_fetchkey('99Plugs Update Manager');
        new Plugs99_Plugin_Updater('https://99plugs.com/', PMAIN_100, [
            'version'   => PMAIN_100_VER,
            'license'   => $key,
            'item_id'   => "100",
            'author'    => "99Plugs",
            'url'       => home_url()
        ]);
    });
}

register_activation_hook(PMAIN_100, function() {
    $apikeys = get_option('apikeys', []);
    $keyname = '99Plugs Update Manager';
    $keynames = array_column($apikeys, 'keyname');
    if (!in_array($keyname, $keynames)) {
        $apikeys[] = ['keyname' => $keyname, 'keyvalue' => '', 'keystatus' => ''];
        update_option('apikeys', $apikeys);
    }
});
