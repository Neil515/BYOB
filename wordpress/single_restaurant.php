<?php get_header(); ?>

<div class="restaurant-card" style="max-width: 800px; margin: auto; padding: 2em; font-family: sans-serif;">
  <h1><?php the_title(); ?></h1>

  <div class="acf-fields">
    <!-- 基本資料 -->
    <div class="info-group basic-info">
      <?php
        $address = get_field('address');
        $map_link = get_field('map_link');

        if (!$map_link && $address) {
          $map_link = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);
        }
      ?>

      <?php if($address): ?>
        <div class="field">
          <strong>地址：</strong>
          <?php if($map_link): ?>
            <a href="<?php echo esc_url($map_link); ?>" target="_blank" rel="noopener">
              <?php echo esc_html($address); ?> 🌐
            </a>
          <?php else: ?>
            <?php echo esc_html($address); ?>
          <?php endif; ?>
        </div>
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

    <!-- 酒水相關 -->
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
        <div class="field"><strong>是否提供開酒服務：</strong><?php the_field('open_bottle_service'); ?></div>
      <?php else: ?>
        <div class="field"><strong>是否提供開酒服務：</strong>暫無資料</div>
      <?php endif; ?>
    </div>

    <!-- 連結資訊 -->
    <div class="info-group link-info">
      <?php if(get_field('social_links')): ?>
        <div class="field"><strong>官方網站/社群連結：</strong>
          <a href="<?php the_field('social_links'); ?>" target="_blank" rel="noopener">
            <?php the_field('social_links'); ?>
          </a>
        </div>
      <?php else: ?>
        <div class="field"><strong>官方網站/社群連結：</strong>暫無資料</div>
      <?php endif; ?>
    </div>

    <!-- 其他資訊 -->
    <div class="info-group other-info">
      <?php if(get_field('notes')): ?>
        <div class="field"><strong>備註說明：</strong><?php the_field('notes'); ?></div>
      <?php else: ?>
        <div class="field"><strong>備註說明：</strong>暫無資料</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php get_footer(); ?>
