<?php
	class WpFastestCacheStatics{
		private $extension = false;
		private $size = false;

		public function __construct($extension = false, $size = false){
			$this->extension = $extension;
			$this->size = $size;
		}

		public function update_db(){
			$option_name = "WpFastestCache".strtoupper($this->extension);
			$option_name_for_size = $option_name."SIZE";

			if($current = get_option($option_name)){
				$current = $current + 1;
				update_option($option_name, $current);
			}else{
				add_option($option_name, 1, null, "yes");
			}

			if($current_size = get_option($option_name_for_size)){
				$current_size = $current_size + $this->size;
				update_option($option_name_for_size, $current_size);
			}else{
				add_option($option_name_for_size, $this->size, null, "yes");
			}
		}
		public function statics(){
			include_once(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/templates/cache-statics.html");
		}

		public function get(){
		    $arr = array(
		        "desktop" => array(
		            "size" => $this->formatSize(get_option("WpFastestCacheHTMLSIZE")),
		            "file" => get_option("WpFastestCacheHTML")
		        ),
		        "mobile" => array(
		            "size" => $this->formatSize(get_option("WpFastestCacheMOBILESIZE")),
		            "file" => get_option("WpFastestCacheMOBILE")
		        ),
		        "js" => array(
		            "size" => $this->formatSize(get_option("WpFastestCacheJSSIZE")),
		            "file" => get_option("WpFastestCacheJS")
		        ),
		        "css" => array(
		            "size" => $this->formatSize(get_option("WpFastestCacheCSSSIZE")),
		            "file" => get_option("WpFastestCacheCSS")
		        )
		    );

		    return $arr;
		}

		private function formatSize($bytes){
		    if(!$bytes || !is_numeric($bytes)){
		        return "0 KB";
		    }

		    $kb = $bytes / 1024;
		    if($kb < 1024){
		        return number_format($kb, 2) . " KB";
		    } else {
		        $mb = $kb / 1024;
		        return number_format($mb, 2) . " MB";
		    }
		}


	}
?>