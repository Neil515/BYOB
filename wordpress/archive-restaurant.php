<?php get_header(); ?>
<style>
.page-header {
  margin: 2rem 0;
  padding: 1rem 0;
  text-align: left;
  border-bottom: 2px solid #D87F8D;
}

.page-title {
  margin: 0;
  padding: 0;
  font-size: 2.5rem;
  font-weight: bold;
  color: #333;
}

/* 讓標題與餐廳卡片使用相同的容器邏輯 */
.page-header {
  max-width: 1200px;
  margin: 2rem auto;
  padding: 1rem 2rem;
}
</style>
<div class="page-header">
  <h1 class="page-title">所有餐廳列表</h1>
</div>
<div class="restaurant-archive-list">
  
  <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <div class="restaurant-card">
      <!-- 加入圖片顯示 -->
      <?php 
      // 獲取兩個 LOGO 的上傳時間，選擇最新的
      $admin_logo = get_field('restaurant_photo', get_the_ID());
      $user_logo_id = get_post_meta(get_the_ID(), '_restaurant_logo', true);
      
      $logo_id = null;
      
      if ($admin_logo && is_array($admin_logo)) {
        $admin_logo_id = $admin_logo['ID'];
        $admin_time = get_post_modified_time('U', false, $admin_logo_id);
        
        if ($user_logo_id) {
          $user_time = get_post_modified_time('U', false, $user_logo_id);
          // 選擇最新的
          $logo_id = ($admin_time > $user_time) ? $admin_logo_id : $user_logo_id;
        } else {
          $logo_id = $admin_logo_id;
        }
      } else {
        $logo_id = $user_logo_id;
      }
      
      if ($logo_id): 
        // 強制讀取原始圖片，避免使用任何預處理的尺寸
        $logo_url = wp_get_attachment_url($logo_id);
        if ($logo_url): ?>
          <div class="restaurant-photo">
            <img src="<?php echo esc_url($logo_url); ?>" 
                 alt="<?php echo esc_attr(get_the_title()); ?> LOGO"
                 class="restaurant-image"
                 style="object-fit: contain; max-width: 100%; height: auto;">
          </div>
        <?php endif;
      endif; ?>
      
      <h2 class="restaurant-title-line">
        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        <?php
          $types = get_field('restaurant_type');
          if ($types):
            $type_output = is_array($types) ? implode(' / ', $types) : $types;
            echo '<span class="restaurant-type">（' . esc_html($type_output) . '）</span>';
          endif;
        ?>
      </h2>

      <div class="acf-fields">
        <div class="info-group basic-info">

		<?php
		  $address = get_field('address');
		  $map_link = get_field('map_link');

		  // fallback：若沒填 map_link，就用地址產生 Google Maps 搜尋網址
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

          <?php /*
          <?php if(get_field('restaurant_type')): ?>
            <div class="field"><strong>餐廳類型：</strong><?php the_field('restaurant_type'); ?></div>
          <?php else: ?>
            <div class="field"><strong>餐廳類型：</strong>暫無資料</div>
          <?php endif; ?>
          */ ?>
        </div>

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

          <?php /*
          <?php if(get_field('open_bottle_service')): ?>
            <div class="field"><strong>是否提供開酒服務？：</strong><?php the_field('open_bottle_service'); ?></div>
          <?php else: ?>
            <div class="field"><strong>是否提供開酒服務？：</strong>暫無資料</div>
          <?php endif; ?>
          */ ?>
        </div>

        <div class="info-group link-info">
          <?php /*
          <?php if(get_field('social_links')): ?>
            <div class="field"><strong>官方網站/社群連結：</strong><a href="<?php the_field('social_links'); ?>" target="_blank" rel="noopener"><?php the_field('social_links'); ?></a></div>
          <?php else: ?>
            <div class="field"><strong>官方網站/社群連結：</strong>暫無資料</div>
          <?php endif; ?>
          */ ?>
        </div>

        <div class="info-group other-info">
          <?php if(get_field('notes')): ?>
            <div class="field"><strong>備註說明：</strong><?php the_field('notes'); ?> 📝</div>
          <?php else: ?>
            <div class="field"><strong>備註說明：</strong>暫無資料</div>
          <?php endif; ?>
        </div>
      </div>
      
      <!-- 更多詳情按鈕 -->
      <div class="more-details-btn">
        <a href="<?php the_permalink(); ?>" class="details-link">
          更多詳情 >>
        </a>
      </div>
    </div>
  <?php endwhile; else: ?>
    <p>目前沒有餐廳資料。</p>
  <?php endif; ?>
</div>
<?php get_footer(); ?>
