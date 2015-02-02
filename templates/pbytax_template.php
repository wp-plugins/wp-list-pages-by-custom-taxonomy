<?php
/*
 * Template for the output of the Widget
 * Override by placing a file called pbytax_template.php in your active theme
 */
	//// !!! THIS FIRST PART IS TO BUILD UP THE QUERY. DO NOT EDIT IT IF YOU'RE NOT SURE WHAT YOU'RE DOING
	
	// store the vars
	$intro = $instance['intro'];
	$max_entries = $instance['max_entries'];
	if ($max_entries=="0"){
		$max_entries= -1;
	}
	$filter_term_id = $instance['filter_term'];
	if ( in_array('any', $filter_term_id) ){
		$filter_term_id = 'any';
	}
	$post_type = $instance['filter_post_type'];
	$taxonomy = $instance['filter_taxonomy'];
	$order_by = $instance['order_by'];
	$order_style = $instance['order_style'];
	$include_children = $instance['include_children'];
	$display_thumb = $instance['display_thumb'];
	$thumb_max_width = $instance['thumb_max_width'];
	$display_excerpt = $instance['display_excerpt'];
	$excerpt_length = $instance['excerpt_length'];
	$display_date = $instance['display_date'];
	$display_in_dropdown = $instance['display_in_dropdown'];
	$dropdown_text = $instance['dropdown_text'];
	$exclude_posts = $instance['exclude_posts'];
	if ($exclude_posts!=""){
		$exclude_posts = explode("-", $exclude_posts);	
	}
	$meta_key_name = $instance['meta_key_name'];
	$meta_key_value = $instance['meta_key_value'];
	$meta_is_number = $instance['meta_is_number'];
	// if meta field is numeric and orderby is set to meta key, tweak it
	$meta_compare = $instance['meta_compare'];
	// if orderby is set to meta value, but no meta key name and value are passed, default to order by date
	// if meta field is numeric and orderby is set to meta value, tweak it to numeric order
	if ($order_by=="meta_value"){
		if ($meta_key_name=="none" || $meta_key_value==""){
			$order_by = "date";
		}
		if ($meta_is_number=="yes"){
			$order_by = "meta_value_num";
		}
	}
	// build up the args for the query
	$pbytax_args =  array(
		'numberposts' => $max_entries ,
		'post_type' => $post_type,
		'orderby' => $order_by,
		'order' => $order_style,
		'post_status' => 'publish',
		); 
	// if there are posts to be excluded, add the parameter
	if ( !empty($exclude_posts) ){
		$pbytax_args['post__not_in'] = $exclude_posts;
	}
	// if a meta key has been passed, add it to the query
	if ($meta_key_name!="none") {
		$pbytax_args['meta_key'] = $meta_key_name;
		if ($meta_key_value!="") {
			//  for order purposes
			if ($meta_is_number=="yes" && $order_by=="meta_value_num"){
				$pbytax_args['meta_value_num'] = $meta_key_value;	
			}
			elseif ($order_by=="meta_value") {
				$pbytax_args['meta_value'] = $meta_key_value;
			}
			// for fetching purposes
			if ($meta_is_number=="yes"){
				$meta_type= "NUMERIC";
			}
			else {
				$meta_type= "CHAR";
			}
			$pbytax_args['meta_query'][0] = array('key'=>$meta_key_name, 'value'=>$meta_key_value, 'compare'=>"$meta_compare", 'type'=>$meta_type);

		}
	}
	// if "any" term is selected, we have to retrieve a list of terms ids for the selected taxonomy
	if ($filter_term_id=="any"){
		$filter_term_id = get_terms( $taxonomy, array('fields' => 'ids'	) );
	}
	// add the taxonomy query to the args
	$pbytax_args['tax_query'] = array( 
		 array(
			'taxonomy' => $taxonomy, 
			'field' => 'id', 
			'terms' => $filter_term_id, 				
			'operator' => 'IN' 
			)
		);
	// add children inclusion to the tax query
	if ($include_children=="true"){
		$pbytax_args['tax_query'][0]['include_children'] = true;
	}
	else {
		$pbytax_args['tax_query'][0]['include_children'] = false;
	}

	
	//// THE LOOP STARTS HERE,
	// HERE YOU COULD EDIT THE HTML STRUCTURE OF THE OUTPUT

	// query the posts and print the list
	$posts = get_posts( $pbytax_args );
	if (!empty($posts)){
		
		// display the introduction text/html ?>
		<div class="pbytax-intro">
        	<?php echo $intro; ?>
		</div>
        
        <?php
		//// if the output should be in a DROPDOWN SELECTOR
		if ($display_in_dropdown=="yes"){
			?>
            
            <select class="pbytax-dropdown" id="pbytax-selector" onchange="location = this.options[this.selectedIndex].value;">
            	<option value="#"><?php echo $dropdown_text; ?> </option>
            	<?php // loop the posts
				foreach( $posts as $post ) {	
					?>
            		<option value="<?php echo get_permalink( $post->ID ); ?>">
                    	<?php echo $post->post_title ?>          
                    </option>
            	<?php // close the foreach loop of posts
				} ?>
            </select>
            
        <?php //// if this was NORMAL WIDGET rather than dropdown
		} 
		else {
			?>
            
            <ul class="pbytax-list">                           
                <?php // loop the posts
                foreach( $posts as $post ) {	
                    ?>
                    
                    <li class="pbytax-item">
                        <?php // display thumb if required
                        if ($display_thumb=="yes"){
                            ?>
                            <div class="pbytax-thumb" <?php if($thumb_max_width!=60){ echo 'style="max-width:'.$thumb_max_width.'px"'; } ?>>
                                <?php // display the image if any is set
                                if (get_the_post_thumbnail($post->ID)){ 
                                    echo get_the_post_thumbnail( $post->ID, 'thumbnail', array('class' => 'img-full-width') ); 
                                } else { // display the no-thumb image instead
                                    echo '<img src="'.plugins_url().'/wp-list-pages-by-custom-taxonomy/images/no-thumb.jpg" />'; 
                                } ?>                          
                            </div>	
                        <?php } ?>
                            
                        <a class="pbytax-post-title" href="<?php echo get_permalink( $post->ID ); ?>" title="<?php echo $post->post_title ?>"><?php echo $post->post_title ?></a>              
                        <?php if ($display_date=="yes"){
                            ?>
                            <span class="pbytax-date"><?php echo get_the_date( 'd-m-Y', $post->ID ); ?></span>
                        <?php } ?>
                        
                        <?php // display the excerpt if required
                        if ($display_excerpt=="yes"){
                            ?>
                            <div class="pbytax-excerpt">
                                <?php // get the excerpt but display only the number of characters set in the options
                                $excerpt = wp_trim_excerpt( strip_tags( strip_shortcodes($post->post_content) ) ); 
                                $excerpt = substr ( $excerpt, 0, $excerpt_length ); echo $excerpt.' [...]'; ?>                          
                            </div>	
                        <?php } ?>
                          
                    </li>                	
                    
                <?php // close the foreach loop of posts
                } ?>            
            </ul>
       	<?php // end the loop
		} ?>
        
    <?php
	} // if no posts
	else {
		echo "no matches";
	}
	

	
	// reset the postdata
	wp_reset_postdata();
		
?>     

