<?php
/*
Plugin Name: Admins - sidebar widget
Plugin URI: http://cndls.georgetown.edu
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
        require_once(ABSPATH.'wp-content/author-plugin.php');
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
		wp_list_admin_users('show_fullname=1&optioncount=1&hide_empty=0'); 
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

} // class AdminWidget

add_action('widgets_init', create_function('', 'return register_widget("AdminWidget");'));
?>