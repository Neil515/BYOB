<?php get_header(); ?>

<div class="restaurant-card" style="max-width: 800px; margin: auto; padding: 2em; font-family: sans-serif;">
  <!-- 加入圖片顯示 -->
  <?php 
  $restaurant_photo = get_field('restaurant_photo');
  if ($restaurant_photo): ?>
    <div class="restaurant-photo">
      <img src="<?php echo esc_url($restaurant_photo['url']); ?>" 
           alt="<?php echo esc_attr($restaurant_photo['alt']); ?>"
           class="restaurant-image">
    </div>
  <?php endif; ?>
  
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
              <?php echo esc_html($address); ?> 📍
            </a>
          <?php else: ?>
            <?php echo esc_html($address); ?>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="field"><strong>地址：</strong>暫無資料</div>
      <?php endif; ?>

      <?php 
      $phone = get_field('phone');
      if ($phone): 
        // 檢查是否包含連字號，如果沒有則加入
        $tel_link = $phone;
        if (strpos($phone, '-') === false) {
          // 根據長度自動加入連字號
          $clean_phone = preg_replace('/[^0-9]/', '', $phone);
          if (strlen($clean_phone) == 8) {
            // 市話格式：02-12345678
            $tel_link = substr($clean_phone, 0, 2) . '-' . substr($clean_phone, 2);
          } elseif (strlen($clean_phone) == 10 && substr($clean_phone, 0, 2) == '09') {
            // 手機格式：0932-123456
            $tel_link = substr($clean_phone, 0, 4) . '-' . substr($clean_phone, 4);
          }
        }
      ?>
        <div class="field">
          <strong>餐廳聯絡電話：</strong>
          <a href="tel:<?php echo esc_attr($tel_link); ?>"><?php echo esc_html($phone); ?> 📞</a>
        </div>
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
        <div class="field"><strong>是否收開瓶費：</strong><?php the_field('is_charged'); ?> 🥂</div>
      <?php else: ?>
        <div class="field"><strong>是否收開瓶費：</strong>暫無資料</div>
      <?php endif; ?>

      <?php if(get_field('corkage_fee')): ?>
        <div class="field"><strong>開瓶費說明：</strong><?php the_field('corkage_fee'); ?> 🪙</div>
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
        <div class="field"><strong>備註說明：</strong><?php the_field('notes'); ?> 📝</div>
      <?php else: ?>
        <div class="field"><strong>備註說明：</strong>暫無資料</div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- 底部操作按鈕 -->
  <div class="single-page-actions">
    <div class="back-to-list">
      <a href="javascript:history.back()" class="back-link">
        << 返回上一頁
      </a>
    </div>
    
    <div class="contact-restaurant">
      <?php 
      $phone = get_field('phone');
      if ($phone): 
        // 檢查是否包含連字號，如果沒有則加入
        $tel_link = $phone;
        if (strpos($phone, '-') === false) {
          // 根據長度自動加入連字號
          $clean_phone = preg_replace('/[^0-9]/', '', $phone);
          if (strlen($clean_phone) == 8) {
            // 市話格式：02-12345678
            $tel_link = substr($clean_phone, 0, 2) . '-' . substr($clean_phone, 2);
          } elseif (strlen($clean_phone) == 10 && substr($clean_phone, 0, 2) == '09') {
            // 手機格式：0932-123456
            $tel_link = substr($clean_phone, 0, 4) . '-' . substr($clean_phone, 4);
          }
        }
      ?>
        <a href="tel:<?php echo esc_attr($tel_link); ?>" class="contact-link">
        撥打電話 📞
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php get_footer(); ?>
