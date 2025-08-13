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

// 處理表單提交
if ($_POST['action'] === 'update_restaurant_profile') {
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

// 餐廳描述
echo '<div class="form-group" style="margin-bottom: 25px;">';
echo '<label for="restaurant_description" style="display: block; margin-bottom: 10px; font-weight: bold; color: #333; font-size: 16px;">餐廳描述</label>';
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
echo '<li>支援格式：JPG、PNG、GIF</li>';
echo '<li>檔案大小限制：2MB</li>';
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
</style>';
?>
