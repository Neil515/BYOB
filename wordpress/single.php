<?php
/**
 * 子主題用 single.php
 * 當文章類型為 restaurant 時，導向自訂卡片模板
 */

if (get_post_type() === 'restaurant') {
    // 安全地載入 single_restaurant.php 並立即結束
    get_template_part('single_restaurant');
    return;
}

// 否則執行 Flatsome 原始單一文章顯示
get_header();
do_action('flatsome_before_blog');
?>

<div class="container" id="content">
  <div class="row">
    <div class="large-12 col">
      <?php do_action('flatsome_before_blog_post'); ?>
      <?php get_template_part('template-parts/posts/single'); ?>
      <?php do_action('flatsome_after_blog_post'); ?>
    </div>
  </div>
</div>

<?php
do_action('flatsome_after_blog');
get_footer();
?>
