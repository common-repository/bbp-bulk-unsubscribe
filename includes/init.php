<?php
/**
 * Front end functions for Unsubscribe
 *
 * @author      VibeThemes
 * @category    Admin
 * @package     BBPBU
 * @version     1.0
 */

 if ( ! defined( 'ABSPATH' ) ) exit;

class BBP_Bulk_Unsubscribe{

    public static $instance;
    
    var $schedule;

    public static function init(){

        if ( is_null( self::$instance ) )
            self::$instance = new BBP_Bulk_Unsubscribe();

        return self::$instance;
    }

    private function __construct(){

    	add_action('bbp_template_after_user_subscriptions',array($this,'unsubscribe_option'));

    	add_action('wp_ajax_unsubscribe_user_from_forums',array($this,'unsubscribe_user_from_forums'));
        add_action('wp_ajax_unsubscribe_user_from_topics',array($this,'unsubscribe_user_from_topics'));
    }

    function unsubscribe_option(){

    	if(!bbp_admin_setting_callback_unsubscribe_from_all())
    		return;

        echo '<a id="unsubscribe_from_all_forums" class="button">'._x('Unsubscribe from all forums','unsunscribe button label','bbpbu').'</a>';

        echo '<a id="unsubscribe_from_all_topics" class="button">'._x('Unsubscribe from all topics','unsunscribe button label','bbpbu').'</a>';

        $this->unsubscribe_users_from_all_forums_topics();
    	
    }

    function unsubscribe_users_from_all_forums_topics(){
        ?>
        <script>
            jQuery(document).ready(function($){

                $('#unsubscribe_from_all_forums').on('click',function(){
                    
                    $.ajax({
                        type: "POST",
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            action:'unsubscribe_user_from_forums',
                        },
                        cache: false,
                        success: function (html) {
                            window.location.reload();
                        }
                    });

                });

                $('#unsubscribe_from_all_topics').on('click',function(){

                    $.ajax({
                        type: "POST",
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            action:'unsubscribe_user_from_topics',
                        },
                        cache: false,
                        success: function (html) {
                            window.location.reload();
                        }
                    });

                });

            });
        </script>
        <?php
    }

    function unsubscribe_user_from_forums(){

        if ( !is_user_logged_in() ){
            _e('Security check Failed. Contact Administrator.','bbpbu');
            die();
        }

        $user_id = get_current_user_id();
        delete_user_option( $user_id, '_bbp_forum_subscriptions' );

        die();
    }

    function unsubscribe_user_from_topics(){

        if ( !is_user_logged_in() ){
            _e('Security check Failed. Contact Administrator.','bbpbu');
            die();
        }

        $user_id = get_current_user_id();
        delete_user_option( $user_id, '_bbp_subscriptions' );

        die();
    }

}

BBP_Bulk_Unsubscribe::init();

function bbp_admin_setting_callback_unsubscribe_from_all( $default = 0 ) {
	return apply_filters( 'bbp_admin_setting_callback_unsubscribe_from_all', (bool) get_option( '_bbp_admin_setting_callback_unsubscribe_from_all', $default ) );
}

