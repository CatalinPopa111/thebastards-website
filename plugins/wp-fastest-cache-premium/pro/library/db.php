<?php
    class WpFastestCacheDatabaseCleanup{

        public static function set_schedule_event(){
            if(!wp_verify_nonce($_POST["nonce"], 'auto-cleanaup')){
                wp_send_json(array("success" => false));
            }

            if(isset($_POST["status"])){
                if($_POST["status"] == "daily" || $_POST["status"] == "weekly"){

                    if(wp_get_schedule("wpfc_db_auto_cleanup") != $_POST["status"]){
                        wp_clear_scheduled_hook("wpfc_db_auto_cleanup");

                        if( time() > strtotime( 'today 5:00' ) ){
                            wp_schedule_event( strtotime('tomorrow 5:00'), $_POST["status"], "wpfc_db_auto_cleanup");
                        }else{
                            wp_schedule_event( strtotime('today 5:00'), $_POST["status"], "wpfc_db_auto_cleanup");
                        }
                    }

                    wp_send_json(array("success" => true, "status" => wp_get_schedule("wpfc_db_auto_cleanup")));
                }else if($_POST["status"] == "off"){
                    wp_clear_scheduled_hook("wpfc_db_auto_cleanup");

                    wp_send_json(array("success" => true));
                }
            }

            wp_send_json(array("success" => false));
        }


        public static function clean($type){
            global $wpdb;

            $statics = array();

            switch ($type){
                case "all_warnings":
                    $wpdb->query("DELETE FROM `$wpdb->posts` WHERE post_type = 'revision';");
                    $wpdb->query("DELETE FROM `$wpdb->posts` WHERE post_status = 'trash';");
                    $wpdb->query("DELETE FROM `$wpdb->comments` WHERE comment_approved = 'spam' OR comment_approved = 'trash';");
                    $wpdb->query("DELETE FROM `$wpdb->comments` WHERE comment_type = 'trackback' OR comment_type = 'pingback';");
                    $wpdb->query("DELETE FROM `$wpdb->options` WHERE option_name LIKE '%\_transient\_%';");
                    $wpdb->query("DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL;");
                    $wpdb->query("DELETE cm FROM {$wpdb->commentmeta} cm LEFT JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id WHERE c.comment_ID IS NULL;");
                    $wpdb->query("DELETE um FROM {$wpdb->usermeta} um LEFT JOIN {$wpdb->users} u ON u.ID = um.user_id WHERE u.ID IS NULL;");
                    $wpdb->query("DELETE tm FROM {$wpdb->termmeta} tm LEFT JOIN {$wpdb->terms} t ON t.term_id = tm.term_id WHERE t.term_id IS NULL;");
                    $wpdb->query("DELETE tr FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->posts} p ON p.ID = tr.object_id WHERE p.ID IS NULL;");
                    break;

                case "post_revisions":
                    $wpdb->query("DELETE FROM `$wpdb->posts` WHERE post_type = 'revision';");
                    break;

                case "trashed_contents":
                    $wpdb->query("DELETE FROM `$wpdb->posts` WHERE post_status = 'trash';");
                    break;

                case "trashed_spam_comments":
                    $wpdb->query("DELETE FROM `$wpdb->comments` WHERE comment_approved = 'spam' OR comment_approved = 'trash';");
                    break;

                case "trackback_pingback":
                    $wpdb->query("DELETE FROM `$wpdb->comments` WHERE comment_type = 'trackback' OR comment_type = 'pingback';");
                    break;

                case "transient_options":
                    $wpdb->query("DELETE FROM `$wpdb->options` WHERE option_name LIKE '%\_transient\_%';");
                    break;

                case "orphaned_post_meta":
                    $wpdb->query("DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL;");
                    break;

                case "orphaned_comment_meta":
                    $wpdb->query("DELETE cm FROM {$wpdb->commentmeta} cm LEFT JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id WHERE c.comment_ID IS NULL;");
                    break;

                case "orphaned_user_meta":
                    $wpdb->query("DELETE um FROM {$wpdb->usermeta} um LEFT JOIN {$wpdb->users} u ON u.ID = um.user_id WHERE u.ID IS NULL;");
                    break;

                case "orphaned_term_meta":
                    $wpdb->query("DELETE tm FROM {$wpdb->termmeta} tm LEFT JOIN {$wpdb->terms} t ON t.term_id = tm.term_id WHERE t.term_id IS NULL;");
                    break;

                case "orphaned_term_relationships":
                    $wpdb->query("DELETE tr FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->posts} p ON p.ID = tr.object_id WHERE p.ID IS NULL;");
                    break;
            }

            die(json_encode(array("success" => true)));
        }



    }
?>