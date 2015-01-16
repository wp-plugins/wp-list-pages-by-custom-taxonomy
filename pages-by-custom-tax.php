<?php
/*
Plugin Name: WP List Pages by Custom Taxonomy
Description: Widget that allow to list XX posts of any active post-type, filtering by any term of any active custom taxonomy, and display only title or thumbnail and excerpt too. you can also exclude specific posts by id!
Author: Andrea Piccart
Version: 1.2.0
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
		// load the scripts and stylesfor the admin
		add_action( 'sidebar_admin_setup', array( $this, 'pbytax_admin_setup' ) );
		// load the styles for the frontend
		add_action( 'wp_enqueue_scripts',array( $this, 'pbytax_styles_setup') );
	}
	
	// function to add scripts and styles to the setting page
	function pbytax_admin_setup() {
		wp_register_script('pbytax-admin-js', plugins_url('js/pbytax_admin.js', __FILE__), array( 'jquery' ) );
		wp_enqueue_script('pbytax-admin-js');
		wp_register_style('pbytax-admin-styles', plugins_url('css/pbytax-admin-style.css',__FILE__ ), false, NULL, false);
		wp_enqueue_style('pbytax-admin-styles'); 
		
	}
	// function to load styles in frontend
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
			'meta_key_name' => 'none',
			'meta_key_value' => '',
			'meta_is_number' => 'no',
			'meta_compare' => '=',
			'order_by' => 'date',
			'order_style' => 'DESC',
			'include_children' => 'true',
			'display_thumb' => 'no',
			'thumb_max_width' => 60,
			'display_excerpt' => 'no',
			'excerpt_length' => 50,
			'display_date' => 'no',
			'display_in_dropdow' => 'no',
			'exclude_posts' => ''
			);
		// if vars are set, override defaults
		$instance = wp_parse_args( $instance, $defaults );
		// convert the array to separated variables
		extract($instance, EXTR_OVERWRITE);

		// print the field for the title and the field for the number of posts to display
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
            	<option value="any" <?php if($filter_post_type=="any"){ echo "selected"; } ?> onclick="displayMetaKeysSelector('any', '<?php echo $this->number; ?>')" >any</option>
				<?php // get all registered post types and print them excluding the useless default ones
                $post_types_list =  get_post_types( '', 'names' ); 
                foreach ($post_types_list as $post_type_name){	
                    if ($post_type_name!='attachment' && $post_type_name!='revision' && $post_type_name!='nav_menu_item'){
                        ?>
                        <option value="<?php echo $post_type_name; ?>" <?php if($post_type_name==$filter_post_type){ echo "selected"; } ?> onclick="displayMetaKeysSelector('<?php echo $post_type_name; ?>', '<?php echo $this->number; ?>')" ><?php echo $post_type_name; ?></option>
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
                <option value="comment_count" <?php if($order_by=="comment_count"){ echo "selected"; } ?> >Comments</option>
        		<option value="rand" <?php if($order_by=="rand"){ echo "selected"; } ?> >Random</option>
                <option value="meta_value" <?php if($order_by=="meta_value"){ echo "selected"; } ?> >Meta Field (need to set Meta Key Name)</option>
            </select>

        	<label for="<?php echo $this->get_field_id( 'order_style' ); ?>"><?php _e( 'Order:' ); ?> </label>
        	<select class="widefat" id="<?php echo $this->get_field_id( 'order_style' ); ?>" name="<?php echo $this->get_field_name( 'order_style' ); ?>">
            	<option value="ASC" <?php if($order_style=="ASC"){ echo "selected"; } ?> >Ascendant</option>
                <option value="DESC" <?php if($order_style=="DESC"){ echo "selected"; } ?> >Descendant</option>
            </select>
        </p>
        <p>
        	<input type="checkbox" class="checkbox-margin" value="true" name="<?php echo $this->get_field_name( 'include_children' ); ?>" <?php if($include_children=="true"){ echo "checked"; } ?> > Include Children
		<br/>
        	<input type="checkbox" class="checkbox-margin" value="yes" name="<?php echo $this->get_field_name( 'display_date' ); ?>" <?php if($display_date=="yes"){ echo "checked"; } ?> > Display Date
        <br/>
        	<input type="checkbox" class="checkbox-margin" value="yes" name="<?php echo $this->get_field_name( 'display_thumb' ); ?>" <?php if($display_thumb=="yes"){ echo "checked"; } ?> > Display Thumbnail  <span class="float-right-field">Max Width:<input class="small-number" id="<?php echo $this->get_field_id( 'thumb_max_width' ); ?>" name="<?php echo $this->get_field_name( 'thumb_max_width' ); ?>" type="number" value="<?php echo esc_attr( $thumb_max_width ); ?>" step="1" min="10" /> </span>
        <br/>
        	<input type="checkbox" class="checkbox-margin" value="yes" name="<?php echo $this->get_field_name( 'display_excerpt' ); ?>" <?php if($display_excerpt=="yes"){ echo "checked"; } ?> > Display Excerpt <span class="float-right-field">Length:<input class="small-number" id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" type="number" value="<?php echo esc_attr( $excerpt_length ); ?>" step="1" min="10" /> </span>
        <br/>   
        	<input type="checkbox" class="checkbox-margin" value="yes" name="<?php echo $this->get_field_name( 'display_in_dropdown' ); ?>" <?php if($display_in_dropdown=="yes"){ echo "checked"; } ?> > Display only Titles in a Dropdown Selector   
        <br/>  	
        </p>
        <p>
			<label for="<?php echo $this->get_field_id( 'exclude_posts' ); ?>"><?php _e( 'Exclude posts:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'exclude_posts' ); ?>" name="<?php echo $this->get_field_name( 'exclude_posts' ); ?>" type="text" value="<?php echo $exclude_posts; ?>"><br/> (insert ids separated by a medium dash - or a space)
		</p>
        <div class="meta_fields_options" <?php if ($filter_post_type=="any"){ echo 'style="display:none"'; } ?> >
        	<p>
            CUSTOM META FIELDS OPTIONS <br/>
                <label for="<?php echo $this->get_field_id( 'meta_key_name' ); ?>"><?php _e( 'Meta Field Name:' ); ?> </label>
                <?php // foreach post_type (retrieved previously) print a selector with the meta-keys
                foreach ($post_types_list as $post_type_name){	
                    if ($post_type_name!='attachment' && $post_type_name!='revision' && $post_type_name!='nav_menu_item'){
                        // get all custom meta keys for this post type
                        $meta_keys_list = $this->pbytax_get_post_type_meta_keys($post_type_name);
                        ?>
                        <select class="widefat meta-keys-selector-<?php echo $this->number; ?>" id="<?php echo $post_type_name.'-keys-'.$this->number; ?>" name="<?php echo $this->get_field_name( 'meta_key_name' ); ?>" <?php if($filter_post_type!=$post_type_name){ echo 'style="display:none" disabled'; } ?>>
                            <option value="none" <?php if($meta_key_name=="none"){ echo "selected"; } ?> >none</option>
                            <?php // foreach meta key, print an option
                            foreach ($meta_keys_list as $meta_key){
                                ?>
                                <option value="<?php echo $meta_key; ?>" <?php if($post_type_name==$filter_post_type && $meta_key_name==$meta_key){ echo "selected"; } ?> ><?php echo $meta_key; ?></option>
                            <?php 
                            } ?>
                        </select>       
                    <?php
                    }
                } ?>
                <br/>
                <label for="<?php echo $this->get_field_id( 'meta_key_value' ); ?>"><?php _e( 'Meta field Value (leave blank to not filter by meta field):' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'meta_key_value' ); ?>" name="<?php echo $this->get_field_name( 'meta_key_value' ); ?>" type="text" value="<?php echo $meta_key_value; ?>">
                <br/>
                <label for="<?php echo $this->get_field_id( 'meta_compare' ); ?>"><?php _e( 'Meta Compare:' ); ?> </label>
                <select class="widefat" id="<?php echo $this->get_field_id( 'meta_compare' ); ?>" name="<?php echo $this->get_field_name( 'meta_compare' ); ?>">
                    <option value="=" <?php if($meta_compare=="="){ echo "selected"; } ?> >=</option>
                    <option value="!=" <?php if($meta_compare=="!="){ echo "selected"; } ?> > &ne;</option>
                    <option value=">=" <?php if($meta_compare==">="){ echo "selected"; } ?> > &gt;=</option>
                    <option value="<=" <?php if($meta_compare=="<="){ echo "selected"; } ?> > &lt;=</option>
                    <option value=">" <?php if($meta_compare==">"){ echo "selected"; } ?> > &gt;</option>
                    <option value="<" <?php if($meta_compare=="<"){ echo "selected"; } ?> > &lt;</option>
                    <option value="LIKE" <?php if($meta_compare=="LIKE"){ echo "selected"; } ?> > LIKE</option>
                    <option value="NOT LIKE" <?php if($meta_compare=="NOT LIKE"){ echo "selected"; } ?> > NOT LIKE</option>
                    <option value="IN" <?php if($meta_compare=="IN"){ echo "selected"; } ?> > IN</option>
                    <option value="NOT IN" <?php if($meta_compare=="NOT IN"){ echo "selected"; } ?> > NOT IN</option>
                </select>
                <br/>
                <input style="padding-top:4px;" type="checkbox" value="yes" name="<?php echo $this->get_field_name( 'meta_is_number' ); ?>" <?php if($meta_is_number=="yes"){ echo "checked"; } ?> > Numeric Meta Field
			</p>
       	</div>

        
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
		$instance['meta_key_name'] = $new_instance['meta_key_name'];
		$instance['meta_key_value'] = $new_instance['meta_key_value']; // this could be anything, so we can't sanitize and user must be careful to fill it
		$instance['meta_is_number'] = $new_instance['meta_is_number'];
		$instance['meta_compare'] = $new_instance['meta_compare'];
		$instance['order_by'] = $new_instance['order_by'];
		$instance['order_style'] = $new_instance['order_style'];	
		$instance['include_children'] = $new_instance['include_children'];
		$instance['display_thumb'] = $new_instance['display_thumb'];
		$instance['thumb_max_width'] = $new_instance['thumb_max_width'];	
		$instance['display_excerpt'] = $new_instance['display_excerpt'];
		$instance['excerpt_length'] = $new_instance['excerpt_length'];
		$instance['display_date'] = $new_instance['display_date'];
		$instance['display_in_dropdown'] = $new_instance['display_in_dropdown'];

		return $instance;
	}
	
	
	// function to query all custom meta keys names for the required post-type
	public function pbytax_get_post_type_meta_keys($post_type){
		global $wpdb;
		// quit if no post type is passed
		if (!$post_type) return;
		
		$query = "
			SELECT DISTINCT($wpdb->postmeta.meta_key) 
			FROM $wpdb->posts 
			LEFT JOIN $wpdb->postmeta 
			ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
			WHERE $wpdb->posts.post_type = '%s' 
			AND $wpdb->postmeta.meta_key != '' 
			AND $wpdb->postmeta.meta_key NOT RegExp '(^[_0-9].+$)' 
			AND $wpdb->postmeta.meta_key NOT RegExp '(^[0-9]+$)'
		";
		$meta_keys = $wpdb->get_col($wpdb->prepare($query, $post_type));

		return $meta_keys;
	
	}
	
	

} // class Pages_by_Tax