<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/*
========================================================
UPDATE KEY ACTIVATION, DEACTIVATION, AND CHECK
========================================================
*/
function plugs99_license_handler() {
    if (isset($_POST['name'])) {
        $name = $_POST['name'];
        $key = '';

        $apikeys = get_option('apikeys');
        foreach ($apikeys as $apikey) {
            if ($apikey['keyname'] === $name) {
                $key = $apikey['keyvalue'];
                break;
            }
        }

        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $edd_action = '';
        switch ($action) {
            case 'plugs99_activate':
                $edd_action = 'activate_license';
                break;
            case 'plugs99_deactivate':
                $edd_action = 'deactivate_license';
                break;
            case 'plugs99_check':
                $edd_action = 'check_license';
                break;
            default:
                wp_die('Invalid action provided.');
                return;
        }

        $api_params = array(
            'edd_action' => $edd_action,
            'license'    => $key,
            'item_name'  => urlencode($name),
            'url'        => home_url()
        );

        $response = wp_remote_post('https://99plugs.com', array(
            'timeout'   => 15,
            'sslverify' => false,
            'body'      => $api_params
        ));

        $data = ['status' => 400, 'message' => 'Something went wrong!', 'state' => ''];

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $license_data = json_decode(wp_remote_retrieve_body($response));
            $license_status = 'Inactive'; // Default to inactive
            $message = 'An error occurred, please try again.';

            switch ($license_data->license) {
                case 'valid':
                    $license_status = "Active";
                    $message = "Your update key is active.";
                    break;
                case 'deactivated':
                    $license_status = "Inactive";
                    $message = 'Deactivated successfully.';
                    break;
                case 'expired':
                    $license_status = "Expired";
                    $message = sprintf('Your update key expired on %s.', date_i18n(get_option('date_format'), strtotime($license_data->expires, current_time('timestamp'))));
                    break;
                case 'disabled':
                case 'revoked':
                    $message = 'Your update key has been disabled.';
                    break;
                case 'inactive':
                case 'site_inactive':
                    $message = 'Your update key is no longer active.';
                    break;
                case 'invalid':
                    if (isset($license_data->error) && $license_data->error === 'expired') {
                        $license_status = "Expired";
                        $message = sprintf('Your update key expired on %s.', date_i18n(get_option('date_format'), strtotime($license_data->expires, current_time('timestamp'))));
                    } else {
                        $message = 'Invalid key. Make sure ITEM NAME and UPDATE KEY match as provided by 99plugs.';
                    }
                    break;
                case 'missing':
                case 'item_name_mismatch':
                    $message = 'Invalid key. Make sure ITEM NAME and UPDATE KEY match as provided by 99plugs.';
                    break;
            }

            if (!empty($apikeys)) {
                foreach ($apikeys as $index => $apikey) {
                    if ($apikey['keyname'] == $name && $apikey['keyvalue'] == $key) {
                        $apikeys[$index]['keystatus'] = $license_status;
                    }
                }
                update_option('apikeys', $apikeys);
            }

            $data['status'] = 200;
            $data['message'] = $message;
            $data['state'] = $license_status;
        } else {
            $data['message'] = is_wp_error($response) ? $response->get_error_message() : 'An error occurred, please try again.';
        }

        echo json_encode($data);
        wp_die();
    }
}

add_action('wp_ajax_plugs99_activate', 'plugs99_license_handler');
add_action('wp_ajax_plugs99_deactivate', 'plugs99_license_handler');
add_action('wp_ajax_plugs99_check', 'plugs99_license_handler');

/*
========================================================
UPDATE KEY RENEW
========================================================
*/
function plugs99_renew() {
  // Check if 'name' is set in POST request
  if (isset($_POST['name'])) {
      $name = sanitize_text_field($_POST['name']);
      $apikeys = get_option('apikeys');
      
      // Search for matching key name and echo the URL, then exit
      foreach ((array) $apikeys as $apikey) {
          if ($apikey['keyname'] == $name) {
              echo 'https://99plugs.com/checkout/?edd_license_key=' . urlencode($apikey['keyvalue']);
              wp_die(); // Terminate execution and return
          }
      }
  }
  wp_die(); // Ensure function terminates if no key is found or 'name' isn't set
}
add_action('wp_ajax_plugs99_renew', 'plugs99_renew');

/*
========================================================
UPDATE KEY AUTO CHECK
========================================================
*/
function p99_check_keys_weekly_schedule() {
  if ( ! wp_next_scheduled( '99plugs_check_keys_weekly' ) ) {
      wp_schedule_event( time(), 'weekly', '99plugs_check_keys_weekly' );
  }
}
function p99_auto_keys_check() {
  $apikeys = get_option('apikeys');
  if (empty($apikeys)) {
    echo json_encode(['status' => 400, 'message' => 'Something went wrong!']);
    return; // Exit if no API keys
  }
  
  foreach ($apikeys as $index => $apikey) {
    $response = wp_remote_post('https://99plugs.com', [
      'timeout' => 15,
      'sslverify' => false,
      'body' => [
        'edd_action' => 'check_license',
        'license' => $apikey['keyvalue'],
        'item_name' => urlencode($apikey['keyname']),
        'url' => home_url()
      ]
    ]);

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
      $license_data = json_decode(wp_remote_retrieve_body($response));
      if ($license_data->license == 'valid') {
        $license_status = "Active";
      } else {
        $license_status = "Inactive";

        switch ($license_data->license) {
          case 'valid':
            $license_status = "Active";
            break;
          case 'deactivated':
            $license_status = "Inactive";
            break;
          case 'expired':
            $license_status = "Expired";
            update_option('expired_notification', true);
            update_option('expired_notification_dismissed', false);
            break;
          case 'invalid':
            if (isset($license_data->error) && $license_data->error === 'expired') {
              $license_status = "Expired";
              update_option('expired_notification', true);
              update_option('expired_notification_dismissed', false);
            }
        }
      }

      $apikeys[$index]['keystatus'] = $license_status;

      update_option('apikeys', $apikeys );
    }
  }
}

  function p99_expiry_notification() {
    if ( get_option( 'expired_notification' ) && ! get_option( 'expired_notification_dismissed' ) ) {
        echo '<div class="notice notice-warning is-dismissible" id="p99-expiration-notice">';
        echo '<p>' . __('One or more update keys have expired; ');
        echo '<a href="'. admin_url( 'tools.php?page=p99-manager' ). '" class="p99-renew-now">Renew Now</a> ';
        echo __('to continue receiving plugin updates from 99Plugs.');
        echo '</p>';
        echo '</div>';
    }
  }

function p99_notify_admin_of_expiry() {
  if ( current_user_can( 'install_plugins' ) ) {
      add_action( 'admin_notices', 'p99_expiry_notification' );
  }
}

function p99_dismiss_notification() {
  if ( ! isset( $_POST['p99_nonce'] ) || ! wp_verify_nonce( $_POST['p99_nonce'], 'p99_notification_nonce' ) ) {
    return;
  }
  update_option( 'expired_notification_dismissed', true );
  wp_die();
}

add_action( 'wp_ajax_p99_dismiss_notification', 'p99_dismiss_notification' );
add_action( 'wp_ajax_nopriv_p99_dismiss_notification', 'p99_dismiss_notification' );

add_action( 'init', 'p99_check_keys_weekly_schedule' );
add_action( '99plugs_check_keys_weekly', 'p99_auto_keys_check' );
add_action( 'init', 'p99_notify_admin_of_expiry' );