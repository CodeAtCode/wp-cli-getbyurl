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
    $url = parse_url( esc_html( $args[0] ) );
    $slug = $url[ 'path' ];
    $last_slug = array_filter( explode( '/', $slug ), 'strlen' );
    $last_slug = $last_slug[ count( $last_slug ) ];
    
    $post = get_posts( array(
            'name' => $last_slug,
            'posts_per_page' => 1,
    ));
    $post = $post[ 0 ];
    if ( is_object( $post ) ) {
        WP_CLI::log( 'post | ' . $post->ID . ' | ' . $post->post_type );
        return;
    }
    
    $taxonomies = get_taxonomies();
    foreach ( $taxonomies as $tax_type_key => $taxonomy ) {
        $tax = get_term_by( 'slug', $last_slug, $taxonomy );
        if( is_object( $tax ) ) {
            WP_CLI::log( 'term | ' . $tax->term_id . ' | ' . $tax->taxonomy );
            return;
        }
    }
};

WP_CLI::add_command( 'get-by-url', 'codeat_get_by_url' );
