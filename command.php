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
function codeat_get_by_url(){
    if( empty( $url ) ){
        WP_CLI::error("An environment variable must be set for DEPLOY_URL.");
    }
    
    $url = parse_url( $url );
    $slug = ['path'];
    $post = get_page_by_path( $slug );
    if ( is_object( $post ) ) {
        WP_CLI::log( $post->ID . ' | ' . $post->post_type );
        return;
    }
    
    $tax = get_term_by( 'slug', $slug );
    if( is_object( $tax ) ) {
        WP_CLI::log( $tax->term_id . ' | ' . $post->taxonomy );
        return;
    }
    
    WP_CLI::log( '' );
};

WP_CLI::add_command( 'get-by-url', 'codeat_get_by_url' );
