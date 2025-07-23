<?php get_header(); ?>
<div class="restaurant-archive-list">
  <h1>所有餐廳列表</h1>
  <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <div class="restaurant-card">
      <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
      <div class="acf-fields">
        <div class="info-group basic-info">
          <?php if(get_field('address')): ?>
            <div class="field"><strong>地址：</strong><?php the_field('address'); ?></div>
          <?php else: ?>
            <div class="field"><strong>地址：</strong>暫無資料</div>
          <?php endif; ?>
          <?php if(get_field('phone')): ?>
            <div class="field"><strong>餐廳聯絡電話：</strong><?php the_field('phone'); ?></div>
          <?php else: ?>
            <div class="field"><strong>餐廳聯絡電話：</strong>暫無資料</div>
          <?php endif; ?>
          <?php if(get_field('restaurant_type')): ?>
            <div class="field"><strong>餐廳類型：</strong><?php the_field('restaurant_type'); ?></div>
          <?php else: ?>
            <div class="field"><strong>餐廳類型：</strong>暫無資料</div>
          <?php endif; ?>
        </div>
        <div class="info-group wine-info">
          <?php if(get_field('is_charged')): ?>
            <div class="field"><strong>是否收開瓶費：</strong><?php the_field('is_charged'); ?></div>
          <?php else: ?>
            <div class="field"><strong>是否收開瓶費：</strong>暫無資料</div>
          <?php endif; ?>
          <?php if(get_field('corkage_fee')): ?>
            <div class="field"><strong>開瓶費說明：</strong><?php the_field('corkage_fee'); ?></div>
          <?php else: ?>
            <div class="field"><strong>開瓶費說明：</strong>暫無資料</div>
          <?php endif; ?>
          <?php if(get_field('equipment')): ?>
            <div class="field"><strong>提供酒器設備：</strong><?php the_field('equipment'); ?></div>
          <?php else: ?>
            <div class="field"><strong>提供酒器設備：</strong>暫無資料</div>
          <?php endif; ?>
          <?php if(get_field('open_bottle_service')): ?>
            <div class="field"><strong>是否提供開酒服務？：</strong><?php the_field('open_bottle_service'); ?></div>
          <?php else: ?>
            <div class="field"><strong>是否提供開酒服務？：</strong>暫無資料</div>
          <?php endif; ?>
        </div>
        <div class="info-group link-info">
          <?php if(get_field('social_links')): ?>
            <div class="field"><strong>官方網站/社群連結：</strong><a href="<?php the_field('social_links'); ?>" target="_blank" rel="noopener"><?php the_field('social_links'); ?></a></div>
          <?php else: ?>
            <div class="field"><strong>官方網站/社群連結：</strong>暫無資料</div>
          <?php endif; ?>
          <?php if(get_field('map_link')): ?>
            <div class="field"><strong>Google Maps 連結：</strong><a href="<?php the_field('map_link'); ?>" target="_blank" rel="noopener">地圖</a></div>
          <?php else: ?>
            <div class="field"><strong>Google Maps 連結：</strong>暫無資料</div>
          <?php endif; ?>
        </div>
        <div class="info-group other-info">
          <?php if(get_field('notes')): ?>
            <div class="field"><strong>備註說明：</strong><?php the_field('notes'); ?></div>
          <?php else: ?>
            <div class="field"><strong>備註說明：</strong>暫無資料</div>
          <?php endif; ?>
          <?php if(get_field('last_updated')): ?>
            <div class="field"><strong>最後更新日期：</strong><?php the_field('last_updated'); ?></div>
          <?php else: ?>
            <div class="field"><strong>最後更新日期：</strong>暫無資料</div>
          <?php endif; ?>
          <?php if(get_field('source')): ?>
            <div class="field"><strong>資料來源/提供人：</strong><?php the_field('source'); ?></div>
          <?php else: ?>
            <div class="field"><strong>資料來源/提供人：</strong>暫無資料</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endwhile; else: ?>
    <p>目前沒有餐廳資料。</p>
  <?php endif; ?>
</div>
<?php get_footer(); ?> 