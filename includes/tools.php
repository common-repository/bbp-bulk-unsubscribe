<?php
/**
 * Back end functions for Unsubscribe
 *
 * @author      VibeThemes
 * @category    Admin
 * @package     BBPBU
 * @version     1.0
 */

 if ( ! defined( 'ABSPATH' ) ) exit;

class BBP_Bulk_Unsubscribe_Tools{

    public static $instance;
    
    var $schedule;

    public static function init(){

        if ( is_null( self::$instance ) )
            self::$instance = new BBP_Bulk_Unsubscribe_Tools();

        return self::$instance;
    }

    private function __construct(){

		add_action( 'bbp_admin_menu',array( $this, 'add_tools_page')); 

		add_filter('bbp_admin_get_settings_fields',array($this,'enable_user_to_unsubscribe_from_all'));
		add_filter('bbp_get_default_options',array($this,'add_unsubscribe_option'));

        //Enque Select2
        add_action( 'admin_enqueue_scripts', array( $this, 'persistent_admin_scripts' ),10,1);

        /* Ajax calls */

        //Get all users
        add_action('wp_ajax_get_all_users',array($this,'get_all_users'));
        //Get/select Users/Forums/Topics
        add_action('wp_ajax_get_users_forums_topics',array($this,'get_users_forums_topics'));
        //Unsubscribe all users from all forums
        add_action('wp_ajax_unsubscribe_all_users',array($this,'unsubscribe_all_users'));
        //Get all users for selected forums/topics
        add_action('wp_ajax_get_all_users_for_selected_forum_topic',array($this,'get_all_users_for_selected_forum_topic'));
        //Unsubscribe all users from selected forums and topics
        add_action('wp_ajax_unsubscribe_forums_topics',array($this,'unsubscribe_forums_topics'));
        //Unsubscribe selected users from all forums
        add_action('wp_ajax_unsubscribe_user',array($this,'unsubscribe_user'));
    }

    function add_unsubscribe_option($ops){
    	$ops['_bbp_admin_setting_callback_unsubscribe_from_all'] = true;
    	return $ops;
    }
    function enable_user_to_unsubscribe_from_all($settings){
    	
    	$settings['bbp_settings_users']['_bbp_admin_setting_callback_unsubscribe_from_all'] = array(
			'title'    => __( 'Enable Forum Users to unsubscribe from all Forums & topics', 'bbpress' ),
			'callback' => array($this,'bbp_admin_setting_callback_unsubscribe_from_all'),
			'page'     => 'discussion'
		);

    	return $settings;
    }
	
	function bbp_admin_setting_callback_unsubscribe_from_all(){
		?>
		<input name="_bbp_admin_setting_callback_unsubscribe_from_all" id="_bbp_admin_setting_callback_unsubscribe_from_all" type="checkbox" value="1" <?php checked( bbp_admin_setting_callback_unsubscribe_from_all( false ) ); ?> />
		<label for="_bbp_admin_setting_callback_unsubscribe_from_all"><?php esc_html_e( 'Allow Users to unsubscribe from all forums and topics', 'bbpbu' ); ?></label>
	<?php

	}	

    function add_tools_page(){

    	if(!function_exists('bbpress'))
    		return;

    	// These are later removed in admin_head
		if ( current_user_can( 'bbp_tools_page' ) ) {
			add_management_page(
				__( 'Bulk Unsubscribe', 'bbpress' ),
				__( 'BBPress Unsubscribe',  'bbpress' ),
				bbpress()->admin->minimum_capability,
				'bbp-bulk-unsubscribe',
				array($this,'bbp_bulk_unsubscribe')
			);
		}

    }

    function persistent_admin_scripts($hook){

        if(in_array($hook,array('tools_page_bbp-bulk-unsubscribe'))){
            wp_enqueue_style( 'select2',plugins_url('../assets/select2.min.css',__FILE__));
            wp_enqueue_script( 'select2', plugins_url('../assets//select2.min.js',__FILE__));
        }

    }

    function bbp_bulk_unsubscribe(){
        global $post;
        $meta = get_post_meta( $post->ID, $id, true);
    	?>
    	<div class="wrap">
    		<h1><?php _ex('Unsubscribe users from Forums and Topics','','bbpbu'); ?></h1>

    		<div class="card">
    			<h2><?php _ex('Unsubscribe all users from all Forums and Topics','','bbpbu'); ?></h2>
    			<p><?php _ex('If you\'re starting afresh or you want to stop sending emails to all your users. Use this option.','','bbpbu'); ?></p>
    			<a id="unsubscribe_all_users" class="button-primary"><?php _ex('Unsubscribe all users from all Forums and Topics','','bbpbu'); ?></a>
    		</div>

    		<div class="card">
    			<h2><?php _ex('Unsubscribe all Users from Selected Forum OR Topic','','bbpbu'); ?></h2>
    			<p><?php _ex('Unsubscribe all users from selected forum or topic','','bbpbu'); ?></p>
                <select name="search_forums_topics[]" id="search_forums_topics" class="select" data-placeholder="<?php echo __('Select single forum or topic','bbpbu'); ?>" style="width:100%;margin-bottom:20px;" single>
                </select><br />
    			<a id="unsubscribe_forums_topics" class="button-primary"><?php _ex('Unsubscribe all users from selected Forum or Topic','','bbpbu'); ?></a>
    		</div>

    		<div class="card">
    			<h2><?php _ex('Unsubscribe Selected Users from all Forums and Topics','','bbpbu'); ?></h2>
    			<select name="search_users[]" id="search_users" class="select" data-placeholder="<?php echo __('Select multiple Users','bbpbu'); ?>" style="width:100%;margin-bottom:20px;" multiple>
                </select><br />
    			<a id="unsubscribe_user" class="button-primary"><?php _ex('Unsubscribe selected users from all Forums & Topics','','bbpbu'); ?></a>
    		</div>

            <?php wp_nonce_field('hkl_security','security'); ?>

    	</div>
    	<?php
        echo '<style>.disabled {opacity:0.5;}</style>';
        $this->unsubscribe_users_from_forums_topics();
    }

    function unsubscribe_users_from_forums_topics(){
        ?>
        <script>
            jQuery(document).ready(function($){

                $('#unsubscribe_all_users').on('click',function(){

                    var $this = $(this);
                    if( $this.hasClass('disabled')){
                        return;
                    }

                    $this.addClass('disabled');

                    $.ajax({
                        type: "POST",
                        dataType: 'json',
                        url: ajaxurl,
                        data: { action: 'get_all_users', 
                                security: $('#security').val(),
                                },
                        cache: false,
                        success: function (json){

                            $('#unsubscribe_all_users').append('<div class="bbpbu_unsubscribe_all_users_progress" style="width:100%;margin-top:20px;margin-bottom:20px;height:10px;background:#fafafa;border-radius:10px;overflow:hidden;"><div class="bar" style="padding:0 1px;background:#37cc0f;height:100%;width:0;"></div></div>');

                            var x = 0;
                            var width = 100*1/json.length;
                              var number = width;
                              var loopArray = function(arr) {
                                  bbpbu_all_users_ajaxcall(arr[x],function(){
                                      x++;
                                      if(x < arr.length) {
                                         loopArray(arr);   
                                    }
                                }); 
                            }

                            // start 'loop'
                            loopArray(json);

                            function bbpbu_all_users_ajaxcall(obj,callback){

                                $.ajax({
                                 type: "POST",
                                  dataType: 'json',
                                 url: ajaxurl,
                                  data: {
                                      action:'unsubscribe_all_users',
                                      security: $('#security').val(),
                                      user:obj.id,
                                      forum:obj.forum_option,
                                      topic:obj.topic_option,
                                  },
                                  cache: false,
                                  success: function (html){

                                    number = number + width;
                                    $('.bbpbu_unsubscribe_all_users_progress .bar').css('width',number+'%');

                                    if(number >= 100){
                                        $this.removeClass('disabled');
                                        $this.html('<strong>'+x+' '+'<?php _e('Users successfuly unsubscribed from all forums and topics','bbpbu'); ?>'+'</strong>');
                                    }

                                  }

                                });

                                // do callback when ready
                                callback();

                            }

                        }
                    });
                });

                $('.select').each(function(){

                    var $this = $(this);
                    var placeholder = $(this).attr('data-placeholder');
                    $(this).select2({
                        minimumInputLength: 4,
                        placeholder: placeholder,
                        closeOnSelect: true,
                        allowClear: true,
                        ajax: {
                            url: ajaxurl,
                            type: "POST",
                            dataType: 'json',
                            delay: 250,
                            data: function(term){ 
                                    return  {   
                                        action: 'get_users_forums_topics',
                                        security: $('#security').val(),
                                        id:$this.attr('id'),
                                        q: term,
                                    }
                            },
                            processResults: function (data) {
                                return {
                                    results: data
                                };
                            },       
                            cache:true  
                        },
                    }).on('select2:open',function(){
                      if($('.select2-container .select2-dropdown').hasClass('select2-dropdown--below')){
                        var topmargin = 35;
                        $('.select2-container:not(.select2)').css('top', '+='+ topmargin +'px');
                      }
                    });

                });

                $('#unsubscribe_forums_topics').on('click',function(){

                    var $this = $(this);
                    if( $this.hasClass('disabled')){
                        return;
                    }

                    $this.addClass('disabled');
                    forum_topic_ids = $('#search_forums_topics.select').val();

                    $.ajax({
                        type: "POST",
                        dataType: 'json',
                        url: ajaxurl,
                        data: { action: 'get_all_users_for_selected_forum_topic', 
                                security: $('#security').val(),
                                id:forum_topic_ids,
                                },
                        cache: false,
                        success: function (json){

                            $('#unsubscribe_forums_topics').append('<div class="bbpbu_unsubscribe_forums_topics_progress" style="width:100%;margin-top:20px;margin-bottom:20px;height:10px;background:#fafafa;border-radius:10px;overflow:hidden;"><div class="bar" style="padding:0 1px;background:#37cc0f;height:100%;width:0;"></div></div>');

                            var x = 0;
                            var width = 100*1/json.length;
                              var number = width;
                              var loopArray = function(arr) {
                                  bbpbu_forums_topics_ajaxcall(arr[x],function(){
                                      x++;
                                      if(x < arr.length) {
                                         loopArray(arr);   
                                    }
                                }); 
                            }

                            // start 'loop'
                            loopArray(json);

                            function bbpbu_forums_topics_ajaxcall(obj,callback){

                                $.ajax({
                                 type: "POST",
                                  dataType: 'json',
                                 url: ajaxurl,
                                  data: {
                                      action:'unsubscribe_forums_topics',
                                      security: $('#security').val(),
                                      user:obj.id,
                                      id:obj.forum_topic_id,
                                      key:obj.meta_key,
                                  },
                                  cache: false,
                                  success: function (html){

                                    number = number + width;
                                    $('.bbpbu_unsubscribe_forums_topics_progress .bar').css('width',number+'%');

                                    if(number >= 100){
                                        $this.removeClass('disabled');
                                        $this.html('<strong><?php _e('All users successfuly unsubscribed from selected forum or topic','bbpbu'); ?>'+'</strong>');
                                    }

                                  }

                                });

                                // do callback when ready
                                callback();

                            }

                        }
                    });
                });

                $('#unsubscribe_user').on('click',function(){

                    var $this = $(this);
                    if( $this.hasClass('disabled')){
                        return;
                    }

                    $this.addClass('disabled');
                    user_ids = $('#search_users.select').val();

                    $.ajax({
                        type: "POST",
                        dataType: 'json',
                        url: ajaxurl,
                        data: {
                            action:'unsubscribe_user',
                            security: $('#security').val(),
                            id: user_ids,
                        },
                        cache: false,
                        success: function (json) {
                            $this.removeClass('disabled');
                            $this.text(json.success_message);
                        }
                    });

                });

            });
        </script>
        <?php
    }

    function get_all_users(){

        //Check security
        if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'hkl_security') || !is_user_logged_in()){

            _e('Security check Failed. Contact Administrator.','bbpbu');
            die();
        }

        //Get options
        global $wpdb;
        $prefix = $wpdb->get_blog_prefix();
        $forum_option_name = '_bbp_forum_subscriptions';
        $topic_option_name = '_bbp_subscriptions';
        $forum_option = $prefix . $forum_option_name;
        $topic_option = $prefix . $topic_option_name;

        //Get all User IDs
        $users = $wpdb->get_results("SELECT user_id FROM {$wpdb->usermeta} WHERE (meta_key = '$forum_option' OR meta_key = '$topic_option')");

        if(empty($users)){
            _e('No users found','bbpbu');
            die();
        }

        //Create Json
        $json = array();
        foreach($users as $user){
            $json[] = array('id'=>$user->user_id,'forum_option'=>$forum_option,'topic_option'=>$topic_option);
        }

        //Send Json
        print_r(json_encode($json));
        die();

    }

    function unsubscribe_all_users(){

        //Check security
        if (!isset($_POST['user']) || !isset($_POST['forum']) || !isset($_POST['topic']) || !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'hkl_security') || !is_user_logged_in()){

            _e('Security check Failed. Contact Administrator.','bbpbu');
            die();
        }

        //Define variables
        $user_id = $_POST['user'];
        $forum_option = $_POST['forum'];
        $topic_option = $_POST['topic'];

        //Unsubscribe user from all topics and forums
        delete_user_meta( $user_id, $forum_option );
        delete_user_meta( $user_id, $topic_option );

        die();

    }

    function get_users_forums_topics(){

        if (!current_user_can('edit_posts') || !isset($_POST['id']) || !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'hkl_security') || !is_user_logged_in()){

            _e('Security check Failed. Contact Administrator.','bbpbu');
            die();
        }

        //Get the IDs and the terms entered by the user
        $id = $_POST['id'];
        $q = $_POST['q'];

        global $wpdb;
        $return = array();
        $search_terms_like = '%'.$q['term'].'%';

        if($id == 'search_forums_topics'){

            //Get forums/topics
            $forums_topics = $wpdb->get_results( $wpdb->prepare( "SELECT ID , post_title  FROM {$wpdb->posts} WHERE ( post_title LIKE %s ) AND (post_type = 'forum' OR post_type = 'topic')", $search_terms_like ) );
            
            //Create Json for forums/topics
            if(!empty($forums_topics)){
                foreach($forums_topics as $forum_topic){
                    $return[] = array('id'=>$forum_topic->ID,'text'=>$forum_topic->post_title);
                }
            }

        }

        if($id == 'search_users'){

            //Get users
            $users = $wpdb->get_results( $wpdb->prepare( "SELECT ID , display_name  FROM {$wpdb->users} WHERE ( user_login LIKE %s OR display_name LIKE %s OR user_email LIKE %s )", $search_terms_like, $search_terms_like, $search_terms_like ) );
            
            //Create Json for Users
            if(!empty($users)){
                foreach($users as $user){
                    $return[] = array('id'=>$user->ID,'text'=>$user->display_name);
                }
            }

        }

        //Return users/forums/topics as Json data
        print_r(json_encode($return));
        die();

    }

    function get_all_users_for_selected_forum_topic(){

        //Check Security
        if ( !isset($_POST['id']) || !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'hkl_security') || !is_user_logged_in()){

            _e('Security check Failed. Contact Administrator.','bbpbu');
            die();
        }

        //Get selected forum/topic IDs
        $forum_topic_ids = $_POST['id'];
        if(empty($forum_topic_ids)){
            die();
        }

        //Get Options
        global $wpdb;
        $prefix = $wpdb->get_blog_prefix();
        $forum_option_name = '_bbp_forum_subscriptions';
        $topic_option_name = '_bbp_subscriptions';
        $forum_option = $prefix . $forum_option_name;
        $topic_option = $prefix . $topic_option_name;

        //Create Json
        $json = array();
        foreach ($forum_topic_ids as $forum_topic_id){

            //Get all users subscribed to the forum/topic
            $users = $wpdb->get_results("SELECT user_id,meta_key FROM {$wpdb->usermeta} WHERE (meta_key = '$forum_option' OR meta_key = '$topic_option') AND (meta_value LIKE '%,$forum_topic_id,%' OR meta_value LIKE '$forum_topic_id,%' OR meta_value LIKE '%,$forum_topic_id' )");

            if(!empty($users)){
                foreach ($users as $user) {
                    $json[] = array('id'=>$user->user_id,'meta_key'=>$user->meta_key,'forum_topic_id'=>$forum_topic_id,'');
                }
            }

        }

        //Send Json
        print_r(json_encode($json));
        die();

    }

    function unsubscribe_forums_topics(){

        //Check Security
        if ( !isset($_POST['user']) || !isset($_POST['id']) || !isset($_POST['key']) || !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'hkl_security') || !is_user_logged_in()){

            _e('Security check Failed. Contact Administrator.','bbpbu');
            die();
        }

        //Define variables
        $user_id = $_POST['user'];
        $forum_topic_id = $_POST['id'];
        $key = $_POST['key'];

        //Get the subscriptions of the user
        $value = get_user_meta( $user_id, $key, true );

        //Check if value is empty
        if(empty($value)){
            die();
        }

        if(strpos($value, ',') !== false){
            $value = explode(',', $value);
        }else{
            $value = (array)$value;
        }

        //Get position in the value
        $pos = array_search( $forum_topic_id, $value );
        if ( false === $pos ) {
            die();
        }

        //Delete the forum/topic id from the value
        unset($value[$pos]);
        $value = implode( ',', $value );

        //Update user meta OR unsubscribe user from the forum/topic
        update_user_meta( $user_id, $key, $value );

        die();

    }

    function unsubscribe_user(){

        //Check Security
        if ( !isset($_POST['id']) || !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'hkl_security') || !is_user_logged_in()){

            _e('Security check Failed. Contact Administrator.','bbpbu');
            die();
        }

        //Get selected User IDs
        $user_ids = $_POST['id'];
        if(empty($user_ids)){
            die();
        }

        //Get Options
        global $wpdb;
        $prefix = $wpdb->get_blog_prefix();
        $forum_option_name = '_bbp_forum_subscriptions';
        $topic_option_name = '_bbp_subscriptions';
        $forum_option = $prefix . $forum_option_name;
        $topic_option = $prefix . $topic_option_name;

        //Remove/unsubscribe users from all forums and topics
        foreach ($user_ids as $user_id) {
            delete_user_meta( $user_id, $forum_option );
            delete_user_meta( $user_id, $topic_option );
        }

        // Create json
        $json_array = array(
            'success_message'=> __('Selected users unsubscribed from all Forums and Topics','bbpbu')
             );
        //Send json
        print_r(json_encode($json_array));

        die();

    }

}

BBP_Bulk_Unsubscribe_Tools::init();
