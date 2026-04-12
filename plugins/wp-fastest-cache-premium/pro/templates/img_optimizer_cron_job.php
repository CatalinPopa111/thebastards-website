<style type="text/css">
	.wpfc-csp-item:hover{
		background-color: #E5E5E5;
	}
	.wpfc-csp-item{
		float: left;
		width: 330.5px;
		margin-right: 7px;
		margin-left: 20px;
	    -moz-border-radius:5px 5px 5px 5px;
	    -webkit-border-radius:5px 5px 5px 5px;
	    border-radius:5px 5px 5px 5px;
	    border:1px solid transparent;
	    cursor:pointer;
	    padding:9px;
		outline:none !important;
		list-style: outside none none;
	}

	.wpfc-csp-item-form-title{
	    max-width:380px;
		font-weight:bold;
	    white-space:nowrap;
	    max-width:615px;
	    margin-bottom:3px;
	    text-overflow:ellipsis;
	    -o-text-overflow:ellipsis;
	    -moz-text-overflow:ellipsis;
	    -webkit-text-overflow:ellipsis;
	    line-height:1em;
	    font-family: Verdana,Geneva,Arial,Helvetica,sans-serif;
	}
	.wpfc-csp-item-details{
	    font-size:11px;
	    color:#888;
		display:block;
	    white-space:nowrap;
	    font-family: Verdana,Geneva,Arial,Helvetica,sans-serif;
	    line-height:1.5em;
	}
	.wpfc-csp-item-details b {
		display:inline;
		margin-left: 1px;

	}
	.wpfc-csp-item-right{
		margin-right: 0;
		margin-left: 0;
	}
</style>


<div template-id="wpfc-modal-auto-optimize" style="display:none; top: 10.5px; left: 226px; position: absolute; padding: 6px; height: auto; width: 560px; z-index: 10001;">
	<div style="height: 100%; width: 100%; background: none repeat scroll 0% 0% rgb(0, 0, 0); position: absolute; top: 0px; left: 0px; z-index: -1; opacity: 0.5; border-radius: 8px;">
	</div>
	<div style="z-index: 600; border-radius: 3px;">
		<div style="font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;font-size:12px;background: none repeat scroll 0px 0px rgb(255, 161, 0); z-index: 1000; position: relative; padding: 2px; border-bottom: 1px solid rgb(194, 122, 0); height: 35px; border-radius: 3px 3px 0px 0px;">
			<table width="100%" height="100%">
				<tbody>
					<tr>
						<td valign="middle" style="vertical-align: middle; font-weight: bold; color: rgb(255, 255, 255); text-shadow: 0px 1px 1px rgba(0, 0, 0, 0.5); padding-left: 10px; font-size: 13px; cursor: move;">Auto Optimize Settings</td>
						<td width="20" align="center" style="vertical-align: middle;"></td>
						<td width="20" align="center" style="vertical-align: middle; font-family: Arial,Helvetica,sans-serif; color: rgb(170, 170, 170); cursor: default;">
							<div title="Close Window" class="close-wiz"></div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="window-content-wrapper" style="padding: 8px;">
			<div style="z-index: 1000; height: auto; position: relative; display: inline-block; width: 100%;" class="window-content">


				<div id="wpfc-wizard-csp" class="wpfc-cdn-pages-container">
					<div wpfc-cdn-page="1" class="wiz-cont">

						<h1>The Cron Job Command</h1>		
						<p>The cronjob command you should use to automatically optimize images.</p>
						<div class="wiz-input-cont">
							<?php
								$wpfc_img_opt_nonce = get_option("WpFcImgOptNonce");

								if(!$wpfc_img_opt_nonce){
									$wpfc_img_opt_nonce = wp_create_nonce("WpFcImgOptNonce");

									add_option("WpFcImgOptNonce", $wpfc_img_opt_nonce, null, "yes");
								}
							?>
							<input onClick="this.select();" value="wget --no-check-certificate -O /dev/null &quot;<?php echo get_site_url('', '?action=wpfastestcache&type=optimizeimage&security='.$wpfc_img_opt_nonce); ?>&quot;" readonly type="text" name="url" class="api-key" style="width: 100%;">
					    </div>

					    <p class="wpfc-bottom-note" style="margin-bottom:-10px;"><a target="_blank" href="https://www.wpfastestcache.com/premium/automate-image-optimization/">Note: Please read this article to learn about this feature.</a></p>

					</div>


				</div>
			</div>
		</div>
		<?php include WPFC_MAIN_PATH."templates/buttons.html"; ?>
	</div>
</div>


