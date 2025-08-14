<?php

/**
 * 餐廳資料編輯頁面模板
 * 
 * 這個檔案會顯示餐廳業者的資料編輯表單
 * 包含基本資料編輯和 LOGO 上傳功能
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    exit;
}

// 檢查使用者是否為餐廳業者
$user_id = get_current_user_id();
if (!$user_id) {
    echo '<div style="text-align: center; padding: 50px;">';
    echo '<h2>請先登入</h2>';
    echo '<p>您需要登入才能編輯餐廳資料。</p>';
    echo '<a href="' . wp_login_url(get_permalink()) . '" class="button">登入</a>';
    echo '</div>';
    return;
}

$user = get_user_by('id', $user_id);
if (!in_array('restaurant_owner', $user->roles)) {
    echo '<div style="text-align: center; padding: 50px;">';
    echo '<h2>權限不足</h2>';
    echo '<p>只有餐廳業者才能存取此頁面。</p>';
    echo '</div>';
    return;
}

// 獲取使用者擁有的餐廳
$user_restaurants = byob_get_user_restaurants($user_id);
if (empty($user_restaurants)) {
    echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 30px; border-radius: 8px; text-align: center;">';
    echo '<h3>⚠️ 注意</h3>';
    echo '<p>您目前沒有關聯的餐廳。</p>';
    echo '<p>這可能是因為：</p>';
    echo '<ul style="text-align: left; display: inline-block; margin: 20px 0;">';
    echo '<li>餐廳資料尚未建立</li>';
    echo '<li>餐廳與您的帳號尚未關聯</li>';
    echo '<li>餐廳狀態不是「已上架」</li>';
    echo '</ul>';
    echo '<p>請聯絡管理員協助處理。</p>';
    echo '</div>';
    return;
}

$restaurant = $user_restaurants[0]; // 取第一個餐廳
$restaurant_id = $restaurant->ID;

// 獲取當前餐廳資料
$current_logo_id = get_post_meta($restaurant_id, '_restaurant_logo', true);
$current_logo_url = $current_logo_id ? wp_get_attachment_image_url($current_logo_id, 'thumbnail') : '';

// ACF 欄位資料載入除錯（僅在開發環境顯示）
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; border-radius: 8px; margin-bottom: 20px; font-family: monospace; font-size: 12px;">';
    echo '<h4 style="margin: 0 0 15px 0; color: #495057;">🔍 ACF 欄位資料載入除錯資訊</h4>';
    
    // 檢查 ACF 外掛是否啟用
    if (function_exists('get_field')) {
        echo '<p style="color: #28a745; margin: 5px 0;">✅ ACF 外掛已啟用</p>';
        
        // 檢查各個 ACF 欄位的資料
        $debug_fields = array(
            'restaurant_type' => '餐廳類型',
            'is_charged' => '是否收開瓶費',
            'corkage_fee' => '開瓶費說明',
            'equipment' => '酒器設備',
            'open_bottle_service' => '開酒服務',
            'open_bottle_service_other_note' => '開酒服務其他說明',
            'website' => '官方網站',
            'social_links' => '社群連結',
            'phone' => '聯絡電話',
            'address' => '地址',
            'business_hours' => '營業時間'
        );
        
        foreach ($debug_fields as $field_name => $field_label) {
            $field_value = get_field($field_name, $restaurant_id);
            if ($field_value !== false && $field_value !== null && $field_value !== '') {
                if (is_array($field_value)) {
                    echo '<p style="color: #28a745; margin: 5px 0;">✅ ' . $field_label . ': ' . implode(', ', $field_value) . '</p>';
                } else {
                    echo '<p style="color: #28a745; margin: 5px 0;">✅ ' . $field_label . ': ' . esc_html($field_value) . '</p>';
                }
            } else {
                echo '<p style="color: #dc3545; margin: 5px 0;">❌ ' . $field_label . ': 無資料或欄位不存在</p>';
            }
        }
    } else {
        echo '<p style="color: #dc3545; margin: 5px 0;">❌ ACF 外掛未啟用</p>';
    }
    
    echo '<p style="color: #6c757d; margin: 5px 0;">餐廳 ID: ' . $restaurant_id . '</p>';
    echo '<p style="color: #6c757d; margin: 5px 0;">餐廳標題: ' . esc_html($restaurant->post_title) . '</p>';
    echo '</div>';
}

// 處理表單提交
if (isset($_POST['action']) && $_POST['action'] === 'update_restaurant_profile') {
    byob_handle_restaurant_profile_submit($restaurant_id);
}

// 顯示成功/失敗訊息
if (isset($_GET['message'])) {
    $message = sanitize_text_field($_GET['message']);
    if ($message === 'success') {
        echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: center;">';
        echo '<h3 style="margin: 0 0 10px 0;">✅ 更新成功！</h3>';
        echo '<p style="margin: 0;">餐廳資料已成功更新。</p>';
        echo '</div>';
    } elseif ($message === 'error') {
        echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: center;">';
        echo '<h3 style="margin: 0 0 10px 0;">❌ 更新失敗</h3>';
        echo '<p style="margin: 0;">請檢查輸入資料是否正確。</p>';
        echo '</div>';
    } elseif ($message === 'partial_success') {
        echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: center;">';
        echo '<h3 style="margin: 0 0 10px 0;">⚠️ 部分更新成功</h3>';
        echo '<p style="margin: 0;">基本資料已更新，但 LOGO 上傳失敗。</p>';
        echo '</div>';
    }
}

// 頁面標題和說明
echo '<div class="restaurant-profile-header" style="text-align: center; margin-bottom: 30px;">';
echo '<h1 style="color: #333; margin-bottom: 10px;">餐廳資料編輯</h1>';
echo '<p style="color: #666; font-size: 16px;">編輯您的餐廳基本資料和 LOGO</p>';
echo '</div>';

// 主要表單
echo '<div class="restaurant-profile-form" style="max-width: 800px; margin: 0 auto;">';
echo '<form method="post" enctype="multipart/form-data" style="background: #f9f9f9; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">';
echo '<input type="hidden" name="action" value="update_restaurant_profile">';
echo '<input type="hidden" name="restaurant_id" value="' . esc_attr($restaurant_id) . '">';

// 餐廳基本資料區塊
echo '<div class="form-section" style="margin-bottom: 35px;">';
echo '<h3 style="color: #333; border-bottom: 3px solid rgba(139, 38, 53, 0.8); padding-bottom: 15px; margin-bottom: 25px;">基本資料</h3>';

// 餐廳名稱
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label for="restaurant_name" style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">餐廳名稱 *</label>';
echo '<input type="text" id="restaurant_name" name="restaurant_name" value="' . esc_attr($restaurant->post_title) . '" required style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;">';
echo '<p style="font-size: 14px; color: #666; margin-top: 5px;">餐廳名稱是必填欄位</p>';
echo '</div>';

// 餐廳類型
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">餐廳類型 <span style="color: #dc3545;">(最多選擇3個)</span></label>';
echo '<div class="checkbox-group" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">';

$restaurant_types = array(
    '台式' => '台式',
    '法式' => '法式',
    '義式' => '義式',
    '日式' => '日式',
    '美式' => '美式',
    '熱炒' => '熱炒',
    '小酒館' => '小酒館',
    '咖啡廳' => '咖啡廳',
    '私廚' => '私廚',
    '異國料理' => '異國料理',
    '燒烤' => '燒烤',
    '火鍋' => '火鍋',
    '牛排' => '牛排',
    'Lounge Bar' => 'Lounge Bar',
    'Buffet' => 'Buffet'
);

$current_types = get_field('restaurant_type', $restaurant_id);
$current_types = is_array($current_types) ? $current_types : array();

foreach ($restaurant_types as $value => $label) {
    $checked = in_array($value, $current_types) ? 'checked' : '';
    echo '<label style="display: flex; align-items: center; cursor: pointer; font-weight: normal; padding: 10px; border: 1px solid #ddd; border-radius: 6px; transition: all 0.3s;">';
    echo '<input type="checkbox" name="restaurant_type[]" value="' . $value . '" ' . $checked . ' style="margin-right: 8px;" onchange="limitCheckboxes(this, 3, \'restaurant_type\')">';
    echo $label;
    echo '</label>';
}

echo '</div>';
echo '<p style="font-size: 14px; color: #666; margin-top: 5px;">請選擇您的餐廳類型（最多3個）</p>';
echo '</div>';

// 其他BYOB規定或備註
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label for="restaurant_description" style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">其他BYOB規定或備註</label>';
echo '<textarea id="restaurant_description" name="restaurant_description" rows="5" placeholder="請描述您的餐廳特色、風格、服務等..." style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; resize: vertical; transition: border-color 0.3s;">' . esc_textarea($restaurant->post_content) . '</textarea>';
echo '<p style="font-size: 14px; color: #666; margin-top: 5px;">讓顧客更了解您的餐廳</p>';
echo '</div>';

// 聯絡電話
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label for="restaurant_phone" style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">聯絡電話</label>';
echo '<input type="tel" id="restaurant_phone" name="restaurant_phone" value="' . esc_attr(get_field('phone', $restaurant_id)) . '" placeholder="例：02-1234-5678" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;">';
echo '<p style="font-size: 14px; color: #666; margin-top: 5px;">顧客可以透過此電話聯絡您</p>';
echo '</div>';

// 地址
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label for="restaurant_address" style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">地址</label>';
echo '<textarea id="restaurant_address" name="restaurant_address" rows="3" placeholder="請輸入完整地址..." style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; resize: vertical; transition: border-color 0.3s;">' . esc_textarea(get_field('address', $restaurant_id)) . '</textarea>';
echo '<p style="font-size: 14px; color: #666; margin-top: 5px;">詳細地址有助於顧客找到您的餐廳</p>';
echo '</div>';

// 營業時間
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label for="business_hours" style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">營業時間</label>';
echo '<textarea id="business_hours" name="business_hours" rows="3" placeholder="例：週一至週五 11:00-22:00，週六日 10:00-23:00" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; resize: vertical; transition: border-color 0.3s;">' . esc_textarea(get_field('business_hours', $restaurant_id)) . '</textarea>';
echo '<p style="font-size: 14px; color: #666; margin-top: 5px;">清楚標示營業時間，避免顧客白跑一趟</p>';
echo '</div>';

// 是否收開瓶費
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label for="is_charged" style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">是否收開瓶費</label>';
echo '<div class="radio-group" style="display: flex; gap: 20px; align-items: center;">';
echo '<label style="display: flex; align-items: center; cursor: pointer; font-weight: normal;">';
echo '<input type="radio" name="is_charged" value="yes" ' . (get_field('is_charged', $restaurant_id) === 'yes' ? 'checked' : '') . ' style="margin-right: 8px;">';
echo '酌收';
echo '</label>';
echo '<label style="display: flex; align-items: center; cursor: pointer; font-weight: normal;">';
echo '<input type="radio" name="is_charged" value="no" ' . (get_field('is_charged', $restaurant_id) === 'no' ? 'checked' : '') . ' style="margin-right: 8px;">';
echo '不收費';
echo '</label>';
echo '<label style="display: flex; align-items: center; cursor: pointer; font-weight: normal;">';
echo '<input type="radio" name="is_charged" value="other" ' . (get_field('is_charged', $restaurant_id) === 'other' ? 'checked' : '') . ' style="margin-right: 8px;">';
echo '其他';
echo '</label>';
echo '</div>';
echo '<p style="font-size: 14px; color: #666; margin-top: 5px;">請選擇您的開瓶費政策</p>';
echo '</div>';

// 開瓶費說明
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label for="corkage_fee" style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">開瓶費說明</label>';
echo '<input type="text" id="corkage_fee" name="corkage_fee" value="' . esc_attr(get_field('corkage_fee', $restaurant_id)) . '" placeholder="例：每瓶酌收100元，或依酒款而定" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;">';
echo '<p style="font-size: 14px; color: #666; margin-top: 5px;">請詳細說明您的開瓶費政策</p>';
echo '</div>';

// 酒器設備
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">酒器設備</label>';
echo '<div class="checkbox-group" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">';

$equipment_options = array(
    '酒杯' => '酒杯',
    '開瓶器' => '開瓶器',
    '冰桶' => '冰桶',
    '醒酒器' => '醒酒器',
    '酒塞/瓶塞' => '酒塞/瓶塞',
    '酒架/酒櫃' => '酒架/酒櫃',
    '溫度計' => '溫度計',
    '濾酒器' => '濾酒器',
    '其他' => '其他',
    '無提供' => '無提供'
);

$current_equipment = get_field('equipment', $restaurant_id);
$current_equipment = is_array($current_equipment) ? $current_equipment : array();

foreach ($equipment_options as $value => $label) {
    $checked = in_array($value, $current_equipment) ? 'checked' : '';
    echo '<label style="display: flex; align-items: center; cursor: pointer; font-weight: normal; padding: 10px; border: 1px solid #ddd; border-radius: 6px; transition: all 0.3s;">';
    echo '<input type="checkbox" name="equipment[]" value="' . $value . '" ' . $checked . ' style="margin-right: 8px;">';
    echo $label;
    echo '</label>';
}

echo '</div>';
echo '<p style="font-size: 14px; color: #666; margin-top: 5px;">請選擇您提供的酒器設備</p>';
echo '</div>';

// 開酒服務
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label for="open_bottle_service" style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">開酒服務</label>';

// 除錯：顯示 ACF 欄位的實際值
$open_bottle_service_value = get_field('open_bottle_service', $restaurant_id);
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo '<p style="font-size: 12px; color: #666; margin-bottom: 5px;">🔍 除錯：ACF 欄位值 = "' . esc_html($open_bottle_service_value) . '"</p>';
}

echo '<select id="open_bottle_service" name="open_bottle_service" onchange="toggleOtherNote()" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; min-width: 200px; text-overflow: clip; white-space: nowrap;">';
echo '<option value="">請選擇</option>';
echo '<option value="yes" ' . ($open_bottle_service_value === 'yes' ? 'selected' : '') . '>是</option>';
echo '<option value="no" ' . ($open_bottle_service_value === 'no' ? 'selected' : '') . '>否</option>';
echo '<option value="other" ' . ($open_bottle_service_value === 'other' ? 'selected' : '') . '>其他</option>';
echo '</select>';
echo '<p style="font-size: 14px; color: #666; margin-top: 5px;">請選擇是否提供開酒服務</p>';
echo '</div>';

// 開酒服務說明文字（是/否選項）
echo '<div id="service_status_text" class="form-group" style="margin-bottom: 25px; display: ' . (in_array($open_bottle_service_value, array('yes', 'no')) ? 'block' : 'none') . ';">';
echo '<div style="background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; text-align: center;">';
if ($open_bottle_service_value === 'yes') {
    echo '<p style="margin: 0; color: #155724; font-weight: bold; font-size: 16px;">✅ 提供開酒服務</p>';
} elseif ($open_bottle_service_value === 'no') {
    echo '<p style="margin: 0; color: #721c24; font-weight: bold; font-size: 16px;">❌ 未提供開酒服務</p>';
}
echo '</div>';
echo '</div>';

// 開酒服務其他說明
echo '<div id="other_note_field" class="form-group" style="margin-bottom: 25px; display: ' . ($open_bottle_service_value === 'other' ? 'block' : 'none') . ';">';
echo '<label for="open_bottle_service_other_note" style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">其他說明</label>';
echo '<input type="text" id="open_bottle_service_other_note" name="open_bottle_service_other_note" value="' . esc_attr(get_field('open_bottle_service_other_note', $restaurant_id)) . '" placeholder="請說明您提供的開酒服務內容..." style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s;">';
echo '<p style="font-size: 14px; color: #666; margin-top: 5px;">請詳細說明您提供的開酒服務內容（選填）</p>';
echo '</div>';

// 官方網站/社群連結
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">官方網站/社群連結</label>';
echo '<div style="display: flex; gap: 15px;">';
echo '<div style="flex: 1;">';
echo '<label for="website" style="display: block; margin-bottom: 8px; font-weight: normal; color: #666; font-size: 14px;">官方網站</label>';
echo '<input type="url" id="website" name="website" value="' . esc_attr(get_field('website', $restaurant_id)) . '" placeholder="例：https://www.example.com" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px; transition: border-color 0.3s;">';
echo '</div>';
echo '<div style="flex: 1;">';
echo '<label for="social_links" style="display: block; margin-bottom: 8px; font-weight: normal; color: #666; font-size: 14px;">社群連結</label>';
echo '<input type="url" id="social_links" name="social_links" value="' . esc_attr(get_field('social_links', $restaurant_id)) . '" placeholder="例：Facebook、Instagram 等" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px; transition: border-color 0.3s;">';
echo '</div>';
echo '</div>';
echo '<p style="font-size: 14px; color: #666; margin-top: 5px;">請輸入您的官方網站和社群媒體連結（選填）</p>';
echo '</div>';

echo '</div>';

// LOGO 上傳區塊
echo '<div class="form-section" style="margin-bottom: 35px;">';
echo '<h3 style="color: #333; border-bottom: 3px solid rgba(139, 38, 53, 0.8); padding-bottom: 15px; margin-bottom: 25px;">餐廳 LOGO</h3>';

// 顯示當前 LOGO
if ($current_logo_url) {
    echo '<div class="current-logo" style="margin-bottom: 25px; text-align: center;">';
    echo '<p style="font-weight: bold; margin-bottom: 15px; color: #333;">當前 LOGO：</p>';
    echo '<img src="' . esc_url($current_logo_url) . '" alt="當前 LOGO" style="max-width: 200px; max-height: 200px; border: 3px solid #ddd; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">';
    echo '</div>';
} else {
    echo '<div class="no-logo" style="margin-bottom: 25px; text-align: center; padding: 30px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 10px;">';
    echo '<p style="color: #6c757d; margin: 0;">目前沒有設定 LOGO</p>';
    echo '</div>';
}

// LOGO 上傳欄位
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label for="restaurant_logo" style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">上傳新 LOGO</label>';
echo '<input type="file" id="restaurant_logo" name="restaurant_logo" accept="image/jpeg,image/png,image/gif" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; background: white; transition: border-color 0.3s;">';
echo '<div style="margin-top: 10px; padding: 15px; background: #e9ecef; border-radius: 8px;">';
echo '<p style="font-size: 14px; color: #495057; margin: 0 0 8px 0;"><strong>📋 上傳須知：</strong></p>';
echo '<ul style="font-size: 14px; color: #495057; margin: 0; padding-left: 20px;">';
echo '<li>支援格式：JPG、PNG 及其他常見圖片格式</li>';
echo '<li>檔案大小限制：1MB</li>';
echo '<li>建議尺寸：300x300 像素以上</li>';
echo '<li>上傳後會自動替換現有 LOGO</li>';
echo '</ul>';
echo '</div>';
echo '</div>';

echo '</div>';

// 提交按鈕
echo '<div class="form-submit" style="text-align: center; padding-top: 20px; border-top: 2px solid #e9ecef;">';
echo '<button type="submit" style="background-color: rgba(139, 38, 53, 0.8); color: white; padding: 18px 40px; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; font-weight: bold; transition: all 0.3s; box-shadow: 0 4px 8px rgba(139, 38, 53, 0.3);">💾 更新餐廳資料</button>';
echo '</div>';

echo '</form>';
echo '</div>';

// 添加一些 CSS 樣式來改善表單互動
echo '<style>
.form-group input:focus,
.form-group textarea:focus {
    border-color: rgba(139, 38, 53, 0.8) !important;
    outline: none;
    box-shadow: 0 0 0 3px rgba(139, 38, 53, 0.1);
}

.form-submit button:hover {
    background-color: rgba(139, 38, 53, 1) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(139, 38, 53, 0.4);
}

.form-submit button:active {
    transform: translateY(0);
}

.checkbox-group label:hover {
    border-color: rgba(139, 38, 53, 0.5);
    background-color: rgba(139, 38, 53, 0.05);
}

.checkbox-group input[type="checkbox"]:checked + span {
    color: rgba(139, 38, 53, 0.8);
    font-weight: bold;
}
</style>';

// 添加 JavaScript 來限制餐廳類型最多只能選3個
echo '<script>
function limitCheckboxes(checkbox, maxCount, groupName) {
    var checkboxes = document.querySelectorAll(\'input[name="\' + groupName + \'[]"]\');
    var checkedCount = 0;
    
    // 計算已選中的數量
    checkboxes.forEach(function(cb) {
        if (cb.checked) {
            checkedCount++;
        }
    });
    
    // 如果超過限制，取消選中
    if (checkedCount > maxCount) {
        checkbox.checked = false;
        alert("餐廳類型最多只能選擇 " + maxCount + " 個選項");
        return false;
    }
    
    return true;
}

// 控制開酒服務欄位的顯示邏輯
function toggleOtherNote() {
    var openBottleService = document.getElementById(\'open_bottle_service\');
    var otherNoteField = document.getElementById(\'other_note_field\');
    var serviceStatusText = document.getElementById(\'service_status_text\');
    
    // 隱藏所有相關欄位
    otherNoteField.style.display = \'none\';
    serviceStatusText.style.display = \'none\';
    
    // 根據選擇顯示對應的欄位
    if (openBottleService.value === \'yes\') {
        serviceStatusText.style.display = \'block\';
        // 更新說明文字
        serviceStatusText.innerHTML = \'<div style="background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; text-align: center;"><p style="margin: 0; color: #155724; font-weight: bold; font-size: 16px;">✅ 提供開酒服務</p></div>\';
    } else if (openBottleService.value === \'no\') {
        serviceStatusText.style.display = \'block\';
        // 更新說明文字
        serviceStatusText.innerHTML = \'<div style="background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; text-align: center;"><p style="margin: 0; color: #721c24; font-weight: bold; font-size: 16px;">❌ 未提供開酒服務</p></div>\';
    } else if (openBottleService.value === \'other\') {
        otherNoteField.style.display = \'block\';
    }
    
    // 如果不是選擇「其他」，清空其他說明欄位的值
    if (openBottleService.value !== \'other\') {
        document.getElementById(\'open_bottle_service_other_note\').value = \'\';
    }
}

// 頁面載入完成後初始化開酒服務欄位的顯示狀態
document.addEventListener(\'DOMContentLoaded\', function() {
    toggleOtherNote();
});
</script>';
?>