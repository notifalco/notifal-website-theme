<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Page.php
 * @version 1.0.0
 */

get_header();
?>
<div class="site-content" role="main">
    <?php
if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();
        the_content();
    endwhile;
endif;
?>
</div>
<?php
get_footer();
?>
