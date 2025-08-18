<?php
/**
 * 餐廳註冊功能
 * 
 * 提供餐廳業者直接註冊功能，包括：
 * - 註冊表單顯示
 * - 表單資料驗證
 * - 會員帳號創建
 * - 錯誤處理和成功訊息
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 顯示餐廳註冊表單
 */
function byob_display_registration_form($atts = array()) {
    // 檢查用戶是否已登入
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (in_array('restaurant_owner', $current_user->roles)) {
            echo '<div class="alert alert-info">您已經註冊為餐廳業者，無需重複註冊。</div>';
            return;
        }
    }
    
    // 獲取表單資料（如果有錯誤重新提交）
    $form_data = isset($_POST['restaurant_registration']) ? $_POST['restaurant_registration'] : array();
    $errors = isset($_POST['registration_errors']) ? $_POST['registration_errors'] : array();
    
    ?>
    <div class="restaurant-registration-form">
        <form method="post" action="" id="restaurant-registration-form" enctype="multipart/form-data">
            <?php wp_nonce_field('restaurant_registration_nonce', 'registration_nonce'); ?>
            
            <!-- 步驟指示器 -->
            <div class="registration-steps">
                <div class="step active" data-step="1">
                    <span class="step-number">1</span>
                    <span class="step-title">基本資訊</span>
                </div>
                <div class="step" data-step="2">
                    <span class="step-number">2</span>
                    <span class="step-title">餐廳詳情</span>
                </div>
                <div class="step" data-step="3">
                    <span class="step-number">3</span>
                    <span class="step-title">聯絡資訊</span>
                </div>
                <div class="step" data-step="4">
                    <span class="step-number">4</span>
                    <span class="step-title">確認提交</span>
                </div>
            </div>
            
            <!-- 步驟1：基本資訊 -->
            <div class="form-step active" data-step="1">
                <h3>餐廳基本資訊</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="restaurant_name">餐廳名稱 *</label>
                        <input type="text" id="restaurant_name" name="restaurant_registration[restaurant_name]" 
                               value="<?php echo esc_attr($form_data['restaurant_name'] ?? ''); ?>" required>
                        <?php if (isset($errors['restaurant_name'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['restaurant_name']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="restaurant_type">餐廳類型 *</label>
                        <select id="restaurant_type" name="restaurant_registration[restaurant_type]" required>
                            <option value="">請選擇餐廳類型</option>
                            <option value="中式餐廳" <?php selected($form_data['restaurant_type'] ?? '', '中式餐廳'); ?>>中式餐廳</option>
                            <option value="西式餐廳" <?php selected($form_data['restaurant_type'] ?? '', '西式餐廳'); ?>>西式餐廳</option>
                            <option value="日式餐廳" <?php selected($form_data['restaurant_type'] ?? '', '日式餐廳'); ?>>日式餐廳</option>
                            <option value="韓式餐廳" <?php selected($form_data['restaurant_type'] ?? '', '韓式餐廳'); ?>>韓式餐廳</option>
                            <option value="泰式餐廳" <?php selected($form_data['restaurant_type'] ?? '', '泰式餐廳'); ?>>泰式餐廳</option>
                            <option value="火鍋店" <?php selected($form_data['restaurant_type'] ?? '', '火鍋店'); ?>>火鍋店</option>
                            <option value="燒烤店" <?php selected($form_data['restaurant_type'] ?? '', '燒烤店'); ?>>燒烤店</option>
                            <option value="牛排館" <?php selected($form_data['restaurant_type'] ?? '', '牛排館'); ?>>牛排館</option>
                            <option value="其他" <?php selected($form_data['restaurant_type'] ?? '', '其他'); ?>>其他</option>
                        </select>
                        <?php if (isset($errors['restaurant_type'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['restaurant_type']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="restaurant_type_other">其他類型說明</label>
                        <input type="text" id="restaurant_type_other" name="restaurant_registration[restaurant_type_other]" 
                               value="<?php echo esc_attr($form_data['restaurant_type_other'] ?? ''); ?>"
                               placeholder="如果選擇其他，請說明餐廳類型">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-primary next-step" data-next="2">下一步</button>
                </div>
            </div>
            
            <!-- 步驟2：餐廳詳情 -->
            <div class="form-step" data-step="2">
                <h3>餐廳詳情</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="restaurant_address">營業地址 *</label>
                        <input type="text" id="restaurant_address" name="restaurant_registration[restaurant_address]" 
                               value="<?php echo esc_attr($form_data['restaurant_address'] ?? ''); ?>" required>
                        <?php if (isset($errors['restaurant_address'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['restaurant_address']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="business_hours">營業時間 *</label>
                        <input type="text" id="business_hours" name="restaurant_registration[business_hours]" 
                               value="<?php echo esc_attr($form_data['business_hours'] ?? ''); ?>" 
                               placeholder="例：週一至週五 11:00-22:00，週六日 10:00-23:00" required>
                        <?php if (isset($errors['business_hours'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['business_hours']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="open_bottle_service">是否提供開酒服務？ *</label>
                        <select id="open_bottle_service" name="restaurant_registration[open_bottle_service]" required>
                            <option value="">請選擇</option>
                            <option value="yes" <?php selected($form_data['open_bottle_service'] ?? '', 'yes'); ?>>是</option>
                            <option value="no" <?php selected($form_data['open_bottle_service'] ?? '', 'no'); ?>>否</option>
                            <option value="other" <?php selected($form_data['open_bottle_service'] ?? '', 'other'); ?>>其他</option>
                        </select>
                        <?php if (isset($errors['open_bottle_service'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['open_bottle_service']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="open_bottle_service_other_note">開酒服務說明</label>
                        <textarea id="open_bottle_service_other_note" name="restaurant_registration[open_bottle_service_other_note]" 
                                  placeholder="如果選擇其他，請說明開酒服務的詳細內容"><?php echo esc_textarea($form_data['open_bottle_service_other_note'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary prev-step" data-prev="1">上一步</button>
                    <button type="button" class="btn btn-primary next-step" data-next="3">下一步</button>
                </div>
            </div>
            
            <!-- 步驟3：聯絡資訊 -->
            <div class="form-step" data-step="3">
                <h3>聯絡資訊</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_name">聯絡人姓名 *</label>
                        <input type="text" id="contact_name" name="restaurant_registration[contact_name]" 
                               value="<?php echo esc_attr($form_data['contact_name'] ?? ''); ?>" required>
                        <?php if (isset($errors['contact_name'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['contact_name']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_phone">聯絡電話 *</label>
                        <input type="tel" id="contact_phone" name="restaurant_registration[contact_phone]" 
                               value="<?php echo esc_attr($form_data['contact_phone'] ?? ''); ?>" required>
                        <?php if (isset($errors['contact_phone'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['contact_phone']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_email">聯絡Email *</label>
                        <input type="email" id="contact_email" name="restaurant_registration[contact_email]" 
                               value="<?php echo esc_attr($form_data['contact_email'] ?? ''); ?>" required>
                        <?php if (isset($errors['contact_email'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['contact_email']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">密碼 *</label>
                        <input type="password" id="password" name="restaurant_registration[password]" required>
                        <?php if (isset($errors['password'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['password']); ?></span>
                        <?php endif; ?>
                        <small class="form-help">密碼至少8個字元，建議包含字母和數字</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="confirm_password">確認密碼 *</label>
                        <input type="password" id="confirm_password" name="restaurant_registration[confirm_password]" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['confirm_password']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary prev-step" data-prev="2">上一步</button>
                    <button type="button" class="btn btn-primary next-step" data-next="4">下一步</button>
                </div>
            </div>
            
            <!-- 步驟4：確認提交 -->
            <div class="form-step" data-step="4">
                <h3>確認資訊</h3>
                
                <div class="confirmation-summary">
                    <div class="summary-item">
                        <strong>餐廳名稱：</strong>
                        <span id="summary-restaurant-name"></span>
                    </div>
                    <div class="summary-item">
                        <strong>餐廳類型：</strong>
                        <span id="summary-restaurant-type"></span>
                    </div>
                    <div class="summary-item">
                        <strong>營業地址：</strong>
                        <span id="summary-restaurant-address"></span>
                    </div>
                    <div class="summary-item">
                        <strong>營業時間：</strong>
                        <span id="summary-business-hours"></span>
                    </div>
                    <div class="summary-item">
                        <strong>開酒服務：</strong>
                        <span id="summary-open-bottle-service"></span>
                    </div>
                    <div class="summary-item">
                        <strong>聯絡人：</strong>
                        <span id="summary-contact-name"></span>
                    </div>
                    <div class="summary-item">
                        <strong>聯絡電話：</strong>
                        <span id="summary-contact-phone"></span>
                    </div>
                    <div class="summary-item">
                        <strong>聯絡Email：</strong>
                        <span id="summary-contact-email"></span>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary prev-step" data-prev="3">上一步</button>
                    <button type="submit" class="btn btn-success" name="submit_registration">提交註冊</button>
                </div>
            </div>
        </form>
    </div>
    
    <?php
    // 只載入JavaScript檔案，CSS放在WordPress自訂→附加CSS中
    wp_enqueue_script('restaurant-registration', get_template_directory_uri() . '/restaurant-registration.js', array('jquery'), '1.0.0', true);
    ?>
    <?php
}

/**
 * 短代碼函數
 */
function byob_restaurant_registration_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_title' => 'true',
        'theme' => 'default'
    ), $atts);
    
    ob_start();
    byob_display_registration_form($atts);
    return ob_get_clean();
}
add_shortcode('restaurant_registration_form', 'byob_restaurant_registration_shortcode');

/**
 * 處理註冊表單提交
 */
function byob_handle_registration_form() {
    // 檢查nonce
    if (!wp_verify_nonce($_POST['registration_nonce'], 'restaurant_registration_nonce')) {
        wp_die('安全驗證失敗');
    }
    
    // 獲取表單資料
    $form_data = $_POST['restaurant_registration'];
    
    // 驗證必填欄位
    $errors = array();
    $required_fields = array(
        'restaurant_name' => '餐廳名稱',
        'restaurant_type' => '餐廳類型',
        'restaurant_address' => '營業地址',
        'business_hours' => '營業時間',
        'open_bottle_service' => '開酒服務',
        'contact_name' => '聯絡人姓名',
        'contact_phone' => '聯絡電話',
        'contact_email' => '聯絡Email',
        'password' => '密碼',
        'confirm_password' => '確認密碼'
    );
    
    foreach ($required_fields as $field => $label) {
        if (empty($form_data[$field])) {
            $errors[$field] = $label . '為必填欄位';
        }
    }
    
    // 驗證email格式
    if (!empty($form_data['contact_email']) && !is_email($form_data['contact_email'])) {
        $errors['contact_email'] = '請輸入有效的Email地址';
    }
    
    // 驗證email是否已被使用
    if (!empty($form_data['contact_email']) && email_exists($form_data['contact_email'])) {
        $errors['contact_email'] = '此email已被註冊';
    }
    
    // 驗證密碼長度
    if (!empty($form_data['password']) && strlen($form_data['password']) < 8) {
        $errors['password'] = '密碼至少需要8個字元';
    }
    
    // 驗證密碼確認
    if (!empty($form_data['password']) && !empty($form_data['confirm_password']) && 
        $form_data['password'] !== $form_data['confirm_password']) {
        $errors['confirm_password'] = '密碼與確認密碼不符';
    }
    
    // 驗證電話號碼格式
    if (!empty($form_data['contact_phone'])) {
        $phone = preg_replace('/[^0-9+\-\(\)]/', '', $form_data['contact_phone']);
        if (strlen($phone) < 8) {
            $errors['contact_phone'] = '請輸入有效的聯絡電話';
        }
    }
    
    // 如果有錯誤，重新顯示表單
    if (!empty($errors)) {
        $_POST['registration_errors'] = $errors;
        return;
    }
    
    // 創建用戶帳號
    $user_data = array(
        'user_login' => sanitize_user($form_data['contact_email']),
        'user_email' => sanitize_email($form_data['contact_email']),
        'user_pass' => $form_data['password'],
        'display_name' => sanitize_text_field($form_data['contact_name']),
        'first_name' => sanitize_text_field($form_data['contact_name']),
        'role' => 'restaurant_owner'
    );
    
    $user_id = wp_insert_user($user_data);
    
    if (is_wp_error($user_id)) {
        $errors['general'] = '創建用戶帳號失敗：' . $user_id->get_error_message();
        $_POST['registration_errors'] = $errors;
        return;
    }
    
    // 創建餐廳文章
    $restaurant_data = array(
        'post_title' => sanitize_text_field($form_data['restaurant_name']),
        'post_content' => '',
        'post_status' => 'publish',
        'post_type' => 'restaurant',
        'post_author' => $user_id
    );
    
    $restaurant_id = wp_insert_post($restaurant_data);
    
    if (is_wp_error($restaurant_id)) {
        // 如果創建餐廳失敗，刪除用戶帳號
        wp_delete_user($user_id);
        $errors['general'] = '創建餐廳資料失敗：' . $restaurant_id->get_error_message();
        $_POST['registration_errors'] = $errors;
        return;
    }
    
    // 設定餐廳meta資料
    update_post_meta($restaurant_id, '_restaurant_owner', $user_id);
    update_post_meta($restaurant_id, '_restaurant_type', sanitize_text_field($form_data['restaurant_type']));
    update_post_meta($restaurant_id, '_restaurant_address', sanitize_text_field($form_data['restaurant_address']));
    update_post_meta($restaurant_id, '_business_hours', sanitize_text_field($form_data['business_hours']));
    update_post_meta($restaurant_id, '_contact_phone', sanitize_text_field($form_data['contact_phone']));
    
    // 設定ACF欄位
    if (function_exists('update_field')) {
        update_field('restaurant_type', sanitize_text_field($form_data['restaurant_type']), $restaurant_id);
        update_field('restaurant_address', sanitize_text_field($form_data['restaurant_address']), $restaurant_id);
        update_field('business_hours', sanitize_text_field($form_data['business_hours']), $restaurant_id);
        update_field('open_bottle_service', sanitize_text_field($form_data['open_bottle_service']), $restaurant_id);
        
        if (!empty($form_data['restaurant_type_other'])) {
            update_field('restaurant_type_other_note', sanitize_text_field($form_data['restaurant_type_other']), $restaurant_id);
        }
        
        if (!empty($form_data['open_bottle_service_other_note'])) {
            update_field('open_bottle_service_other_note', sanitize_textarea_field($form_data['open_bottle_service_other_note']), $restaurant_id);
        }
    }
    
    // 自動登入用戶
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    // 重定向到成功頁面或後台
    wp_redirect(admin_url());
    exit;
}

/**
 * AJAX處理註冊請求
 */
function byob_ajax_registration() {
    // 檢查nonce
    if (!wp_verify_nonce($_POST['nonce'], 'restaurant_registration_nonce')) {
        wp_send_json_error('安全驗證失敗');
    }
    
    // 處理註冊邏輯
    byob_handle_registration_form();
    
    // 返回結果
    if (empty($_POST['registration_errors'])) {
        wp_send_json_success('註冊成功');
    } else {
        wp_send_json_error($_POST['registration_errors']);
    }
}
add_action('wp_ajax_restaurant_registration', 'byob_ajax_registration');
add_action('wp_ajax_nopriv_restaurant_registration', 'byob_ajax_registration');

/**
 * 處理表單提交
 */
if ($_POST && isset($_POST['submit_registration'])) {
    byob_handle_registration_form();
}
