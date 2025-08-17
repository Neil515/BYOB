<?php get_header(); ?>

<div class="restaurant-card" style="max-width: 800px; margin: auto; padding: 2em; font-family: sans-serif; position: relative;">
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
      <div class="restaurant-photo" style="position: absolute; top: 20px; right: 20px; width: 120px; height: 120px; overflow: hidden; border-radius: 4px; z-index: 2;">
        <img src="<?php echo esc_url($logo_url); ?>" 
             alt="<?php echo esc_attr(get_the_title()); ?> LOGO"
             class="restaurant-image"
             style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
      </div>
    <?php endif;
  endif; ?>
  
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
              <?php echo esc_html($address); ?> &#128205;
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
          <a href="tel:<?php echo esc_attr($tel_link); ?>"><?php echo esc_html($phone); ?> &#128222;</a>
        </div>
      <?php else: ?>
        <div class="field"><strong>餐廳聯絡電話：</strong>暫無資料</div>
      <?php endif; ?>

      <?php 
      $types = get_field('restaurant_type');
      if ($types): 
        // 處理複選情況
        if (is_array($types)) {
          $type_output = implode(' / ', $types);
        } else {
          $type_output = $types;
        }
      ?>
        <div class="field"><strong>餐廳類型：</strong><?php echo esc_html($type_output); ?></div>
      <?php else: ?>
        <div class="field"><strong>餐廳類型：</strong>暫無資料</div>
      <?php endif; ?>
    </div>

    <!-- 酒水相關 -->
    <div class="info-group wine-info">
      <?php 
      $is_charged = get_field('is_charged');
      // 除錯：顯示原始值和類型
      if (current_user_can('administrator')) {
        echo '<!-- DEBUG: is_charged = ' . var_export($is_charged, true) . ' -->';
      }
      
      if ($is_charged): 
        if (is_array($is_charged)) {
          $charged_output = implode(' / ', $is_charged);
        } else {
          $charged_output = $is_charged;
        }
      ?>
        <div class="field"><strong>是否收開瓶費：</strong><?php echo esc_html($charged_output); ?> &#127864;</div>
      <?php else: ?>
        <div class="field"><strong>是否收開瓶費：</strong>暫無資料</div>
      <?php endif; ?>

      <?php 
      $corkage_fee = get_field('corkage_fee');
      $corkage_fee_other = get_field('corkage_fee_other');
      
      if ($corkage_fee): 
        if ($corkage_fee === '酌收' && $corkage_fee_other) {
          $fee_output = $corkage_fee_other;
        } elseif ($corkage_fee === '其他' && $corkage_fee_other) {
          $fee_output = $corkage_fee_other;
        } else {
          $fee_output = $corkage_fee;
        }
      ?>
        <div class="field"><strong>開瓶費說明：</strong><?php echo esc_html($fee_output); ?> &#127881;</div>
      <?php else: ?>
        <div class="field"><strong>開瓶費說明：</strong>暫無資料</div>
      <?php endif; ?>

      <?php 
      $equipment = get_field('equipment');
      if ($equipment): 
        if (is_array($equipment)) {
          $equipment_output = implode(' | ', $equipment);
        } else {
          $equipment_output = $equipment;
        }
      ?>
        <div class="field"><strong>提供酒器設備：</strong><?php echo esc_html($equipment_output); ?></div>
      <?php else: ?>
        <div class="field"><strong>提供酒器設備：</strong>暫無資料</div>
      <?php endif; ?>

      <?php 
      $open_bottle_service = get_field('open_bottle_service');
      $open_bottle_service_other_note = get_field('open_bottle_service_other_note');
      
      // 除錯：顯示原始值和類型
      if (current_user_can('administrator')) {
        echo '<!-- DEBUG: open_bottle_service = ' . var_export($open_bottle_service, true) . ' -->';
        echo '<!-- DEBUG: open_bottle_service_other_note = ' . var_export($open_bottle_service_other_note, true) . ' -->';
      }

      if ($open_bottle_service): 
        if ($open_bottle_service === '是') {
          $service_output = '是';
        } elseif ($open_bottle_service === '否') {
          $service_output = '否';
        } elseif ($open_bottle_service === '其他') {
          $service_output = '其他：' . ($open_bottle_service_other_note ?: '無說明');
        } else {
          $service_output = $open_bottle_service;
        }
      ?>
        <div class="field"><strong>是否提供開酒服務：</strong><?php echo esc_html($service_output); ?></div>
      <?php else: ?>
        <div class="field"><strong>是否提供開酒服務：</strong>暫無資料</div>
      <?php endif; ?>
    </div>

	<!-- 連結資訊（合併官網與社群連結） -->
	<div class="info-group link-info">
		<?php 
		  $website = get_field('website');
		  $social_links = get_field('social_links');
		  $links = [];

		  if ($website) {
			$links[] = '<a href="'.esc_url($website).'" target="_blank" rel="noopener">官網連結</a>';
		  }
		  if ($social_links) {
			$links[] = '<a href="'.esc_url($social_links).'" target="_blank" rel="noopener">社群連結</a>';
		  }
		?>

		<?php if (!empty($links)): ?>
		  <div class="field">
			<strong>官方網站/社群連結：</strong>
			<?php echo implode(' | ', $links); ?>
		  </div>
		<?php else: ?>
		  <div class="field"><strong>官方網站/社群連結：</strong>暫無資料</div>
		<?php endif; ?>
	</div>


    <!-- 其他資訊 -->
    <div class="info-group other-info">
      <?php if(get_field('notes')): ?>
        <div class="field"><strong>備註說明：</strong><?php the_field('notes'); ?> &#128221;</div>
      <?php else: ?>
        <div class="field"><strong>備註說明：</strong>暫無資料</div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- 底部操作按鈕 -->
  <div class="single-page-actions">
    <div class="back-to-list">
      <?php
      // 智能返回邏輯
      $referer = wp_get_referer();
      $archive_url = get_post_type_archive_link('restaurant');
      $home_url = home_url();
      
      // 檢查是否有有效的來源頁面
      if ($referer && $referer !== get_permalink() && strpos($referer, $home_url) === 0) {
        // 有有效來源頁面，返回上一頁
        $back_url = $referer;
      } else {
        // 沒有有效來源頁面，返回餐廳列表總表
        $back_url = $archive_url;
      }
      ?>
      
      <a href="<?php echo esc_url($back_url); ?>" class="back-link">
        << 返回餐廳列表
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
        撥打電話 &#128222;
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php get_footer(); ?>