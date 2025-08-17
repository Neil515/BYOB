<?php
/**
 * 餐廳照片管理頁面
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    exit;
}

// 檢查用戶權限
$user_id = get_current_user_id();
if (!$user_id) {
    echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; text-align: center;">';
    echo '<h3>❌ 請先登入</h3>';
    echo '<p>您需要登入才能管理餐廳照片。</p>';
    echo '</div>';
    return;
}

// 檢查用戶角色
$user = get_user_by('id', $user_id);
if (!in_array('restaurant_owner', $user->roles)) {
    echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; text-align: center;">';
    echo '<h3>❌ 權限不足</h3>';
    echo '<p>只有餐廳業者才能存取此頁面。</p>';
    echo '</div>';
    return;
}

// 獲取用戶的餐廳
$user_restaurants = byob_get_user_restaurants($user_id);
if (empty($user_restaurants)) {
    echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 20px; border-radius: 8px; text-align: center;">';
    echo '<h3>⚠️ 注意</h3>';
    echo '<p>您目前沒有關聯的餐廳。請聯絡管理員。</p>';
    echo '</div>';
    return;
}

$restaurant_id = $user_restaurants[0]->ID;

// 處理照片上傳
if ($_POST['action'] === 'upload_photos') {
    $result = byob_handle_photo_upload($restaurant_id, $_FILES['photos']);
    if (is_wp_error($result)) {
        echo '<div class="error">' . $result->get_error_message() . '</div>';
    } else {
        echo '<div class="success">照片上傳成功！</div>';
    }
}

// 處理照片刪除
if ($_POST['action'] === 'delete_photo') {
    $photo_id = intval($_POST['photo_id']);
    $result = byob_delete_restaurant_photo($restaurant_id, $photo_id);
    if (is_wp_error($result)) {
        echo '<div class="error">' . $result->get_error_message() . '</div>';
    } else {
        echo '<div class="success">照片刪除成功！</div>';
    }
}

// 獲取現有照片（讀取三個群組欄位）
$photo_1 = get_field('restaurant_photo_1', $restaurant_id);
$photo_2 = get_field('restaurant_photo_2', $restaurant_id);
$photo_3 = get_field('restaurant_photo_3', $restaurant_id);

$existing_photos = array();
if ($photo_1 && !empty($photo_1['photo'])) {
    $existing_photos[] = $photo_1;
}
if ($photo_2 && !empty($photo_2['photo'])) {
    $existing_photos[] = $photo_2;
}
if ($photo_3 && !empty($photo_3['photo'])) {
    $existing_photos[] = $photo_3;
}

$photo_count = count($existing_photos);
$max_photos = 3;
$can_upload = $photo_count < $max_photos;
?>

<div class="restaurant-photos-management">
    <h2>餐廳照片管理</h2>
    
    <!-- 預覽餐廳按鈕 -->
    <div style="text-align: center; margin: 20px 0; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px;">
        <a href="<?php echo get_permalink($restaurant_id); ?>" target="_blank" style="background-color: #8b2635; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">👁️ 預覽餐廳</a>
        <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">點擊按鈕在新分頁中預覽您的餐廳前台頁面</p>
        <p style="margin: 5px 0 0 0; color: #999; font-size: 12px;">餐廳ID: <?php echo $restaurant_id; ?> | 連結: <?php echo get_permalink($restaurant_id); ?></p>
    </div>
    
    <!-- 照片上傳區域 -->
    <?php if ($can_upload): ?>
        <div class="photo-upload-section">
            <h3>上傳新照片</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_photos">
                
                <div class="upload-instructions">
                    <p><strong>上傳須知：</strong></p>
                    <ul>
                        <li>最多可上傳 <?php echo $max_photos; ?> 張照片</li>
                        <li>建議上傳餐廳環境、用餐區域等代表性照片</li>
                        <li>照片說明為選填項目</li>
                        <li>支援 JPG、PNG 格式，單張檔案大小不超過 2MB</li>
                    </ul>
                </div>
                
                <div class="photo-upload-fields">
                    <div class="photo-field">
                        <label>選擇照片：</label>
                        <input type="file" name="photos[]" accept="image/*" required>
                    </div>
                    <div class="photo-field">
                        <label>照片說明（選填）：</label>
                        <input type="text" name="photo_description" placeholder="例如：餐廳用餐區域">
                    </div>
                </div>
                
                <button type="submit" class="upload-button">上傳照片</button>
            </form>
        </div>
    <?php else: ?>
        <div class="upload-limit-reached">
            <p>您已達到照片數量上限（<?php echo $max_photos; ?>張）。如需上傳新照片，請先刪除現有照片。</p>
        </div>
    <?php endif; ?>
    
    <!-- 現有照片管理 -->
    <div class="existing-photos-section">
        <h3>現有照片（<?php echo $photo_count; ?>/<?php echo $max_photos; ?>）</h3>
        
        <?php if (empty($existing_photos)): ?>
            <p>目前還沒有上傳任何照片。</p>
        <?php else: ?>
            <div class="photos-grid">
                <?php foreach ($existing_photos as $index => $photo): ?>
                    <div class="photo-item">
                        <div class="photo-preview">
                            <img src="<?php echo esc_url($photo['photo']['sizes']['thumbnail']); ?>" 
                                 alt="<?php echo esc_attr($photo['description'] ?: '餐廳照片'); ?>">
                        </div>
                        <div class="photo-info">
                            <?php if ($photo['description']): ?>
                                <p class="photo-description"><?php echo esc_html($photo['description']); ?></p>
                            <?php endif; ?>
                            <form method="post" class="delete-photo-form">
                                <input type="hidden" name="action" value="delete_photo">
                                <input type="hidden" name="photo_id" value="<?php echo $photo['photo']['ID']; ?>">
                                <button type="submit" class="delete-button" onclick="return confirm('確定要刪除這張照片嗎？')">刪除</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.restaurant-photos-management {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.photo-upload-section, .existing-photos-section {
    background: #f9f9f9;
    padding: 20px;
    margin: 20px 0;
    border-radius: 8px;
}

.upload-instructions {
    background: #e7f3ff;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.upload-instructions ul {
    margin: 10px 0;
    padding-left: 20px;
}

.photo-field {
    margin: 15px 0;
}

.photo-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.photo-field input[type="file"],
.photo-field input[type="text"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.upload-button, .delete-button {
    background: #0073aa;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.delete-button {
    background: #dc3232;
    padding: 5px 10px;
    font-size: 12px;
}

.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.photo-item {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.photo-preview img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 4px;
}

.photo-info {
    margin-top: 10px;
}

.photo-description {
    margin: 5px 0;
    font-style: italic;
    color: #666;
}

.upload-limit-reached {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    padding: 15px;
    border-radius: 5px;
    color: #856404;
}

.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 15px;
    border-radius: 5px;
    margin: 20px 0;
}

.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 15px;
    border-radius: 5px;
    margin: 20px 0;
}
</style>
