<?php
define('PMAIN_5172',__FILE__);
include dirname(__FILE__).'/99plugs/99updater.php';
?>
<?php
/*
Plugin Name: WP Fastest Cache Premium
Plugin URI: http://www.wpfastestcache.com/
Description: The Premium Version of WP Fastest Cache
Version: 1.7.6
Author: Emre Vona
Author URI: http://tr.linkedin.com/in/emrevona 
Licence: GPLv2

Copyright (C)2014 Emre Vona
*/
	if (!defined('WPFC_WP_CONTENT_BASENAME')) {
		if (!defined('WPFC_WP_PLUGIN_DIR')) {
			if(preg_match("/(\/trunk\/|\/wp-fastest-cache-premium\/)$/", plugin_dir_path( __FILE__ ))){
				define("WPFC_WP_PLUGIN_DIR", preg_replace("/(\/trunk\/|\/wp-fastest-cache-premium\/)$/", "", plugin_dir_path( __FILE__ )));
			}else if(preg_match("/\\\wp-fastest-cache-premium\/$/", plugin_dir_path( __FILE__ ))){
				//D:\hosting\LINEapp\public_html\wp-content\plugins\wp-fastest-cache/
				define("WPFC_WP_PLUGIN_DIR", preg_replace("/\\\wp-fastest-cache-premium\/$/", "", plugin_dir_path( __FILE__ )));
			}
		}

		define("WPFC_WP_CONTENT_DIR", dirname(WPFC_WP_PLUGIN_DIR));
		define("WPFC_WP_CONTENT_BASENAME", basename(WPFC_WP_CONTENT_DIR));
	}
	

	if(!isset($GLOBALS["wp_fastest_cache_options"])){
		if($wp_fastest_cache_options = get_option("WpFastestCache")){
			$GLOBALS["wp_fastest_cache_options"] = json_decode($wp_fastest_cache_options);
		}else{
			$GLOBALS["wp_fastest_cache_options"] = array();
		}
	}

	add_action('wp_ajax_wpfc_image_credit_template_ajax_request', "wpfc_image_credit_template_ajax_request");

	add_action('wp_ajax_get_server_time_ajax_request', "get_server_time_ajax_request");

	add_filter('script_loader_tag', 'lazy_load_for_script', 10, 1);

	add_action('wp_ajax_wpfc_db_set_auto_cleanup', 'wpfc_db_set_auto_cleanup_callback');

	add_action("wpfc_db_auto_cleanup",  'execute_auto_cleanup', 10);

	add_action('parse_request', 'optimize_manually', 10);
 

	function optimize_manually(){
		if(isset($_GET["action"]) && $_GET["action"] == "wpfastestcache"){
			if(isset($_GET["type"]) && $_GET["type"] == "optimizeimage"){

				if(!isset($_GET["security"])){
					die("Security Check Failure!");
				}

				if($_GET["security"] != get_option("WpFcImgOptNonce")){
					die("Security Check Failure!");
				}

				include_once(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/library/image.php");

				$optimizer = new WpFastestCacheImageOptimisation();
				$country_code = $optimizer->get_country_code();
				$server_list = json_decode($optimizer->get_server_list(), true);
				$country_list = json_decode($optimizer->get_country_list());

				if(!$country_code){
					die("Undefined Country Code: <a target='_blank' href='https://www.wpfastestcache.com/contact-us/'>Please contact us via email</a>");
				}

				if($server_arr = get_option("WpFcServerUrl")){
					$server_url = $server_arr["url"];

					if((time()-$server_arr["time"]) > 60*60*24){
						delete_option("WpFcServerUrl");
						
						die("Refresh the page please...");
					}else{
						$determined_server_response_time = get_server_time($server_url);

						if($determined_server_response_time["time"] > 1){
							delete_option("WpFcServerUrl");

							die("Refresh the page please...");
						}
					}
				}else{
					
					if(isset($server_list[$country_code])){
					    $_GET["servers"] = $server_list[$country_code];
					}else if(preg_match("/".$country_code."/", $country_list->america)){
						$_GET["servers"] = $server_list["na"];
					}else if(preg_match("/".$country_code."/", $country_list->oceania)){
						$_GET["servers"] = $server_list["as"];
					}else if(preg_match("/".$country_code."/", $country_list->asia)){
						$_GET["servers"] = $server_list["as"];
					}else if(preg_match("/".$country_code."/", $country_list->w_europe)){
						$_GET["servers"] = $server_list["euw"];
					}else if(preg_match("/".$country_code."/", $country_list->e_europe)){
						$_GET["servers"] = $server_list["eue"];
					}else if(preg_match("/".$country_code."/", $country_list->africa)){
						$_GET["servers"] = array_merge($server_list["euw"], $server_list["eue"]);
					}

					$_GET["nonce"] = wp_create_nonce('wpfc-statics-nonce');

					$server_response_time_arr = get_server_time_ajax_request(true);

					usort($server_response_time_arr, "order_server_response_time");

					add_option("WpFcServerUrl", array("url" => $server_response_time_arr[0]["url"], "time" => time()), null, "yes");

					die("Nearest Server Found: ".$server_response_time_arr[0]["url"]);
				}

				$_GET["server_url"] = $server_url;
				
				$res = $optimizer->optimizeFirstImage();

				if($res[1] == "success"){
					if($res[0] == "finish"){
						die("All images have been optimized successfully!");
					}else{
						$id = $res[2];
						$percentage = $res[3];

						if(isset($_GET["id_manually"])){
							return array("id" => $id, "progress" => round($percentage, 2));
						}
						
						echo "Site Location: ".$country_code."\n<br>";
						echo "Server: ".$server_url."\n<br>";
						echo "Img ID: ".$id."\n<br>";
						echo "Progress: ".round($percentage, 2)."%\n<br>";

						if($percentage == 100){
							delete_option("WpFcLastImageId");
						}

					}
				}else{
					print_r($res);
				}
				
				exit;
			}
		}
	}


	function order_server_response_time($a,$b){
		return ($a["time"]["time"] <= $b["time"]["time"]) ? -1 : 1;
	}

	function execute_auto_cleanup(){
		include_once(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/library/db.php");
		WpFastestCacheDatabaseCleanup::clean("all_warnings");
	}

	function wpfc_db_set_auto_cleanup_callback(){
		include_once(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/library/db.php");
		WpFastestCacheDatabaseCleanup::set_schedule_event();
	}

	function lazy_load_for_script($tag){
		$src = false;

		if(preg_match("/src\s*\=\s*[\"\']([^\"\']+sb-instagram\.min\.js)/", $tag, $out)){
			$src = $out[1];
		}

		if($src){
			$tmp_script =  '<script type="text/javascript">'.
							"window.addEventListener('scroll',function(){".
							"(function(d,s){".
							"if(document.querySelectorAll("."\"script[src='\""." + s + "."\"']\"".").length > 0){return;}".
							"var t = d.createElement('script');".
							't.setAttribute("src", s);'.
							"d.body.appendChild(t);".
							'})(document, "'.$src.'");'.
							"});".
							"</script>\n";

			return $tmp_script;
		}else{
			return $tag;
		}
	}

	function get_server_time_ajax_request($return_arr = false){
		if( ! isset($_GET['nonce']) || ! wp_verify_nonce($_GET['nonce'], 'wpfc-statics-nonce') ){
	        wp_send_json_error(array("error" => "Invalid nonce"), 403);
	        return;
	    }

	    
		foreach ((array)$_GET["servers"] as $key => $value) {
			$_GET["servers"][$key]["time"] = get_server_time($value["url"]);

			if($_GET["servers"][$key]["time"]["time"] === 0){
				unset($_GET["servers"][$key]);
			}
		}
		if($return_arr){
			return $_GET["servers"];
		}else{
			wp_send_json($_GET["servers"]);
		}
	}

	function get_server_time($url){
		$result = array("success" => true,
						"time" => 0);

		if(function_exists("fsockopen")){
			$port = preg_match("/^https/", $url) ? 443 : 80;

			$url = preg_replace("/https?\:\/\//", "", $url);

		    $starttime = microtime(true);

		    $file      = @fsockopen($url, 443, $errno, $errstr, 1);
		    $stoptime  = microtime(true);
		    $status    = 0;

		    //echo $stoptime."\n\n";

		    if (!$file){
		        $status = 1000;  // Site is down
		    }else{
		        fclose($file);
		        $status = ($stoptime - $starttime);
		    }

		    $result["time"] = round($status, 3);

		}else if(function_exists("curl_init")){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 1);

			if(curl_exec($ch)){
				$info = curl_getinfo($ch);
			}

			curl_close($ch);

			if(isset($info["http_code"]) && ($info["http_code"] == 200)){
				$result["time"] = round($info["total_time"], 3);
			}else{
				$result["time"] = 1000;
			}
		}else{
			$result["time"] = 0;
			$result["success"] = false;
		}

		return $result;
	}

	function wpfc_image_credit_template_ajax_request(){
		include_once(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/templates/buy_credit.php");
		exit;
	}

    


	if(!is_admin()){
		if(isset($GLOBALS["wp_fastest_cache_options"]->wpFastestCacheStatus)){
			if(isset($GLOBALS["wp_fastest_cache_options"]->wpFastestCacheLazyLoad)){
				include_once plugin_dir_path( __FILE__ )."pro/library/lazy-load.php";
				
				$lazy = new WpFastestCacheLazyLoad();

				if(isset($GLOBALS["wp_fastest_cache_options"]->wpFastestCacheLazyLoad_exclude_full_size_img)){
					add_filter( 'wp_get_attachment_image_attributes', array($lazy, "mark_attachment_page_images"), 10, 2);
					add_filter( 'the_content', array($lazy, "mark_content_images"), 99);
				}
			}
		}

		if(isset($GLOBALS["wp_fastest_cache_options"]->wpFastestCacheStatus)){
			if(isset($GLOBALS["wp_fastest_cache_options"]->wpFastestCacheWidgetCache)){
				include_once plugin_dir_path( __FILE__ )."pro/library/widget-cache.php";

				WpfcWidgetCache::action();
			}
		}

		if(isset($GLOBALS["wp_fastest_cache_options"]->wpFastestCacheMobileTheme) && $GLOBALS["wp_fastest_cache_options"]->wpFastestCacheMobileTheme){
			if(isset($GLOBALS["wp_fastest_cache_options"]->wpFastestCacheMobileTheme_themename) && $GLOBALS["wp_fastest_cache_options"]->wpFastestCacheMobileTheme_themename){
				add_action('plugins_loaded', 'wpfc_mts_init', 1);
				
				function wpfc_mts_init(){
					if(isset($GLOBALS['wp_fastest_cache']) && method_exists($GLOBALS['wp_fastest_cache'], 'get_operating_systems')){
						$is_mobile = false;

						foreach ($GLOBALS['wp_fastest_cache']->get_mobile_browsers() as $value) {
							if(preg_match("/".$value."/i", $_SERVER['HTTP_USER_AGENT'])){
								$is_mobile = true;
							}
						}

						foreach ($GLOBALS['wp_fastest_cache']->get_operating_systems() as $key => $value) {
							if(preg_match("/".$value."/i", $_SERVER['HTTP_USER_AGENT'])){
								$is_mobile = true;
							}
						}
					}

					if($is_mobile){
						$themes = wp_get_themes();
						$GLOBALS["wp_fastest_cache_mobile_theme_obj"] = $themes[$GLOBALS["wp_fastest_cache_options"]->wpFastestCacheMobileTheme_themename];

						add_filter('stylesheet', 'wpfc_load_mobile_style');
						add_filter('template', 'wpfc_load_mobile_theme');
					}
				}

				function wpfc_load_mobile_style(){
					return $GLOBALS["wp_fastest_cache_mobile_theme_obj"]->get_template();
				}

				function wpfc_load_mobile_theme(){
					return $GLOBALS["wp_fastest_cache_mobile_theme_obj"]->get_stylesheet();
				}
			}
		}
	}else{
		add_action('delete_attachment', 'wpfc_delete_webp');
		add_filter( 'plugin_action_links_wp-fastest-cache-premium/wpFastestCachePremium.php', "wpfc_plugin_action_links");

		function wpfc_plugin_action_links($links){
			if(!isset($GLOBALS["wp_fastest_cache"])){
				array_unshift( $links,
					'<a style="color:red;font-weight:bold;" target="_blank" href="https://www.wpfastestcache.com/download-the-premium/free-plugin-must-be-installed-as-well/">***FREE Plugin Must be Installed As Well***</a>');
			}

			return $links;
		}

		function wpfc_delete_webp($id){
			if(isset($id)){
				$data = wp_get_attachment_metadata($id);

				if(isset($data["file"])){
					$path = WPFC_WP_CONTENT_DIR."/uploads/".dirname($data["file"]);

					if(file_exists($path."/".basename($data["file"]).".webp")){
						@unlink($path."/".basename($data["file"]).".webp");
					}

					if(isset($data["file"])){
						foreach((array)$data["sizes"] as $key => $value){
							if(file_exists($path."/".$value["file"].".webp")){
								@unlink($path."/".$value["file"].".webp");
							}
						}
					}
				}
			}
		}

		include_once plugin_dir_path( __FILE__ )."pro/library/update.php";
		$wpfc_update_checker = new WpFastestCacheUpdate();

		include_once plugin_dir_path( __FILE__ )."pro/library/widget-cache.php";
		WpfcWidgetCache::add_filter_admin();

		//include_once WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/library/admin.php";
		//$wpfcpa = new WPFC_PREMIUM_ADMIN();

        include_once plugin_dir_path( __FILE__ )."pro/library/single-image.php";   
	}
?>