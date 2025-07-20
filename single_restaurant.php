<?php get_header(); ?>

<div class="restaurant-card" style="max-width: 800px; margin: auto; padding: 2em; font-family: sans-serif;">
  <h1><?php the_title(); ?></h1>

  <?php 
  /*
  <pre><?php print_r(get_fields()); ?></pre>
  */
  ?>

  <ul>
    <li><strong>地址：</strong> <?php the_field('address'); ?></li>
    <li><strong>餐廳聯絡電話：</strong> <?php the_field('phone'); ?></li>
    <li><strong>餐廳類型：</strong> <?php the_field('restaurant_type'); ?></li>
    <li><strong>是否收開瓶費：</strong> <?php the_field('is_charged'); ?></li>
    <li><strong>開瓶費說明：</strong> <?php the_field('corkage_fee'); ?></li>
    <li><strong>提供酒器設備：</strong> <?php the_field('equipment'); ?></li>
    <li><strong>是否提供開酒服務：</strong> <?php the_field('open_bottle_service'); ?></li>
    <li><strong>官方網站/社群連結：</strong>
      <?php if (get_field('social_links')): ?>
        <a href="<?php the_field('social_links'); ?>" target="_blank">點我</a>
      <?php else: ?>
        無提供
      <?php endif; ?>
    </li>
    <li><strong>Google Maps：</strong>
      <?php if (get_field('map_link')): ?>
        <a href="<?php the_field('map_link'); ?>" target="_blank">查看地圖</a>
      <?php else: ?>
        無提供
      <?php endif; ?>
    </li>
    <li><strong>備註說明：</strong> <?php the_field('notes'); ?></li>
    <li><strong>最後更新：</strong> <?php the_field('last_updated'); ?></li>
    <li><strong>資料來源：</strong> <?php the_field('source'); ?></li>
  </ul>
</div>

<?php get_footer(); ?>
