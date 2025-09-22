<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Single.php
 * @version 1.0.0
 */

get_header();

if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();
        the_content();
    endwhile;
endif;

get_footer();
