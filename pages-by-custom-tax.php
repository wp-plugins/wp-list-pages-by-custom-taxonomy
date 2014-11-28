<?php
/*
Plugin Name: WP List Pages by Custom Taxonomy
Description: Widget that allow to list XX posts of any active post-type, filtering by any term of any active custom taxonomy, and display only title or thumbnail and excerpt too. you can also exclude specific posts by id!
Author: Andrea Piccart
Version: 1.0
Author URI: http://www.affordable-web-developer.com
*/

// Block direct access to this file
if ( !defined('ABSPATH') ) {
	die('-1');
}

// register the widget
add_action( 'widgets_init', 'register_pbytax_widget');	
function register_pbytax_widget() {
	register_widget( 'Pages_by_Tax' );
}

/**
 * Adds Pages_by_Tax widget.
 */
class Pages_by_Tax extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'Pages_by_Tax', // Base ID
			__('WP Pages by Custom Taxonomy', 'wp-list-pages-by-custom-taxonomy'), // Name
			array( 'description' => __( 'Widget that allow to list XX posts of any active post-type, filtering by any term of any active custom taxonomy, and display only title or thumbnail and excerpt too. you can also exclude specific posts by id!', 'pages-by-custom-tax' ), ) // Args
		);
		// load the scripts for the admin
		add_action( 'sidebar_admin_setup', array( $this, 'pbytax_admin_setup' ) );
		// load the styles for the frontend
		add_action( 'wp_enqueue_scripts',array( $this, 'pbytax_styles_setup') );
	}
	
	// function to add scripts to the setting page
	function pbytax_admin_setup() {
		wp_register_script('pbytax-admin-js', plugins_url('js/pbytax_admin.js', __FILE__), array( 'jquery' ) );
		wp_enqueue_script('pbytax-admin-js'); 
		
	}
	
	function pbytax_styles_setup() {
		wp_register_style('pbytax-styles', plugins_url('css/pbytax-style.css',__FILE__ ), false, NULL, false);
		wp_enqueue_style('pbytax-styles');
	}
	

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		// print before widget options and title
     	echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		
		// use a template for the output so that it can easily be overridden by theme
		// check for template in active theme
		$template = locate_template(array('pbytax_template.php'));
		// if none found use the default template
		if ( $template == '' ) {
			$template = 'templates/pbytax_template.php';
		}
		include ( $template ); 
		
		// print after widget
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		// set defaults
		$defaults = array (
			'title' => 'Widget title',
			'max_entries' => 10,
			'filter_post_type' => 'post',
			'filter_taxonomy' => 'category',
			'filter_term' => array('any'),
			'order_by' => 'date',
			'order_style' => 'DESC',
			'include_children' => 'true',
			'display_thumb' => 'no',
			'display_excerpt' => 'no',
			'exclude_posts' => ''
			);
		// if vars are set, override defaults
		$instance = wp_parse_args( $instance, $defaults );
		// convert the array to separated variables
		extract($instance, EXTR_OVERWRITE);
		
		
		// print the field for the title and the field for the number of pages to display
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
        <p>
			<label for="<?php echo $this->get_field_id( 'max_entries' ); ?>"><?php _e( 'Max Entries:' ); ?> </label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'max_entries' ); ?>" name="<?php echo $this->get_field_name( 'max_entries' ); ?>" type="number" value="<?php echo esc_attr( $max_entries ); ?>" step="1" min="0"> (set 0 to list all)
		</p>
        <p>
        	<label for="<?php echo $this->get_field_id( 'filter_post_type' ); ?>"><?php _e( 'Post Type:' ); ?> </label>
            <select class="widefat" id="<?php echo $this->get_field_id( 'filter_post_type' ); ?>" name="<?php echo $this->get_field_name( 'filter_post_type' ); ?>">
            	<option value="any" <?php if($filter_post_type=="any"){ echo "selected"; } ?> >any</option>
				<?php // get all registered post types and print them excluding the useless default ones
                $post_types_list =  get_post_types( '', 'names' ); 
                foreach ($post_types_list as $post_type_name){	
                    if ($post_type_name!='attachment' && $post_type_name!='revision' && $post_type_name!='nav_menu_item'){
                        ?>
                        <option value="<?php echo $post_type_name; ?>" <?php if($post_type_name==$filter_post_type){ echo "selected"; } ?> ><?php echo $post_type_name; ?></option>
                    <?php
                    }
                } ?>
            </select>       
        </p>
        <p>
        	<label for="<?php echo $this->get_field_id( 'filter_taxonomy' ); ?>"><?php _e( 'Taxonomy:' ); ?> </label>
            <select class="widefat tax-selector" id="<?php echo $this->get_field_id( 'filter_taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'filter_taxonomy' ); ?>">
            <?php // get all registered taxonomies and print them
			$taxonomies_list = get_taxonomies();
			foreach ($taxonomies_list as $tax_name){			
				?>
            	<option value="<?php echo $tax_name; ?>" <?php if($tax_name==$filter_taxonomy){ echo "selected"; } ?> onclick="displayTermsSelector('<?php echo $tax_name; ?>', '<?php echo $this->number; ?>')" ><?php echo $tax_name; ?></option>
            <?php 
			} ?>
            </select>
            
            <label for="<?php echo $this->get_field_id( 'filter_term' ); ?>"><?php _e( 'Pull from this Term:' ); ?> </label>
            
            <?php // build a selector for each taxonomy, listing the terms. jquery will then display the correct one based on selected taxonomy
			foreach ($taxonomies_list as $tax_name){
 	           ?>
           		<select multiple class="widefat terms-selector-<?php echo $this->number; ?>" <?php if($filter_taxonomy!=$tax_name){ echo 'style="display:none" disabled'; } ?> id="<?php echo $tax_name.'-'.$this->number; ?>" name="<?php echo $this->get_field_name( 'filter_term' ); ?>[]" >
            		<option value="any" <?php if(in_array('any', $filter_term) && $filter_taxonomy==$tax_name){ echo "selected"; } ?> >any</option>
					<?
                    // get the terms of the taxonomy and print them
                    $terms = get_terms( $tax_name );
                    foreach ($terms as $term) {            
                        ?>
                        <option value="<?php echo $term->term_id; ?>" <?php if(in_array($term->term_id, $filter_term) && $filter_taxonomy==$tax_name){ echo "selected"; } ?> ><?php echo $term->name; ?></option>      
                    <? } ?>
                </select>
           	<? } ?>
		</p>
        <p>
        	<label for="<?php echo $this->get_field_id( 'order_by' ); ?>"><?php _e( 'Order By:' ); ?> </label>
        	<select class="widefat" id="<?php echo $this->get_field_id( 'order_by' ); ?>" name="<?php echo $this->get_field_name( 'order_by' ); ?>">
            	<option value="date" <?php if($order_by=="date"){ echo "selected"; } ?> >Date</option>
                <option value="title" <?php if($order_by=="title"){ echo "selected"; } ?> >Title</option>
        		<option value="rand" <?php if($order_by=="rand"){ echo "selected"; } ?> >Random</option>
            </select>

        	<label for="<?php echo $this->get_field_id( 'order_style' ); ?>"><?php _e( 'Order:' ); ?> </label>
        	<select class="widefat" id="<?php echo $this->get_field_id( 'order_style' ); ?>" name="<?php echo $this->get_field_name( 'order_style' ); ?>">
            	<option value="ASC" <?php if($order_style=="ASC"){ echo "selected"; } ?> >Ascendant</option>
                <option value="DESC" <?php if($order_style=="DESC"){ echo "selected"; } ?> >Descendant</option>
            </select>
        </p>
        <p>
        	<input type="checkbox" value="true" name="<?php echo $this->get_field_name( 'include_children' ); ?>" <?php if($include_children=="true"){ echo "checked"; } ?> > Include Children
		<br/>
        	<input type="checkbox" value="yes" name="<?php echo $this->get_field_name( 'display_thumb' ); ?>" <?php if($display_thumb=="yes"){ echo "checked"; } ?> > Display Thumbnail
        <br/>
        	<input type="checkbox" value="yes" name="<?php echo $this->get_field_name( 'display_excerpt' ); ?>" <?php if($display_excerpt=="yes"){ echo "checked"; } ?> > Display Excerpt
        </p>
        <p>
			<label for="<?php echo $this->get_field_id( 'exclude_posts' ); ?>"><?php _e( 'Exclude posts:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'exclude_posts' ); ?>" name="<?php echo $this->get_field_name( 'exclude_posts' ); ?>" type="text" value="<?php echo $exclude_posts; ?>"><br/> (insert ids separated by a medium dash - or a space)
		</p>
        
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['exclude_posts'] = sanitize_title( $new_instance['exclude_posts']); // although we specify to put dashes, we'll sanitize the input to reduce risks
		// other fields are selectors and number field, so data will always be sanitized
		$instance['max_entries'] = $new_instance['max_entries'];
		$instance['filter_post_type'] = $new_instance['filter_post_type'];
		$instance['filter_taxonomy'] = $new_instance['filter_taxonomy'];
		$instance['filter_term'] = $new_instance['filter_term'];
		$instance['order_by'] = $new_instance['order_by'];
		$instance['order_style'] = $new_instance['order_style'];	
		$instance['include_children'] = $new_instance['include_children'];
		$instance['display_thumb'] = $new_instance['display_thumb'];	
		$instance['display_excerpt'] = $new_instance['display_excerpt'];

		return $instance;
	}

} // class Pages_by_Tax