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


<div template-id="wpfc-modal-buy-crdit" style="display:none; top: 10.5px; left: 226px; position: absolute; padding: 6px; height: auto; width: 560px; z-index: 10001;">
    <div style="height: 100%; width: 100%; background: none repeat scroll 0% 0% rgb(0, 0, 0); position: absolute; top: 0px; left: 0px; z-index: -1; opacity: 0.5; border-radius: 8px;">
    </div>
    <div style="z-index: 600; border-radius: 3px;">
        <div style="font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;font-size:12px;background: none repeat scroll 0px 0px rgb(255, 161, 0); z-index: 1000; position: relative; padding: 2px; border-bottom: 1px solid rgb(194, 122, 0); height: 35px; border-radius: 3px 3px 0px 0px;">
            <table width="100%" height="100%">
                <tbody>
                    <tr>
                        <td valign="middle" style="vertical-align: middle; font-weight: bold; color: rgb(255, 255, 255); text-shadow: 0px 1px 1px rgba(0, 0, 0, 0.5); padding-left: 10px; font-size: 13px; cursor: move;">Credits Packages</td>
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

                        <h1>Purchase Credits</h1>       
                        <p>Choose the amount of credits you'd like to purchase to optimize more images.</p>




                        <form action="https://api.wpfastestcache.net/paypal/buyimagecredit/" method="post">
                            <input type="hidden" name="hostname" value="<?php echo str_replace(array("http://", "www."), "", $_SERVER["HTTP_HOST"]); ?>">
                            
                            <div class="wiz-input-cont" style="float: left; width: 44%; margin-right: 10px;" for="quantity-2000">
                                <label>
                                    <input type="radio" name="quantity" id="quantity-2000" value="2000"><span class="">2000 Credits ($1.4)</span>
                                </label>
                            </div>

                            <div class="wiz-input-cont" style="float: left; width: 44%;" for="quantity-5000">
                                <label>
                                    <input type="radio" name="quantity" id="quantity-5000" value="5000"><span class="">5000 Credits ($3.5)</span>
                                </label>
                            </div>

                            <div class="wiz-input-cont" style="float: left; width: 44%; margin-top: 10px; margin-right: 10px; margin-bottom: 10px;" for="quantity-10000">
                                <label>
                                    <input type="radio" name="quantity" id="quantity-10000" value="10000"><span class="">10,000 Credits ($7)</span>
                                </label>
                            </div>

                            <div class="wiz-input-cont" style="float: left; width: 44%; margin-top: 10px; margin-bottom: 10px;" for="quantity-20000">
                                <label>
                                    <input type="radio" name="quantity" id="quantity-20000" value="20000"><span class="">20,000 Credits ($14)</span>
                                </label>
                            </div>
                        </form>




                    </div>

                </div>
            </div>
        </div>
        <?php include WPFC_MAIN_PATH."templates/buttons.html"; ?>
    </div>
</div>


