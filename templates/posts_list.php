<?php

use unt\objects\Post;

/**
 * @var array<Post> $posts;
 */

?>

<div class="posts">
    <?php foreach ($posts as $post) {
        \unt\design\Template::get('post')->variables([
                'post' => $post
        ])->show();
    } ?>
</div>