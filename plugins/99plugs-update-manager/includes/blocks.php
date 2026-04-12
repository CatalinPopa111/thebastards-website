<?php
if (!defined('ABSPATH')) exit;

$pluglist = p99_pluglist();
foreach (['pre_set_site_transient_update_plugins', 'pre_site_transient_update_plugins', 'site_transient_update_plugins'] as $hook) {
  add_filter($hook, function ($transient) use ($pluglist) {
      if (isset($transient) && is_object($transient)) {
          foreach ($pluglist as $plug) {
            if (isset($transient) && is_object($transient) && isset($transient->response[$plug])) {
              if (strpos($transient->response[$plug]->package, '99plugs') === false) {
                unset($transient->response[$plug]);
              }
            }
          }
      }
      return $transient;
  }, PHP_INT_MAX);
}

add_filter('http_request_args', function ($r, $url) use ($pluglist) {
  if (strpos($url, 'https://api.wordpress.org/plugins/update-check/1.1/') === 0) {
    $plugins = json_decode($r['body']['plugins'], true);
    foreach ($pluglist as $plug) {
      if (isset($plugins['plugins'][$plug])) {
        unset($plugins['plugins'][$plug]);
      }
    }
    if (isset($plugins['plugins'][plugin_basename(PMAIN_100)])) {
      unset($plugins['plugins'][plugin_basename(PMAIN_100)]);
    }
    $r['body']['plugins'] = json_encode($plugins);
  }
  return $r;
}, PHP_INT_MAX, 2);
