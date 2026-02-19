<?php get_header(); ?>

<section class="hero">
    <h1>Welcome to My Custom Home Page </h1>
    <p>This is built using front-page.php</p>
</section>

<section class="latest-posts">
    <h2>Latest Posts</h2>

    <?php
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => 3
    );
    $query = new WP_Query($args);

    if($query->have_posts()):
        while($query->have_posts()): $query->the_post(); ?>
            <div class="post-item">
                <h3><?php the_title(); ?></h3>
                <p><?php the_excerpt(); ?></p>
            </div>
        <?php endwhile;
        wp_reset_postdata();
    endif;
    ?>
</section>

<?php get_footer(); ?>
