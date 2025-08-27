<?php
/**
 * 餐廳直接加入功能 - 極簡測試版本
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 建立簡化餐廳文章
 */
function byob_create_simple_restaurant($restaurant_data) {
    // 基本餐廳建立邏輯
    $post_data = array(
        'post_title' => sanitize_text_field($restaurant_data['restaurant_name']),
        'post_content' => '',
        'post_status' => 'publish',
        'post_type' => 'restaurant',
        'post_author' => $restaurant_data['user_id'] ?? 1,
    );
    
    $post_id = wp_insert_post($post_data);
    if (is_wp_error($post_id)) {
        return new WP_Error('restaurant_creation_failed', '餐廳建立失敗');
    }
    
    return array(
        'success' => true,
        'post_id' => $post_id,
        'message' => '餐廳已成功建立！'
    );
}

/**
 * 處理餐廳註冊
 */
function byob_handle_direct_restaurant_registration($form_data) {
    // 基本驗證
    if (empty($form_data['restaurant_name']) || empty($form_data['email'])) {
        return new WP_Error('missing_field', '必填欄位不能為空');
    }
    
    // 建立用戶
    $user_data = array(
        'user_login' => $form_data['email'],
        'user_email' => $form_data['email'],
        'user_pass' => $form_data['password'],
        'display_name' => $form_data['contact_person'],
        'role' => 'restaurant_owner'
    );
    
    $user_id = wp_insert_user($user_data);
    if (is_wp_error($user_id)) {
        return new WP_Error('user_creation_failed', '用戶建立失敗');
    }
    
    // 建立餐廳
    $restaurant_data = array(
        'restaurant_name' => $form_data['restaurant_name'],
        'user_id' => $user_id
    );
    
    $result = byob_create_simple_restaurant($restaurant_data);
    if (is_wp_error($result)) {
        wp_delete_user($user_id);
        return $result;
    }
    
    return array(
        'success' => true,
        'message' => '註冊成功！'
    );
}

/**
 * 驗證表單資料
 */
function byob_validate_direct_registration_form($form_data) {
    return array(
        'restaurant_name' => sanitize_text_field($form_data['restaurant_name']),
        'contact_person' => sanitize_text_field($form_data['contact_person']),
        'phone' => sanitize_text_field($form_data['phone']),
        'address' => sanitize_text_field($form_data['address']),
        'email' => sanitize_email($form_data['email']),
        'password' => $form_data['password']
    );
}

/**
 * AJAX 處理
 */
function byob_handle_direct_registration_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'byob_direct_registration')) {
        wp_send_json_error('安全驗證失敗');
    }
    
    $cleaned_data = byob_validate_direct_registration_form($_POST);
    $result = byob_handle_direct_restaurant_registration($cleaned_data);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success($result);
}

// 註冊 AJAX 處理函數
add_action('wp_ajax_byob_direct_registration', 'byob_handle_direct_registration_ajax');
add_action('wp_ajax_nopriv_byob_direct_registration', 'byob_handle_direct_registration_ajax');

/**
 * 極簡短代碼
 */
function byob_restaurant_registration_form_shortcode($atts) {
    $nonce = wp_create_nonce('byob_direct_registration');
    
    $form_html = '<div style="max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9;">';
    $form_html .= '<form id="byob-restaurant-registration" method="post">';
    $form_html .= '<div style="margin-bottom: 15px;"><label>餐廳名稱 *</label><input type="text" name="restaurant_name" required style="width: 100%; padding: 8px;"></div>';
    $form_html .= '<div style="margin-bottom: 15px;"><label>聯絡人姓名 *</label><input type="text" name="contact_person" required style="width: 100%; padding: 8px;"></div>';
    $form_html .= '<div style="margin-bottom: 15px;"><label>聯絡電話 *</label><input type="tel" name="phone" required style="width: 100%; padding: 8px;"></div>';
    $form_html .= '<div style="margin-bottom: 15px;"><label>餐廳地址 *</label><textarea name="address" required style="width: 100%; padding: 8px;"></textarea></div>';
    $form_html .= '<div style="margin-bottom: 15px;"><label>餐廳Email *</label><input type="email" name="email" required style="width: 100%; padding: 8px;"></div>';
    $form_html .= '<div style="margin-bottom: 15px;"><label>密碼 *</label><input type="password" name="password" required style="width: 100%; padding: 8px;"></div>';
    $form_html .= '<input type="hidden" name="nonce" value="' . $nonce . '">';
    $form_html .= '<button type="submit" style="width: 100%; padding: 10px; background: #8b2635; color: white; border: none;">註冊餐廳</button>';
    $form_html .= '</form>';
    $form_html .= '<div id="registration-message" style="margin-top: 15px;"></div>';
    $form_html .= '</div>';
    
    $form_html .= '<script>';
    $form_html .= 'jQuery(document).ready(function($) {';
    $form_html .= '$("#byob-restaurant-registration").on("submit", function(e) {';
    $form_html .= 'e.preventDefault();';
    $form_html .= 'var formData = new FormData(this);';
    $form_html .= 'formData.append("action", "byob_direct_registration");';
    $form_html .= '$.ajax({';
    $form_html .= 'url: "' . admin_url('admin-ajax.php') . '",';
    $form_html .= 'type: "POST",';
    $form_html .= 'data: formData,';
    $form_html .= 'processData: false,';
    $form_html .= 'contentType: false,';
    $form_html .= 'success: function(response) {';
    $form_html .= 'if (response.success) {';
    $form_html .= '$("#registration-message").html("<div style=background:#d4edda;color:#155724;padding:10px;>" + response.data.message + "</div>");';
    $form_html .= '} else {';
    $form_html .= '$("#registration-message").html("<div style=background:#f8d7da;color:#721c24;padding:10px;>" + response.data + "</div>");';
    $form_html .= '}';
    $form_html .= '},';
    $form_html .= 'error: function() {';
    $form_html .= '$("#registration-message").html("<div style=background:#f8d7da;color:#721c24;padding:10px;>發生錯誤</div>");';
    $form_html .= '}';
    $form_html .= '});';
    $form_html .= '});';
    $form_html .= '});';
    $form_html .= '</script>';
    
    return $form_html;
}

// 註冊短代碼
add_shortcode('byob_restaurant_registration_form', 'byob_restaurant_registration_form_shortcode');

?>
