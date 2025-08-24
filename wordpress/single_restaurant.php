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
        // 處理餐廳類型，將「其他」替換為「其他: [說明文字]」
        $type_output = '';
        if (is_array($types)) {
          $processed_types = array();
          foreach ($types as $type) {
            if ($type === '其他') {
              // 獲取其他類型說明
              $other_note = get_field('restaurant_type_other_note');
              if (!empty($other_note)) {
                $processed_types[] = '其他: ' . $other_note;
              } else {
                $processed_types[] = $type;
              }
            } else {
              $processed_types[] = $type;
            }
          }
          $type_output = implode(' / ', $processed_types);
        } else {
          // 如果是字串，檢查是否包含「其他」
          if (strpos($types, '其他') !== false) {
            $other_note = get_field('restaurant_type_other_note');
            if (!empty($other_note)) {
              $type_output = str_replace('其他', '其他: ' . $other_note, $types);
            } else {
              $type_output = $types;
            }
          } else {
            $type_output = $types;
          }
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
          if ($open_bottle_service === '有') {
            $service_output = '有';
          } elseif ($open_bottle_service === '無') {
            $service_output = '無';
          } elseif ($open_bottle_service === '其他') {
            // 當選擇"其他"時，直接顯示說明文字，不顯示"其他"兩字
            if ($open_bottle_service_other_note && !empty(trim($open_bottle_service_other_note))) {
              $service_output = $open_bottle_service_other_note;
            } else {
              $service_output = '其他';
            }
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
  
  <!-- 餐廳照片區塊 -->
  <?php
  // 獲取餐廳照片
  $photo_1 = get_field('restaurant_photo_1', get_the_ID());
  $photo_2 = get_field('restaurant_photo_2', get_the_ID());
  $photo_3 = get_field('restaurant_photo_3', get_the_ID());
  
  $photos = array();
  
  // 檢查照片是否有效並添加到陣列
  if ($photo_1 && !empty($photo_1['photo'])) {
    $photos[] = $photo_1;
  }
  if ($photo_2 && !empty($photo_2['photo'])) {
    $photos[] = $photo_2;
  }
  if ($photo_3 && !empty($photo_3['photo'])) {
    $photos[] = $photo_3;
  }
  
  // 如果有照片才顯示區塊
  if (!empty($photos)): ?>
    <div class="restaurant-photos-section">
      <h3 style="color: #333; margin: 0 0 20px 0;">
        🏠 餐廳照片
      </h3>
      
      <div class="restaurant-photos-grid">
        <?php foreach ($photos as $index => $photo): 
          $photo_id = null;
          $photo_url = '';
          $photo_description = '';
          
          // 正確獲取照片說明
          if (isset($photo['description']) && !empty($photo['description'])) {
            $photo_description = $photo['description'];
          }
          
          // 獲取照片ID
          if (isset($photo['photo'])) {
            if (is_numeric($photo['photo'])) {
              $photo_id = intval($photo['photo']);
            } elseif (is_array($photo['photo']) && isset($photo['photo']['ID'])) {
              $photo_id = intval($photo['photo']['ID']);
            }
          }
          
          // 獲取照片URL - 使用縮圖尺寸
          if ($photo_id) {
            $photo_url = wp_get_attachment_image_url($photo_id, 'thumbnail');
            if (!$photo_url) {
              $photo_url = wp_get_attachment_image_url($photo_id, 'medium');
            }
            if (!$photo_url) {
              $photo_url = wp_get_attachment_url($photo_id);
            }
          }
          
          if ($photo_url): ?>
            <div class="restaurant-photo-item" data-photo-index="<?php echo $index; ?>">
              <img src="<?php echo esc_url($photo_url); ?>" 
                   alt="<?php echo esc_attr($photo_description ?: '餐廳照片'); ?>"
                   class="restaurant-photo-image"
                   loading="lazy"
                   title="<?php echo esc_attr($photo_description ?: '點擊放大查看'); ?>"
                   data-description="<?php echo esc_attr($photo_description); ?>">
            </div>
          <?php endif;
        endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
  
  <!-- 底部操作按鈕 -->
  <div class="single-page-actions">
    <div class="back-to-list">
             <?php
       // 簡化返回邏輯：總是返回餐廳列表頁
       $archive_url = get_post_type_archive_link('restaurant');
       ?>
       
       <a href="<?php echo esc_url($archive_url); ?>" class="back-link" id="back-to-list-link">
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

<!-- 照片放大覆蓋層 -->
<div id="photo-overlay" class="photo-overlay">
  <div class="photo-overlay-content">
    <img id="overlay-image" src="" alt="餐廳照片">
    <div id="overlay-description" class="overlay-description"></div>
    <button class="close-overlay" aria-label="關閉">×</button>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const photoItems = document.querySelectorAll('.restaurant-photo-item');
  const photoOverlay = document.getElementById('photo-overlay');
  const overlayImage = document.getElementById('overlay-image');
  const overlayDescription = document.getElementById('overlay-description');
  const closeOverlay = document.querySelector('.close-overlay');
  
  // 點擊照片開啟放大視窗
  photoItems.forEach(function(item) {
    item.addEventListener('click', function() {
      const photo = item.querySelector('.restaurant-photo-image');
      
      if (photo) {
        // 獲取原始大圖URL（替換縮圖URL）
        let originalUrl = photo.src;
        if (originalUrl.includes('-150x150') || originalUrl.includes('-300x300')) {
          // 如果是縮圖，嘗試獲取原始圖
          originalUrl = originalUrl.replace(/-150x150|-\d+x\d+/g, '');
        }
        
        overlayImage.src = originalUrl;
        overlayImage.alt = photo.alt;
        
        // 從data-description獲取說明文字
        const description = photo.getAttribute('data-description');
        if (description && description.trim()) {
          overlayDescription.textContent = description;
          overlayDescription.style.display = 'block';
        } else {
          overlayDescription.style.display = 'none';
        }
        
        photoOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // 防止背景滾動
      }
    });
  });
  
  // 關閉放大視窗
  function closePhotoOverlay() {
    photoOverlay.style.display = 'none';
    document.body.style.overflow = ''; // 恢復背景滾動
  }
  
  // 點擊關閉按鈕
  closeOverlay.addEventListener('click', closePhotoOverlay);
  
  // 點擊背景關閉
  photoOverlay.addEventListener('click', function(e) {
    if (e.target === photoOverlay) {
      closePhotoOverlay();
    }
  });
  
  // ESC鍵關閉
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && photoOverlay.style.display === 'flex') {
      closePhotoOverlay();
    }
  });
  
     // 觸控手勢支援（手機友善）
   let startY = 0;
   let currentY = 0;
   
   photoOverlay.addEventListener('touchstart', function(e) {
     startY = e.touches[0].clientY;
   });
   
   photoOverlay.addEventListener('touchmove', function(e) {
     currentY = e.touches[0].clientY;
   });
   
   photoOverlay.addEventListener('touchend', function() {
     const diff = startY - currentY;
     if (Math.abs(diff) > 100) { // 滑動超過100px
       closePhotoOverlay();
     }
   });
   
   // 篩選條件記憶功能
   const backToListLink = document.getElementById('back-to-list-link');
   if (backToListLink) {
     backToListLink.addEventListener('click', function(e) {
       // 檢查是否有儲存的篩選條件
       const savedFilters = sessionStorage.getItem('restaurant_filters');
       if (savedFilters) {
         try {
           const filters = JSON.parse(savedFilters);
           // 將篩選條件附加到URL參數
           const url = new URL(this.href);
           
           // 添加篩選參數
           Object.keys(filters).forEach(key => {
             if (filters[key] && filters[key] !== '') {
               url.searchParams.set(key, filters[key]);
             }
           });
           
           // 更新連結的href
           this.href = url.toString();
         } catch (error) {
           console.log('篩選條件解析失敗，使用預設返回');
         }
       }
     });
   }
 });
</script>

<?php get_footer(); ?>