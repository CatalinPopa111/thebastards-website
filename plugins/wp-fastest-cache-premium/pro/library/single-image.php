<?php



	// 1. Kolon ekle
	add_filter('manage_upload_columns', function ($columns) {
	    $columns['wpfc_image_optimizer'] = 'Image Optimizer';
	    return $columns;
	});

	function wpfc_get_custom_column_html($meta_wpfc_optimisation){
		$json = base64_decode($meta_wpfc_optimisation, true);
		$arr = $json ? json_decode($json, true) : null;

		$bytes = 0;
		$sizes = 0;

		foreach($arr as $item){
		    $bytes += isset($item['reduction']) ? (int)$item['reduction'] : 0;
		}

		$sizes  = count($arr);
	    $kb = round($bytes / 1024, 1);

	    return "<div style=''>{$sizes} sizes compressed</div><div style=''>Reduced by({$kb} KB)</div>";
	}

	// 2. Her satırın içeriğini yaz
	add_action('manage_media_custom_column', function ($column_name, $id) {

	    if ($column_name !== 'wpfc_image_optimizer') {
	        return;
	    }

	    $is_optimized = get_post_meta($id, 'wpfc_optimisation', true);
	    $is_excluded  = get_post_meta($id, '_wpfc_optimize_exclude', true);

	    $nonce = wp_create_nonce('wpfc_single_image_optimize_nonce');

	    // --- ZATEN OPTIMIZE EDİLMİŞSE ---
	    if (!empty($is_optimized)) {


			$json = base64_decode($is_optimized, true);
			$arr = $json ? json_decode($json, true) : null;

			$bytes = 0;
			$sizes = 0;

			foreach($arr as $item){
			    $bytes += isset($item['reduction']) ? (int)$item['reduction'] : 0;
			}

			$sizes  = count($arr);
	        $kb = round($bytes / 1024, 1);

	        echo wpfc_get_custom_column_html($is_optimized);



	        echo "<button 
		            class='button wpfc-revert-image' 
		            data-id='{$id}' 
		            data-nonce='{$nonce}'
		          >Revert</button>";

	        return;
	    }

	    // --- EXCLUDED ise sadece INCLUDE tuşu göster ---
		if ($is_excluded) {
		    echo "<button class='button wpfc-include-image' data-id='{$id}' data-nonce='{$nonce}'>Include</button>";
		    return;
		}

	    // --- OPTIMIZE EDİLMEMİŞ = Optimize + Exclude Göster ---


		echo "<button class='button wpfc-optimize-single' data-id='{$id}' data-nonce='{$nonce}'>Optimize</button>
		      <button class='button wpfc-exclude-image' data-id='{$id}' data-nonce='{$nonce}'>Exclude</button>";



	}, 10, 2);

	// 3. Kolon genişliği (opsiyonel)
	add_action('admin_head', function () {
	    echo "
	    <style>
	        th#wpfc_image_optimizer { width: 200px; }
	        td.wpfc_image_optimizer { white-space: nowrap; }
	    </style>";
	});





	// Attachment Details paneline alan ekle
	add_filter('attachment_fields_to_edit', function ($form_fields, $post) {

	    $is_optimized = get_post_meta($post->ID, 'wpfc_optimisation', true);
	    $is_excluded  = get_post_meta($post->ID, '_wpfc_optimize_exclude', true);

	    $html = "";

	    $nonce = wp_create_nonce('wpfc_single_image_optimize_nonce');

	    if (!empty($is_optimized)) {

	        $nonce = wp_create_nonce('wpfc_revert_image_' . $post->ID);

		    $html  = wpfc_get_custom_column_html($is_optimized);
		    $html .= "<br><button 
		                class='button wpfc-revert-image' 
		                data-id='{$post->ID}' 
		                data-nonce='{$nonce}'
		             >Revert</button>";


	    } elseif ($is_excluded) {

	        $html = "<button class='button wpfc-include-image' data-id='{$post->ID}' data-nonce='{$nonce}'>Include</button>";
	        
	    } else {

	        $opt_btn = "<button class='button wpfc-optimize-single' data-id='{$post->ID}' data-nonce='{$nonce}'>Optimize</button>";
	        $exclude_btn = "<button class='button wpfc-exclude-image' data-id='{$post->ID}' data-nonce='{$nonce}'>Exclude</button>";

	        $html = $opt_btn . " " . $exclude_btn;
	    }

	    $form_fields['wpfc_image_optimizer'] = [
	        'label' => __('Image Optimizer', 'wpfc'),
	        'input' => 'html',
	        'html'  => "<div class='wpfc-attachment-optimizer'>{$html}</div>"
	    ];

	    return $form_fields;
	}, 10, 2);




	add_action('admin_enqueue_scripts', 'wpfc_single_image_optimize_js');
	function wpfc_single_image_optimize_js() {
	    wp_enqueue_script(
	        'wpfc-single-optimize',
	        plugin_dir_url(dirname(__FILE__)) . 'js/single-optimize.js',
	        ['jquery'],
	        false,
	        true
	    );

	    $nonce = wp_create_nonce('wpfc_single_image_optimize_nonce');

	    wp_localize_script('wpfc-single-optimize', 'WPFC_SINGLE_OPTIMIZE', [
	        'ajaxurl' => admin_url('admin-ajax.php'),
	        'nonce' => $nonce
	    ]);
	}


	add_action('wp_ajax_wpfc_optimize_single_image', 'wpfc_optimize_single_image_ajax');

	function wpfc_optimize_single_image_ajax() {

	    if (!isset($_POST['id'], $_POST['nonce'])) {
	        wp_send_json_error(['msg' => 'Missing parameters']);
	    }

	    $id = intval($_POST['id']);

	    if (!wp_verify_nonce($_POST['nonce'], 'wpfc_single_image_optimize_nonce')) {
	        wp_send_json_error(['msg' => 'Security error']);
	    }

	    $_GET["action"] = "wpfastestcache";
	    $_GET["type"] = "optimizeimage";
	    $_GET["security"] = get_option("WpFcImgOptNonce");
	    $_GET["id_manually"] = $id;

	    delete_option("WpFcLastImageId_manually");

	    // Bu fonksiyon her çağrıda progress döndürmeli
	    $result = optimize_manually(); 

	    wp_send_json_success([
	        'progress' => $result["progress"],  // 0-100
	        'id'      => $result["id"]
	    ]);
	}


add_action('wp_ajax_wpfc_revert_image', function () {

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['msg' => 'Permission denied']);
    }

    $id = intval($_POST['id']);

    if (!wp_verify_nonce($_POST['nonce'], 'wpfc_single_image_optimize_nonce' )) {
        wp_send_json_error(['msg' => 'Invalid nonce']);
    }

    // Premium sınıfı load edelim
    if (!class_exists('WpFastestCacheImageOptimisationPremium')) {
        include_once WPFC_WP_PLUGIN_DIR . "/wp-fastest-cache-premium/pro/library/image.php";
    }

    $optim = new WpFastestCacheImageOptimisationPremium();
    $optim->id = $id;

    ob_start();
    $optim->wpfc_revert_image_ajax_request();
    $result = ob_get_clean();

    echo $result;
    wp_die();
});



	/* AJAX handler that determines nearest server and saves it */
	add_action('wp_ajax_wpfc_determine_server', 'wpfc_determine_server_ajax');

	function wpfc_determine_server_ajax() {
	    // permission + nonce check
	    if ( ! current_user_can('upload_files') ) {
	        wp_send_json_error(array('msg' => 'Permission denied'), 403);
	    }

	    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'wpfc_single_image_optimize_nonce') ) {
	        wp_send_json_error(array('msg' => 'Invalid nonce'), 403);
	    }

	    // include optimizer class if not loaded
	    if (!class_exists('WpFastestCacheImageOptimisation')) {
	        $image_lib = WPFC_WP_PLUGIN_DIR . "/wp-fastest-cache-premium/pro/library/image.php";
	        if (file_exists($image_lib)) {
	            include_once($image_lib);
	        }
	    }

	    if (!class_exists('WpFastestCacheImageOptimisation')) {
	        wp_send_json_error(array('msg' => 'Image optimisation library not found'));
	    }

	    $optimizer = new WpFastestCacheImageOptimisation();

	    // get lists
	    $server_list = json_decode($optimizer->get_server_list(), true);
	    $country_list = json_decode($optimizer->get_country_list());

	    // determine country code (same as manual flow)
	    $country_code = $optimizer->get_country_code();

	    if (!$country_code) {
	        wp_send_json_error(array('msg' => 'Could not determine country code'));
	    }

	    // pick servers based on country code (same mapping used elsewhere)
	    if (isset($server_list[$country_code])) {
	        $servers = $server_list[$country_code];
	    } else if (preg_match("/".$country_code."/", $country_list->america)) {
	        $servers = $server_list["na"];
	    } else if (preg_match("/".$country_code."/", $country_list->oceania)) {
	        $servers = $server_list["as"];
	    } else if (preg_match("/".$country_code."/", $country_list->asia)) {
	        $servers = $server_list["as"];
	    } else if (preg_match("/".$country_code."/", $country_list->w_europe)) {
	        $servers = $server_list["euw"];
	    } else if (preg_match("/".$country_code."/", $country_list->e_europe)) {
	        $servers = $server_list["eue"];
	    } else if (preg_match("/".$country_code."/", $country_list->africa)) {
	        $servers = array_merge($server_list["euw"], $server_list["eue"]);
	    } else {
	        // fallback: use all servers
	        $servers = array();
	        foreach ($server_list as $entry) {
	            if (is_array($entry)) {
	                $servers = array_merge($servers, $entry);
	            }
	        }
	    }

	    if (empty($servers)) {
	        wp_send_json_error(array('msg' => 'No servers found for your region'));
	    }

	    // prepare GET-equivalent for existing helper (get_server_time_ajax_request expects $_GET['servers'] and nonce)
	    $_GET['servers'] = $servers;
	    $_GET['nonce'] = wp_create_nonce('wpfc-statics-nonce'); // reuse existing nonce check used in get_server_time_ajax_request

	    // call helper to get response times (function already in your plugin)
	    $server_response_time_arr = get_server_time_ajax_request(true); // returns array of servers with time

	    if (!is_array($server_response_time_arr) || empty($server_response_time_arr)) {
	        wp_send_json_error(array('msg' => 'Could not measure servers'));
	    }

	    // sort by time (use existing order function)
	    usort($server_response_time_arr, 'order_server_response_time');

	    // take fastest and save option
	    $chosen = $server_response_time_arr[0];
	    if (!isset($chosen['url'])) {
	        wp_send_json_error(array('msg' => 'Invalid server response'));
	    }

	    update_option('WpFcServerUrl', array('url' => $chosen['url'], 'time' => time()));

	    wp_send_json_success(array('url' => $chosen['url'], 'ping' => $chosen['time']));
	}




		// Exclude image action
		add_action('wp_ajax_wpfc_exclude_image_ajax', function () {

		    if (!current_user_can('upload_files')) {
		        wp_send_json_error(['msg' => 'Permission denied']);
		    }

		    $id = intval($_POST['id']);

		    if (!wp_verify_nonce($_POST['nonce'], 'wpfc_single_image_optimize_nonce')) {
		        wp_send_json_error(['msg' => 'Invalid nonce']);
		    }

		    update_post_meta($id, '_wpfc_optimize_exclude', 1);

		    wp_send_json_success(['msg' => 'Excluded']);
		});


		// Include image action
add_action('wp_ajax_wpfc_include_image_ajax', function () {

    if (!current_user_can('upload_files')) {
        wp_send_json_error(['msg' => 'Permission denied']);
    }

    $id = intval($_POST['id']);

    if (!wp_verify_nonce($_POST['nonce'], 'wpfc_single_image_optimize_nonce')) {
        wp_send_json_error(['msg' => 'Invalid nonce']);
    }

    delete_post_meta($id, '_wpfc_optimize_exclude');

    wp_send_json_success(['msg' => 'Included']);
});




	add_action('wp_ajax_wpfc_get_optimisation_meta', function () {
	    
	    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'wpfc_single_image_optimize_nonce') ) {
	        wp_send_json_error(array('msg' => 'Invalid nonce'), 403);
	    }

	    $id = intval($_POST['id']);

	    $meta = get_post_meta($id, 'wpfc_optimisation', true);

	    if (!$meta) {
	        wp_send_json_error('Boş meta');
	    }

	    wp_send_json_success([
	        'html' => wpfc_get_custom_column_html($meta)
	    ]);
	});
?>