<?php
/*
Plugin Name: Admins Standalone - sidebar widget
Plugin URI: https://github.com/CNDLS/adminwidget
Description: Adds a sidebar widget that lists the administrators/instructors in the blog
Author: CNDLS
Author URI: http://cndls.georgetown.edu
*/
/**
 * AdminWidget Class
 */
class AdminWidget extends WP_Widget {
    /** constructor */
    function AdminWidget() {
		$widget_ops = array('classname' => 'widget_listadmins', 'description' => __( 'Displays a list of blog administrators/instructors') );
		parent::WP_Widget(false, $name = 'Admins', $widget_ops);	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        echo $before_widget;
        if ( $title )
        	echo $before_title . $title . $after_title;
        #Call custom function from author-plugin.php in the mu-plugins directory
		echo '<ul>';
		$this->list_admin_users('show_fullname=1&optioncount=1&hide_empty=0'); 
        echo '</ul>';
		echo $after_widget; 
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    /** @see WP_Widget::form */
	#Allows the blog admin to give a title to the widget (displayed in the sidebar)
    function form($instance) {				
        $title = esc_attr($instance['title']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php 
    }
    
    function list_admin_users($args = '') {
    	global $wpdb, $blog_id;

    	$defaults = array(
    		'optioncount' => false, 'exclude_admin' => true,
    		'show_fullname' => false, 'hide_empty' => true,
    		'feed' => '', 'feed_image' => '', 'echo' => true
    	);

    	$r = wp_parse_args( $args, $defaults );
    	extract($r, EXTR_SKIP);

    	$return = '';

    	// $sub_query = "SELECT DISTINCT user_id from $wpdb->usermeta WHERE $wpdb->usermeta.meta_key = 'wp_".$wpdb->blogid."_user_level'";
    	$user_query = get_users('role=administrator');
        $admin_list = array();
        // print_r($user_query);
    
        foreach ($user_query as $admin) {
        	// print_r($author->ID);
        	array_push($admin_list,$admin->ID);
        }
        // print_r($author_list);

        $admin_id_list = implode(',', $admin_list);

        if (!empty($admin_id_list)) { # check to make sure the array isn't empty
            $authors = $wpdb->get_results("SELECT user_id as ID, meta_value from $wpdb->usermeta
                                        WHERE $wpdb->usermeta.meta_key = 'last_name' AND $wpdb->usermeta.user_id IN ($admin_id_list)
                                        ORDER BY meta_value");
        
            $author_count = array();
            foreach ((array) $wpdb->get_results("SELECT DISTINCT post_author, COUNT(ID) AS count FROM $wpdb->posts WHERE post_type = 'post' AND " . get_private_posts_cap_sql( 'post' ) . " GROUP BY post_author") as $row) {
                $author_count[$row->post_author] = $row->count;
            }

            # fetch the ID of the owner from the database
            $owner['id'] = $wpdb->get_var("select id from $wpdb->users where user_email = '" . get_option('admin_email') . "'");

            # find the owner in the admin list
            $i = 0;
            foreach ( (array) $authors as $author ) {
                if ($owner['id'] == $author->ID) {
                    $owner['key'] = $i;
                }
                $i++;
            }

            # reorder the admins array if necessary
            # 1) preserve the owner's object
            # 2) remove the owner from the admins array
            # 3) reset the array keys (array_merge trick)
            # 4) take the preserved owner object and prepend on the admins array
            if ($owner['key']) {
                $owner['object'] = $authors[$owner['key']];
                unset($authors[$owner['key']]);
                $authors = array_merge($authors);
                array_unshift($authors, $owner['object']);
            }

            foreach ( (array) $authors as $author ) {
                $author = get_userdata( $author->ID );
                $posts = (isset($author_count[$author->ID])) ? $author_count[$author->ID] : 0;
                $name = $author->display_name;
        
                //Get the role for the authors
                $auth = $author -> ID;
                $auth_info = get_usermeta($auth, "wp_".$blog_id."_capabilities");
                # If user is a member of blog, only then does the $auth_info variable has value.
                # Hence, if check is this is a NON-EMPTY variable (Handles the error thrown if the $auth_info var is empty)
                if ($auth_info)
                    $auth_role = key($auth_info);
        
                if ($auth_role == 'administrator'){
                if ( $show_fullname && ($author->first_name != '' && $author->last_name != '') )
                    $name = "$author->first_name $author->last_name";

                if ( !($posts == 0 && $hide_empty) )
                    $return .= '<li>';
                if ( $posts == 0 ) {
                    if ( !$hide_empty )
                        $link = $name;
                } else {
                    $link = '<a href="' . get_author_posts_url($author->ID, $author->user_nicename) . '" title="' . sprintf(__("Posts by $name %s"), attribute_escape($author->display_name)) . '">' . $name . '</a>';

                    if ( (! empty($feed_image)) || (! empty($feed)) ) {
                        $link .= ' ';
                        if (empty($feed_image))
                            $link .= '(';
                        $link .= '<a href="' . get_author_rss_link(0, $author->ID, $author->user_nicename) . '"';

                        if ( !empty($feed) ) {
                            $title = ' title="' . $feed . '"';
                            $alt = ' alt="' . $feed . '"';
                            $name = $feed;
                            $link .= $title;
                        }

                        $link .= '>';

                        if ( !empty($feed_image) )
                            $link .= "<img src=\"$feed_image\" border=\"0\"$alt$title" . ' />';
                        else
                            $link .= $name;

                        $link .= '</a>';

                        if ( empty($feed_image) )
                            $link .= ')';
                    } //End of first empty(feed) if condition

                    if ( $optioncount )
                        $link .= ' ('. $posts . ')';

                }//End of else part of post == 0 condition

                if ( !($posts == 0 && $hide_empty) )
                    $return .= $link . '</li>';
            } //End of foreach loop
            } //Checking roles

        } // End check for empty array
	
    	if ( !$echo )
    		return $return;
    	echo $return;
	
    } //End of function
    

} // class AdminWidget

add_action('widgets_init', create_function('', 'return register_widget("AdminWidget");'));
?>