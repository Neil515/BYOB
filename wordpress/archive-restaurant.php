<?php get_header(); ?>
<div class="restaurant-archive-list" style="max-width:900px;margin:0 auto;">
  <h1>所有餐廳列表</h1>
  <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <div class="restaurant-card" style="border:1px solid #eee;padding:20px;margin-bottom:20px;">
      <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
      <div class="acf-fields">
        <strong>地址：</strong><?php the_field('address'); ?><br>
        <strong>餐廳聯絡電話：</strong><?php the_field('phone'); ?><br>
        <strong>餐廳類型：</strong><?php the_field('restaurant_type'); ?><br>
        <strong>是否收開瓶費：</strong><?php the_field('is_charged'); ?><br>
        <strong>開瓶費說明：</strong><?php the_field('corkage_fee'); ?><br>
        <strong>提供酒器設備：</strong><?php the_field('equipment'); ?><br>
        <strong>是否提供開酒服務？：</strong><?php the_field('open_bottle_service'); ?><br>
        <strong>官方網站/社群連結：</strong><?php the_field('social_links'); ?><br>
        <strong>Google Maps 連結：</strong><?php the_field('map_link'); ?><br>
        <strong>備註說明：</strong><?php the_field('notes'); ?><br>
        <strong>最後更新日期：</strong><?php the_field('last_updated'); ?><br>
        <strong>資料來源/提供人：</strong><?php the_field('source'); ?><br>
      </div>
    </div>
  <?php endwhile; else: ?>
    <p>目前沒有餐廳資料。</p>
  <?php endif; ?>
</div>
<?php get_footer(); ?> 