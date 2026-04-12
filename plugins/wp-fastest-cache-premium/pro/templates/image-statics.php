<?php
	include_once(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/templates/img_optimizer_cron_job.php");
	include_once(WPFC_WP_PLUGIN_DIR."/wp-fastest-cache-premium/pro/templates/buy_credit.php");
?>

<div style="float: right; margin-top:-30px;">
	
<!-- 	<div style="border-right-width: 1px; border-right-style: solid; padding-right: 5px;width: auto; cursor: pointer; display: inline-block;">
		<span id="buy-image-credit-link">Buy Image Credit</span>
	</div> -->

	<div style="border-right-width: 1px; border-right-style: solid; padding-right: 5px;width: auto; cursor: pointer; display: inline-block;">
		<span id="auto-optimize-link">Auto Optimize</span>
	</div>

	<div style="padding-right: 10px;width: auto; cursor: pointer; display: inline-block;" id="container-show-hide-image-list">
		<span id="show-image-list">Show Images</span>
		<span style="display:none;" id="hide-image-list">Hide Images</span>
	</div>

</div>



<div id="wpfc-image-static-panel" style="width:100%;float:left;" data-nonce="<?php echo wp_create_nonce('wpfc-statics-nonce'); ?>">
	<div style="float: left; width: 100%;">
		<div style="float:left;padding-left: 22px;padding-right:15px;">
			<div style="display: inline-block;">
				<div style="width: 150px; height: 150px; position: relative; border-top-left-radius: 150px; border-top-right-radius: 150px; border-bottom-right-radius: 150px; border-bottom-left-radius: 150px; background-color: #ffcc00;">
					

					<div style="position: absolute; top: 0px; left: 0px; width: 150px; height: 150px; border-top-left-radius: 150px; border-top-right-radius: 150px; border-bottom-right-radius: 150px; border-bottom-left-radius: 150px; clip: rect(0px 150px 150px 75px);">
						<div id="wpfc-pie-chart-little" style="position: absolute; top: 0px; left: 0px; width: 150px; height: 150px; border-top-left-radius: 150px; border-top-right-radius: 150px; border-bottom-right-radius: 150px; border-bottom-left-radius: 150px; clip: rect(0px 75px 150px 0px); -webkit-transform: rotate(36deg); transform: rotate(0deg); background-color: #FFA500;"></div>
					</div>


					<div id="wpfc-pie-chart-big-container-first" style="display:none;position: absolute; top: 0px; left: 0px; width: 150px; height: 150px; border-top-left-radius: 150px; border-top-right-radius: 150px; border-bottom-right-radius: 150px; border-bottom-left-radius: 150px; clip: rect(0px 150px 150px 25px); -webkit-transform: rotate(0deg); transform: rotate(0deg);">
						<div style="position: absolute; top: 0px; left: 0px; width: 150px; height: 150px; border-top-left-radius: 150px; border-top-right-radius: 150px; border-bottom-right-radius: 150px; border-bottom-left-radius: 150px; clip: rect(0px 75px 150px 0px); -webkit-transform: rotate(180deg); transform: rotate(180deg); background-color: #FFA500;"></div>
					</div>
					<div id="wpfc-pie-chart-big-container-second-right" style="display:none;position: absolute; top: 0px; left: 0px; width: 150px; height: 150px; border-top-left-radius: 150px; border-top-right-radius: 150px; border-bottom-right-radius: 150px; border-bottom-left-radius: 150px; clip: rect(0px 150px 150px 75px); -webkit-transform: rotate(180deg); transform: rotate(180deg);">
						<div id="wpfc-pie-chart-big-container-second-left" style="position: absolute; top: 0px; left: 0px; width: 150px; height: 150px; border-top-left-radius: 150px; border-top-right-radius: 150px; border-bottom-right-radius: 150px; border-bottom-left-radius: 150px; clip: rect(0px 75px 150px 0px); -webkit-transform: rotate(90deg); transform: rotate(90deg); background-color: #FFA500;"></div>
					</div>

				</div>
				<div style="width: 114px;height: 114px;margin-top: -133px;background-color: white;margin-left: 18px;position: absolute;border-radius: 150px;">
					<p style="text-align:center;margin:27px 0 0 0;color: black;">Succeed</p>
					<p class="wpfc-loading-statics" id="wpfc-optimized-statics-percent" style="text-align: center; font-size: 18px; font-weight: bold; font-family: verdana; margin: -2px 0px 0px; color: black;"></p>
					<p style="text-align:center;margin:0;color: black;">%</p>
				</div>
			</div>
		</div>
		<div id="wpfc-statics-right" style="float: left;padding-left:12px;">
			<ul style="list-style: none outside none;float: left;">
				<li>
					<div style="background-color: #1E88E5;width:15px;height:15px;float:left;margin-top:4px;border-radius:5px;"></div>
					<div style="float:left;padding-left:6px;">All JPEG/PNG</div>
					<div class="wpfc-loading-statics" id="wpfc-optimized-statics-total_image_number" style="font-size: 14px; font-weight: bold; color: black; float: left; width: 65%; margin-left: 5px;"></div>
				</li>
				<li>
					<div style="background-color: #FFA726;width:15px;height:15px;float:left;margin-top:4px;border-radius:5px;"></div>
					<div style="float:left;padding-left:6px;">Pending</div>
					<div class="wpfc-loading-statics" id="wpfc-optimized-statics-pending" style="font-size: 14px; font-weight: bold; color: black; float: left; width: 65%; margin-left: 5px;"></div>
				</li>
				<li>
					<div style="background-color: #E53935;width:15px;height:15px;float:left;margin-top:4px;border-radius:5px;"></div>
					<div data-click-action="errors" style="float:left;padding-left:6px;width: 45%;cursor: pointer;">Errors</div>
					<div data-click-action="errors" class="wpfc-loading-statics" id="wpfc-optimized-statics-error" style="cursor: pointer;font-size: 14px; font-weight: bold; color: black; float: left; width: 45%; margin-left: 5px;"></div>
				</li>





				<li style="display:none;">
					<div style="float:left;padding-left:6px;">Server Location</div>
				</li>





			</ul>
			<ul style="list-style: none outside none;float: left;">
				<li>
					<div style="background-color: rgb(61, 207, 60);width:15px;height:15px;float:left;margin-top:4px;border-radius:5px;"></div>
					<div style="float:left;padding-left:6px;"><span>Optimized Images</span></div>
					<div class="wpfc-loading-statics" id="wpfc-optimized-statics-optimized" style="font-size: 14px; font-weight: bold; color: black; float: left; width: 65%; margin-left: 5px;"></div>
				</li>

				<li>
					<div style="background-color: #9E9E9E;width:15px;height:15px;float:left;margin-top:4px;border-radius:5px;"></div>
					<div data-click-action="excluded" style="cursor: pointer;float:left;padding-left:6px;"><span>Excluded</span></div>
					<div data-click-action="excluded" class="wpfc-loading-statics" id="wpfc-optimized-statics-excluded" style="cursor: pointer;font-size: 14px; font-weight: bold; color: black; float: left; width: 80%; margin-left: 5px;"></div>
				</li>

				<li>
					<div style="background-color: #2E7D32;width:15px;height:15px;float:left;margin-top:4px;border-radius:5px;"></div>
					<div style="float:left;padding-left:6px;"><span>Total Reduction</span></div>
					<style type="text/css">
						#wpfc-optimized-statics-reduction:not(.wpfc-loading-statics):after{ content: "" !important; }
					</style>
					<div class="wpfc-loading-statics" id="wpfc-optimized-statics-reduction" style="font-size: 14px; font-weight: bold; color: black; float: left; width: 80%; margin-left: 5px;"></div>
				</li>


				<li style="display: none;" id="wpfc-server-list"></li>



				
			</ul>

			<ul style="list-style: none outside none;float: left;">
				<li>
					<h1 style="cursor:pointer;margin-top:0;float:left;padding-top: 0;">Credit: <span id="wpfc-optimized-statics-credit" class="wpfc-loading-statics" style="display: inline-block; height: 16px; width: auto;min-width:25px;"></span> <img src=" data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAApgAAAKYB3X3/OAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAM1SURBVEiJrdXLa11lFAXw3zn3lZsmTVK0adOHbbFiVBBt46MVWyeKFgw6cKQTEaoD/wMpCDoWQTBFnDnSUYtFcaCIKNpC0Wqa2qZobZuQ9/ve3HMfn4MTrC3hNq3u4TmbtfZe7LW+KIRgTXX20CbyzxOeJdpO6MadKGEM40SnRI1jegvf82kdopsS/PxCp1x4X+QVRGubxlVROKz32InmBMPPFST5IexcI/CN9Xrc9Hep8sZ/ACfUjzQnKF/YfdvgkEz0NCeoTZ22NERo3Dp4bZbyxZnmBO17uyTjzP7A8mUalZugBqrTxG2EmHxPnG3aX/5jp859LJ5jcRCDZNuJi8R5ojzqNBK6nqF0jvmTbH2U+jyNUkdzgtrUaaVh2u5n3T0k4ySTNMrU5lPgKLNCFhPn6HiE5QspQfniTHOC9r1dFs6QTFG8i/xGWrat3lsdIdtKtbIi0fwaJGrfd0D3qyydpTREZZTkcirN/yJR19ORzqdukG2a6S+ZPMbkCaKoqUT/cvJLGUOV/ULcT+jDRlF+F42cTAfZTnJ30LaH9j6yXSyd4ep71GZWMALVGQrbqYxQnZpLCYb6DwnRUWxputE/FdFxkE2vESoMvkjx3hWJfmTrm5R/JxkXhcH+wxhYG/ANlevm7g8Y+TAFL19ICXMbUqMtDs7EQv3IbYFDdYyp43S/fO2KuM5osWSi57YJYOE02Q1kN5HMMXeS/GaybWSKHbHyxRm12VsHDg2Whiiu5OGqV3T+SizfE5v9KY2C6jRu8gA1KtRKFHalzs60pN9Xk6hWeieWKXZY/zDJRLre1NfMnWLhl3TCpfMs/kZ1jvwOpr9Jp6/PS3Pq1xT0eonqMq1veXL5aKx8/oo4x4YD6XotW9BIs2Z5hPKfaf6IqC+kuZTJU5ukNExrL41KEGfmcJzogLFL7R74/F2IwreFw0QDWrZS2EyuS9Ont1GhUaf1PkY/+suerx7Xun8SyWrtqdG+azkkSI0W5ciuT+0f55EhJBS2se5BRgbofCIo7Lhk9OOHHAxNL+RaVHwWZXS37BeFfkGfYKNIN1oxYf1jdcXdWWOfvK245Qt9w5ebX0NafwP+zWSYgN1VyAAAAABJRU5ErkJggg==" /> </h1>
				</li>
				<li>
					<input style="width:100%;height:65px;" id="wpfc-optimize-images-button" type="submit" value="Optimize All" class="button-primary" />
				</li>

				<li>
					<div id="wpfc-opt-image-loading" style="height: 10px; border: 1px solid rgb(61, 207, 60); width: 215px;padding: 2px;"><div style="height:100%;background-color: rgb(61, 207, 60);width:0;"></div></div>
				</li>

			</ul>
		</div>
	</div>
</div>