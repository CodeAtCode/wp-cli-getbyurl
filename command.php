<?php

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

/**
 * Get the id with post type or taxonomy by URL.
 *
 * ## OPTIONS
 *
 * <url>
 * : Number of users to generate
 *
 * ## EXAMPLES
 *
 *     wp get-by-url [your-url-with-slug]
 *
 * @accesspublic
 * @param  array $args
 * @param  array $assoc_args
 * @return
 */
function codeat_get_by_url( $args ){ 
    $url = esc_html( $args[0] );
    
    $post = url_to_post( $url );
    if ( is_object( $post ) ) {
        WP_CLI::log( 'post | ' . $post->ID . ' | ' . $post->post_type );
        return;
    }
    
    $url_path = codeat_clean_url( $url );
    $url_parameters = explode( '/', $url_path );
    $last_slug = $url_parameters[ count( $url_parameters ) -1 ];
    $term_slug = $url_parameters[ 0 ];
    
    $taxonomies = get_taxonomies( array( '_builtin' => true ), 'objects' );
    foreach ( $taxonomies as $taxonomy ) {
        if ( $taxonomy->rewrite[ 'slug' ] === $term_slug ) {
            $tax = get_term_by( 'slug', $last_slug, $taxonomy->name );
            if( is_object( $tax ) ) {
                WP_CLI::log( 'term | ' . $tax->term_id . ' | ' . $tax->taxonomy );
                return;
            }
        }
    }
};

// Based on the code of url_to_postid to get the clean url
function codeat_clean_url( $url ) {
	global $wp_rewrite;    

	$url_host      = str_replace( 'www.', '', parse_url( $url, PHP_URL_HOST ) );
	$home_url_host = str_replace( 'www.', '', parse_url( home_url(), PHP_URL_HOST ) );

	// Bail early if the URL does not belong to this site.
	if ( $url_host && $url_host !== $home_url_host ) {
		return 0;
	}

	// First, check to see if there is a 'p=N' or 'page_id=N' to match against
	if ( preg_match('#[?&](p|page_id|attachment_id)=(\d+)#', $url, $values) )	{
		$id = absint($values[2]);
		if ( $id )
			return $id;
	}

	// Get rid of the #anchor
	$url_split = explode('#', $url);
	$url = $url_split[0];

	// Get rid of URL ?query=string
	$url_split = explode('?', $url);
	$url = $url_split[0];

	// Set the correct URL scheme.
	$scheme = parse_url( home_url(), PHP_URL_SCHEME );
	$url = set_url_scheme( $url, $scheme );

	// Add 'www.' if it is absent and should be there
	if ( false !== strpos(home_url(), '://www.') && false === strpos($url, '://www.') )
		$url = str_replace('://', '://www.', $url);

	// Strip 'www.' if it is present and shouldn't be
	if ( false === strpos(home_url(), '://www.') )
		$url = str_replace('://www.', '://', $url);

	if ( trim( $url, '/' ) === home_url() && 'page' == get_option( 'show_on_front' ) ) {
		$page_on_front = get_option( 'page_on_front' );

		if ( $page_on_front && get_post( $page_on_front ) instanceof WP_Post ) {
			return (int) $page_on_front;
		}
	}

	// Check to see if we are using rewrite rules
	$rewrite = $wp_rewrite->wp_rewrite_rules();

	// Not using rewrite rules, and 'p=N' and 'page_id=N' methods failed, so we're out of options
	if ( empty($rewrite) )
		return 0;

	// Strip 'index.php/' if we're not using path info permalinks
	if ( !$wp_rewrite->using_index_permalinks() )
		$url = str_replace( $wp_rewrite->index . '/', '', $url );

	if ( false !== strpos( trailingslashit( $url ), home_url( '/' ) ) ) {
		// Chop off http://domain.com/[path]
		$url = str_replace(home_url(), '', $url);
	} else {
		// Chop off /path/to/blog
		$home_path = parse_url( home_url( '/' ) );
		$home_path = isset( $home_path['path'] ) ? $home_path['path'] : '' ;
		$url = preg_replace( sprintf( '#^%s#', preg_quote( $home_path ) ), '', trailingslashit( $url ) );
	}

	// Trim leading and lagging slashes
	$url = trim($url, '/');

	return $url;
}

// Fork of url_to_postid to get the object
function url_to_post( $url ) {
	global $wp_rewrite;

	// Check to see if we are using rewrite rules
	$rewrite = $wp_rewrite->wp_rewrite_rules();

	// Not using rewrite rules, and 'p=N' and 'page_id=N' methods failed, so we're out of options
	if ( empty($rewrite) )
		return 0;
	
	$post_type_query_vars = array();

	foreach ( get_post_types( array() , 'objects' ) as $post_type => $t ) {
		if ( ! empty( $t->query_var ) )
			$post_type_query_vars[ $t->query_var ] = $post_type;
	}

	// Look for matches.
	$url = $request = codeat_clean_url( $url );
	$request_match = $request;
	foreach ( (array)$rewrite as $match => $query) {

		// If the requesting file is the anchor of the match, prepend it
		// to the path info.
		if ( !empty($url) && ($url != $request) && (strpos($match, $url) === 0) )
			$request_match = $url . '/' . $request;

		if ( preg_match("#^$match#", $request_match, $matches) ) {

			if ( $wp_rewrite->use_verbose_page_rules && preg_match( '/pagename=\$matches\[([0-9]+)\]/', $query, $varmatch ) ) {
				// This is a verbose page match, let's check to be sure about it.
				$page = get_page_by_path( $matches[ $varmatch[1] ] );
				if ( ! $page ) {
					continue;
				}

				$post_status_obj = get_post_status_object( $page->post_status );
				if ( ! $post_status_obj->public && ! $post_status_obj->protected
					&& ! $post_status_obj->private && $post_status_obj->exclude_from_search ) {
					continue;
				}
			}

			// Got a match.
			// Trim the query of everything up to the '?'.
			$query = preg_replace("!^.+\?!", '', $query);

			// Substitute the substring matches into the query.
			$query = addslashes(WP_MatchesMapRegex::apply($query, $matches));

			// Filter out non-public query vars
			global $wp;
			parse_str( $query, $query_vars );
			$query = array();
			foreach ( (array) $query_vars as $key => $value ) {
				if ( in_array( $key, $wp->public_query_vars ) ){
					$query[$key] = $value;
					if ( isset( $post_type_query_vars[$key] ) ) {
						$query['post_type'] = $post_type_query_vars[$key];
						$query['name'] = $value;
					}
				}
			}

			// Resolve conflicts between posts with numeric slugs and date archive queries.
			$query = wp_resolve_numeric_slug_conflicts( $query );

			// Do the query
			$query = new WP_Query( $query );
			if ( ! empty( $query->posts ) && $query->is_singular )
				return $query->post;
			else
				return 0;
		}
	}
	return 0;
}

WP_CLI::add_command( 'get-by-url', 'codeat_get_by_url' );
