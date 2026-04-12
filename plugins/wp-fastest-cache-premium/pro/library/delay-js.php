<?php
	class WpFastestCacheDelayJS{
		private $html = "";
		private $tags = array();
		private $external_js_list = array();
		private $inline_js_list = array();
		private $timeout = false;

		public function __construct($html){
			$this->html = $html;

			$this->set_external_js_list();

			$this->tags = $this->find_tags("<script", "</script>");
		}

		public function set_external_js_list(){

			array_push($this->external_js_list, "\/\/apps\.elfsight\.com\/p\/platform\.js");
			array_push($this->external_js_list, "\/\/app\.eu\.usercentrics\.eu\/browser-ui\/latest\/loader.js");

			/*	
				//cdn.iubenda.com/cs/gpp/stub.js
				//cdn.iubenda.com/cs/tcf/stub-v2.js
				//cdn.iubenda.com/cs/tcf/safe-tcf-v2.js
				//cdn.iubenda.com/cs/iubenda_cs.js
			*/
			array_push($this->external_js_list, "\/\/cdn\.iubenda\.com\/cs\/[^\'\"]+\.js");


			array_push($this->external_js_list, "\/\/cdn\-cookieyes\.com\/client_data\/[^\/]+\/script\.js"); // https://cdn-cookieyes.com/client_data/98d628abbf56ac1b21091a7c/script.js
			
			array_push($this->external_js_list, "\/\/cdn\.commoninja\.com\/sdk\/latest\/commonninja\.js"); // https://cdn.commoninja.com/sdk/latest/commonninja.js


			array_push($this->external_js_list, "\/\/www\.?chatbase\.co\/embed\.min\.js"); // <script src="https://www.chatbase.co/embed.min.js" chatbotId="q2lLmbaHlJhveV" domain="www.chatbase.co" defer></script>



			array_push($this->external_js_list, "\/\/cookie\-cdn\.cookiepro\.com\/scripttemplates\/otSDKStub.js"); // https://cookie-cdn.cookiepro.com/scripttemplates/otSDKStub.js
			array_push($this->external_js_list, "\/\/cookie\-cdn\.cookiepro\.com\/consent\/[^\/]+\/OtAutoBlock\.js"); // https://cookie-cdn.cookiepro.com/consent/4bc6aca4-873d-44d4-bbc4-fdd2bb2a9309/OtAutoBlock.js

			array_push($this->external_js_list, "\/\/consent\.cookiebot\.com\/uc\.js"); // <script id="Cookiebot" src="https://consent.cookiebot.com/uc.js" data-cbid="65fadc-a00-4e9b-a483-e0b8ddf81d" async></script>


			array_push($this->external_js_list, "\/\/downloads\.mailchimp\.com\/js\/signup-forms\/popup\/unique-methods\/embed\.js");


			array_push($this->external_js_list, "\/\/fareharbor\.com\/embeds\/api\/v\d+\/"); // <script src="https://fareharbor.com/embeds/api/v1/?autolightframe=yes"></script>



			array_push($this->external_js_list, "\/\/www\.?google-analytics\.com\/analytics\.js");  // <script defer src="https://www.google-analytics.com/analytics.js"></script>
			array_push($this->external_js_list, "\/\/www\.?googletagmanager\.com\/gtag\/js");       // <script src="https://www.googletagmanager.com/gtag/js?id=XXXXXX" id="google_gtagjs-js" async></script>



			array_push($this->external_js_list, "\/\/connect\.livechatinc\.com\/api\/[^\/]+\/script\/[^\/]+\/widget\.js"); // https://connect.livechatinc.com/api/v1/script/5f9a2753-6a3e-4594-a867-e61f99b773e6/widget.js?lcv=af1aa836-6a44-4013-8944-05d402c980f1
			array_push($this->external_js_list, "\/\/js\.chargebee\.com\/[^\/]+\/chargebee\.js");
			array_push($this->external_js_list, "\/\/js\.hs-scripts\.com\/\d+\.js"); // HubSpot Embed Code
			array_push($this->external_js_list, "\/\/assets\.pinterest\.com\/js\/pinit\.js");
			array_push($this->external_js_list, "\/\/sdki\.truepush\.com\/sdk\/[^\/]+\/app\.js"); // https://sdki.truepush.com/sdk/v2.0.3/app.js
			array_push($this->external_js_list, "\/\/static\.zdassets\.com\/ekr\/snippet\.js");

			array_push($this->external_js_list, "\/\/storage\.googleapis\.com\/chatheroes\.com\/ecobranded\/ecobranded\.js");




			array_push($this->external_js_list, "\/\/user\.callnowbutton\.com\/[^\/]+\.js"); // <script data-cnb-version="1.4.2" async="async" src="https://user.callnowbutton.com/domain_7bf7e29d_1493_4adc_93c9_e81d62b2f789.js"></script>




			array_push($this->external_js_list, "\/\/verify\.authorize\.net\:443\/anetseal\/seal\.js"); // <script language="javascript" src="//verify.authorize.net:443/anetseal/seal.js"></script>


			array_push($this->external_js_list, "\/\/widget\.trustpilot\.com\/bootstrap\/v\d+\/tp\.widget\.bootstrap\.min\.js"); // <script src="//widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js?ver=1.0&#039; async=&#039;async" id="widget-bootstrap-js"></script>

			array_push($this->external_js_list, "\/wp\-content\/plugins\/chaty\/js\/cht\-front\-script\.min\.js");
			array_push($this->external_js_list, "\/wp\-content\/plugins\/click-to-chat-for-whatsapp\/new\/inc\/assets\/js\/app\.js");
			array_push($this->external_js_list, "\/wp-content\/plugins\/complianz-gdpr-premium\/cookiebanner\/js\/complianz\.min\.js");
			array_push($this->external_js_list, "\/wp\-content\/plugins\/contact\-form\-7\/includes\/swv\/js\/index\.js");

			if(!preg_match("/wpcf7\.init/s", $this->html)){
				array_push($this->external_js_list, "\/wp\-content\/plugins\/contact\-form\-7\/includes\/js\/index\.js");
			}

			array_push($this->external_js_list, "\/wp\-content\/plugins\/complianz-gdpr\/cookiebanner\/js\/complianz\.min\.js");
			array_push($this->external_js_list, "\/wp\-content\/plugins\/cookie-law-info\/legacy\/public\/js\/cookie-law-info-public\.js");
			array_push($this->external_js_list, "\/wp\-content\/plugins\/cookie-law-info\/lite\/frontend\/js\/script\.min\.js");
			array_push($this->external_js_list, "\/wp\-content\/plugins\/cookie\-notice\/js\/front\.min\.js");







			array_push($this->external_js_list, "\/wp\-content\/plugins\/gdpr\-cookie\-compliance\/dist\/scripts\/main\.js");
			array_push($this->external_js_list, "\/wp\-content\/plugins\/table\-of\-contents\-plus\/front\.min\.js");

			array_push($this->external_js_list, "\/wp-content\/plugins\/pixelyoursite\/dist\/scripts\/public.js");
			array_push($this->external_js_list, "\/wp-content\/plugins\/pixelyoursite-pro\/dist\/scripts\/public.js");

			array_push($this->external_js_list, "\/wp\-content\/plugins\/woocommerce\/assets\/js\/frontend\/add\-to\-cart\.min\.js");
			array_push($this->external_js_list, "\/wp\-content\/plugins\/woocommerce\/assets\/js\/frontend\/add\-to\-cart\-variation\.min\.js");


			array_push($this->external_js_list, "\/wp-content\/plugins\/wordpress-gdpr\/public\/js\/wordpress-gdpr-public\.js");

			/*
			google-listings-and-ads eklentisi sorun çıkardı.
			array_push($this->external_js_list, "\/wp-includes\/js\/dist\/hooks\.min\.js");
			*/
			
			// array_push($this->external_js_list, "\/wp-includes\/js\/dist\/i18n\.min\.js");
			// array_push($this->inline_js_list, "id\=[\"\']wp-i18n-js-after[\"\']");
			// array_push($this->inline_js_list, "id\=[\"\']contact-form-7-js-translations[\"\']");

		}

		public function action(){
			$this->tags = array_reverse($this->tags);

			foreach ($this->tags as $key => $value){
				if($value["text"] != $this->delay_it($value["text"], false)){
					$this->timeout = $this->timeout + 100;
				}
			}

			foreach ($this->tags as $key => &$value){

				$delayed_js = $this->delay_it($value["text"]);

				$this->html = substr_replace($this->html, $delayed_js, $value["start"], ($value["end"] - $value["start"] + 1));
			}

			return $this->html;
		}

		public function get_attributes($script){
			$attributes = new stdClass();

			preg_match_all("/\s+([^\s\=\'\"]+)\s*\=[\"\'\s]*([^\"\'\>]+)/i", $script, $parsed);

			if(isset($parsed[1][0])){
				foreach ($parsed[1] as $key => $value) {
					$attributes->$value = $parsed[2][$key];
				}
			}

			return $attributes;
		}

		public function delay_it($js, $timeout_decrease = true){
			$attributes = $this->get_attributes($js);

			if(false && isset($attributes->{'data-wp-strategy'}) && $attributes->{'data-wp-strategy'} == "defer"){

				if(preg_match("/single-product(\.min)?\.js/", $attributes->src)){
					// It causes woocommerce-product-gallery__image to fail loading
					return $js;
				}

				$tmp_timeout = $this->timeout;

				$middle_for_external = "(function(d,s){";
				$middle_for_external = $middle_for_external."var f=d.getElementsByTagName(s)[0];";
				$middle_for_external = $middle_for_external."j=d.createElement(s);";

				foreach ($attributes as $attr_key => $attr_value) {
					if($attr_key == "data-wp-strategy"){
						continue;
					}

					if($attr_key == "defer"){
						continue;
					}

					$middle_for_external = $middle_for_external."j.setAttribute('".$attr_key."', '".$attr_value."');";
				}

				$middle_for_external = $middle_for_external."f.parentNode.insertBefore(j,f);";
				$middle_for_external = $middle_for_external."})(document,'script');";

			}else if(isset($attributes->src) && $this->in_external_js_list($attributes->src)){
				$tmp_timeout = $this->timeout;

				$middle_for_external = "(function(d,s){";
				$middle_for_external = $middle_for_external."var f=d.getElementsByTagName(s)[0];";
				$middle_for_external = $middle_for_external."j=d.createElement(s);";

				foreach ($attributes as $attr_key => $attr_value) {
					$middle_for_external = $middle_for_external."j.setAttribute('".$attr_key."', '".$attr_value."');";
				}

				$middle_for_external = $middle_for_external."f.parentNode.insertBefore(j,f);";
				$middle_for_external = $middle_for_external."})(document,'script');";

			}else if($tmp_inline_js = $this->in_inline_js_list($js)){
				$tmp_timeout = $this->timeout + 300;

				$tmp_inline_js = preg_replace("/^<script[^\>]*>|<\/script>$/i", "", $tmp_inline_js);
								
				$middle_for_external = $tmp_inline_js;
			}else if($tmp_inline_js = $this->in_3th_inline_js_list($js)){
				$tmp_timeout = $this->timeout;

				$tmp_inline_js = preg_replace("/^<script[^\>]*>|<\/script>$/i", "", $tmp_inline_js);
								
				$middle_for_external = $tmp_inline_js;
			}


			if(isset($middle_for_external)){
				$js = str_replace("{{MIDDLE}}", $middle_for_external, $this->get_delay_inline_js_code());
				$js = str_replace("{{TIMEOUT}}", $tmp_timeout, $js);

				if($timeout_decrease){
					$this->timeout = $this->timeout - 100;
				}
			}

			return $js;
		}


		public function in_inline_js_list($js = false){
			if(!$js){
				return false;
			}

			$js = preg_replace("/\/\*\s*\<\s*\!\s*\[\s*CDATA\s*\[\s*\*\//i", "", $js);
			$js = preg_replace("/\/\*\s*\]\s*\]\>\s*\*\//i", "", $js);
			$js = preg_replace("/\/\*[^\/\*\n]+\*\//", "", $js); // remove comment which is /* comment */


			if(!empty($this->inline_js_list)){
				foreach ($this->inline_js_list as $key => $value) {
					if(preg_match("/".$value."/i", $js)){
						return $js;
					}
				}
			}

			return false;
		}

		public function in_3th_inline_js_list($js = false){
			if(!$js){
				return false;
			}

			$js = preg_replace("/\/\*\s*\<\s*\!\s*\[\s*CDATA\s*\[\s*\*\//i", "", $js);
			$js = preg_replace("/\/\*\s*\]\s*\]\>\s*\*\//i", "", $js);
			$js = preg_replace("/\/\*[^\/\*\n]+\*\//", "", $js); // remove comment which is /* comment */



			if(preg_match("/<script[^\>]*>\s*\(\s*function\([wdsli\s\,]+\)\s*\{\s*w\[l\]\s*\=\s*w\[l\]\s*\|\|\s*\[\];\s*w\[l\]\.push\(\s*\{\s*[\"\']\s*gtm\.start\s*[\"\'][^\}]+\s*\}\s*\)[^\}]+\s*\}\s*\)\([^\)]+\)\s*\;\s*<\/script>/i", $js)){
				// (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
				// new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
				// j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
				// 'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
				// })(window,document,'script','dataLayer','GTM-M2QGN6H');

				return $js;
			}

			if(preg_match("/<script[^\>]*>\s*var\s+Tawk_API\s*\=\s*Tawk_API\s*\|\|\s*\{\}[\,\;\s]+(var)*\s*Tawk_LoadStart\s*\=\s*new\s+Date\(\)\s*\;\s*\(function\(\)\{[^\}]+\}\s*\)\(\)\s*\;\s*<\/script>/i", $js)){
				// <script type="text/javascript">
				// var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
				// (function(){
				// var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
				// s1.async=true;
				// s1.src='https://embed.tawk.to/63f4e6a831ebfa0fe7ee85a6/1gpqaa15b';
				// s1.charset='UTF-8';
				// s1.setAttribute('crossorigin','*');
				// s0.parentNode.insertBefore(s1,s0);
				// })();
				// </script>

				// <script id="tawk-script">
				// var Tawk_API=Tawk_API||{};
				// var Tawk_LoadStart=new Date();
				// (function(){
				// var s1=document.createElement('script'),s0=document.getElementsByTagName('script')[0];
				// s1.async=true;
				// s1.src='https://embed.tawk.to/61e7e3ebb84f7301d32bcc96/1fpot4ks0';
				// s1.charset='UTF-8';
				// s1.setAttribute('crossorigin','*');
				// s0.parentNode.insertBefore(s1, s0);
				// })();</script>

				return $js;
			}

			if(preg_match("/<script[^\>]*>\s*\!function\([fbevnts\,\s]+\)\s*\{(((?!\.insertBefore\().)+)\.insertBefore\([^\)]+\)\}\([^\)]+fbevents\.js[^\)]+\);\s*(fbq\([^\)]+\)\s*\;\s*){0,2}\s*<\/script>/is", $js)){
				// <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
				// n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
				// n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
				// t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
				// document,'script','https://connect.facebook.net/en_US/fbevents.js');</script>

				// <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
				// n.callMethod.apply(n,arguments):n.queue.push(arguments)};
				// if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
				// n.queue=[];t=b.createElement(e);t.async=!0;
				// t.src=v;s=b.getElementsByTagName(e)[0];
				// s.parentNode.insertBefore(t,s)}(window, document,'script',
				// 'https://connect.facebook.net/en_US/fbevents.js');
				// fbq('init', '305619320703136');
				// fbq('track', 'PageView');</script>

				return $js;
			}

			if(preg_match("/<script[^\>]*>\s*\(\s*function\s*\([dsi\,\s]+\)\s*\{[^\}]+\}\s*\([^\)]*facebook-jssdk[^\)]+\)\s*\)\s*\;\s*<\/script>/is", $js)){
				// <script>(function(d, s, id){
				// var js, fjs = d.getElementsByTagName(s)[0];
				// if (d.getElementById(id)) return;
				// js = d.createElement(s); js.id = id;
				// js.src = "//connect.facebook.net/fr_FR/sdk.js#xfbml=1&version=v2.8&appId=\"297186066963865\"";
				// fjs.parentNode.insertBefore(js, fjs);
				// }(document, 'script', 'facebook-jssdk' ));
				
				// </script>
				return $js;
			}

			if(preg_match("/<script[^\>]*>(\s*var\s+url\s*\=[^\;]+\;)?\s*fbq\(\s*[\"\'][a-z]+[\"\'][^\)]+\s*\)(\s*\;)?\s*<\/script>/i", $js)){

				// <script>
				// var url = window.location.origin + '?ob=open-bridge';
				// fbq('set', 'openbridge', '4011737065779972', url);
				// </script>
				// <script>fbq('init', '4011737065779972', {}, {"agent": "wordpress-6.7.2-4.1.1"})</script>
				// <script>fbq('track', 'PageView', []);</script>		

				return $js;

			}

			if(preg_match("/<script[^\>]*>\s*(fbq\([^\)]+\)\;\s*){2}document\.addEventListener\(/i", $js)){
				// <script>
				// fbq('init', '649269165501775', {}, {
				// "agent": "woocommerce-7.4.0-3.0.12"
				// });
				// fbq('track', 'PageView', {
				// "source": "woocommerce",
				// "version": "7.4.0",
				// "pluginVersion": "3.0.12"
				// });
				// document.addEventListener('DOMContentLoaded', function(){
				// jQuery&&jQuery(function($){
				// $(document.body).append('<div class=\"wc-facebook-pixel-event-placeholder\"></div>');
				// });
				// }, false);
				// </script>

				$js = preg_replace("/document\.addEventListener\(\s*[\'\"]\s*DOMContentLoaded\s*[\'\"]\s*,\s*function\(\)\{(((?!\}\s*,\s*false\s*\)\s*;).)+)\}\s*,\s*false\s*\)\s*;/is", "$1", $js);


				return $js;
			}

			if(preg_match("/<script[^\>]*>\s*\(\s*function\([^\)]+\)\{\s*[^\{]+function\(\)\{[^\}]+\}[^\}]+\}\s*\)\s*\([^\)]+mc\.yandex\.ru\/metrika\/tag\.js[^\)]+\)\s*\;\s*ym\([^\)]+\)\s*\;\s*<\/script>/", $js)){
				// <script defer >(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
				// m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
				// (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
				// ym(89601392, "init", {
				// clickmap:true,
				// trackLinks:true,
				// accurateTrackBounce:true
				// });</script>

				return $js;
			}

			if(preg_match("/<script[^\>]*>\s*window\.dojoRequire\s*\(((?:(?!\}\s*\)).)+)(\s*\}\s*\)\s*)+\s*<\/script>/is", $js)){
				// <script>window.dojoRequire(["mojo/signup-forms/Loader"], function(L){ L.start({"baseUrl":"mc.us7.list-manage.com","uuid":"e20fdfb45704802e3f0352e1b","lid":"feb90e6353","uniqueMethods":true}) })</script>
				return $js;
			}

			if(preg_match("/<script\s*>\s*\(\s*function\s*\([hotjar,\s]+\)\s*\{\s*((?:(?!\}\s*\)\s*\().)+)\}\s*\)\s*\([^\)]+static\.hotjar\.com[^\)]+\)\s*\;\s*\<\/script>/is", $js)){
				// <script>(function(h, o,t ,j,a,r){
				// h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
				// h._hjSettings={hjid:3867011,hjsv:5};
				// a=o.getElementsByTagName('head')[0];
				// r=o.createElement('script');r.async=1;
				// r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
				// a.appendChild(r);
				// } ) ( window,document,'//static.hotjar.com/c/hotjar-','.js?sv=');</script>

				return $js;
			}

			if(preg_match("/<script[^\>]*>!function\(e\)\{if\(!window\.pintrk\)\{window\.pintrk\s*=\s*function\s*\(\)\s*\{/", $js) && preg_match("/pintrk\('page'\);\s*</script>/", $js)){
				// <script>
				// !function(e){if(!window.pintrk){window.pintrk = function () {
				// window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var
				// n=window.pintrk;n.queue=[],n.version="3.0";var
				// t=document.createElement("script");t.async=!0,t.src=e;var
				// r=document.getElementsByTagName("script")[0];
				// r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");
				// pintrk('load', '2614456761949', {em: '<user_email_address>'});
				// pintrk('page');
				// </script>

				return $js;
			}

			if(preg_match("/<script[^\>]*>\s*\!function\s*\([wdt,\s]+\)\s*\{.*\}\s*\(window\s*,\s*document\s*,\s*[\'\"]\s*ttq\s*[\'\"]\s*\)\s*\;\s*<\/script>/is", $js)){
				// <script>!function (w, d, t){
				// w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++
				// )ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};n=document.createElement("script");n.type="text/javascript",n.async=!0,n.src=i+"?sdkid="+e+"&lib="+t;e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};
				// ttq.load('CF0L0RJC77UCCRP8BQBG');
				// ttq.page();
				// }(window, document, 'ttq');
				// </script>
				
				return $js;
			}

			return false;
		}

		public function get_delay_inline_js_code(){
			$script = "<script data-wpfc-render=\"false\">";
			$script = $script."(function(){";
			$script = $script.'let events=["mousemove", "wheel", "scroll", "touchstart", "touchmove"];';
			$script = $script."let fired = false;";

			$script = $script."events.forEach(function(event){";
			$script = $script."window.addEventListener(event, function(){";
			$script = $script."if(fired === false){";
			$script = $script."fired = true;";

			$script = $script."setTimeout(function(){ {{MIDDLE}} }, {{TIMEOUT}});";

			$script = $script."}";
			$script = $script."},{";
			$script = $script."once: true";
			$script = $script."});";
			$script = $script."});";
			$script = $script."})();";
			$script = $script."</script>";

			return $script;
		}

		public function in_external_js_list($src = false){
			if(!$src){
				return false;
			}

			foreach ($this->external_js_list as $key => $value) {
				if(preg_match("/".$value."/i", $src)){
					return true;
				}
			}

			return false;
		}

		public function find_tags($start_string, $end_string){
			if(!$this->html){
				return array();
			}else{
				$data = $this->html;
			}

			$list = array();
			$start_index = false;
			$end_index = false;

			for($i = 0; $i < strlen( $data ); $i++) {
			    if(substr($data, $i, strlen($start_string)) == $start_string){
			    	if(!$start_index && !$end_index){
			    		$start_index = $i;
			    	}
				}

				if($start_index && $i > $start_index){
					if(substr($data, $i, strlen($end_string)) == $end_string){
						$end_index = $i + strlen($end_string)-1;
						$text = substr($data, $start_index, ($end_index-$start_index + 1));
						

						array_push($list, array("start" => $start_index, "end" => $end_index, "text" => $text));


						$start_index = false;
						$end_index = false;
					}
				}
			}

			return $list;
		}

	}
?>