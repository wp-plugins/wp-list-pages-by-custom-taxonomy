<?php
/*
 * Template for the output of the Widget
 * Override by placing a file called pbytax_template.php in your active theme
 */
	// !!! THIS FIRST PART IS TO BUILD UP THE QUERY. DO NOT EDIT IT IF YOU'RE NOT SURE WHAT YOU'RE DOING
	
	// store the vars
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
	$display_excerpt = $instance['display_excerpt'];
	$exclude_posts = $instance['exclude_posts'];
	if ($exclude_posts!=""){
		$exclude_posts = explode("-", $exclude_posts);	
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

	
	// THE LOOP STARTS HERE,
	// HERE YOU COULD EDIT THE HTML STRUCTURE OF THE OUTPUT

	// query the posts and print the list
	$posts = get_posts( $pbytax_args );
	if (!empty($posts)){
		?>
        <ul class="pbytax-list">
                       
        	<?
			foreach( $posts as $post ) {	
				?>
				
				<li class="pbytax-item">
                	<?php // display thumb if required
					if ($display_thumb=="yes"){
						?>
                		<div class="pbytax-thumb">
                        	<?php if (get_the_post_thumbnail($post->ID)){ echo get_the_post_thumbnail( $post->ID, 'thumbnail' ); }else{ echo '<img src="'.plugins_url().'/wp-list-pages-by-custom-taxonomy/images/no-thumb.jpg" />'; } ?>                          
                        </div>	
                    <? } ?>
                        
                	<a class="pbytax-post-title" href="<?php echo get_permalink( $post->ID ); ?>" title="<?php echo $post->post_title ?>"><?php echo $post->post_title ?></a>              
                    
                    <?php // display the excerpt if required
					if ($display_excerpt=="yes"){
						?>
                		<div class="pbytax-excerpt">
                        	<?php // get the excerpt but display only first 60 characters
							$excerpt = wp_trim_excerpt( strip_tags( strip_shortcodes($post->post_content) ) ); 
							$excerpt = substr ( $excerpt, 0, 50 ); echo $excerpt.' [...]'; ?>                          
                        </div>	
                    <? } ?>
                      
                </li>
                			
				
			<?php 
			} ?>
		
		</ul>
    <?php
	}
	else {
		echo "no matches";
	}
	
	// reset the postdata
	wp_reset_postdata();
		
?>     