<?php
/**
 * 餐廳直接加入功能
 * 
 * 此檔案處理餐廳直接加入的註冊流程：
 * - 接收網站表單直接提交的資料
 * - 處理用戶帳號建立、權限驗證
 * - 調用共用函數建立發布狀態餐廳文章
 * - 立即上架，無需審核
 * 
 * 註冊流程：網站表單 → 建立用戶帳號 → 立即建立發布狀態餐廳 → 自動上架
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 處理餐廳直接加入請求，建立發布狀態餐廳文章
 * 
 * @param array $form_data 表單提交的資料
 * @return array|WP_Error 成功返回文章資訊，失敗返回錯誤
 */
function byob_handle_direct_restaurant_registration($form_data) {
    try {
        // 驗證必填欄位
        $required_fields = array(
            'email' => 'Email',
            'restaurant_name' => '餐廳名稱',
            'restaurant_type' => '餐廳類型',
            'district' => '行政區',
            'address' => '地址',
            'is_charged' => '是否收開瓶費',
            'source' => '餐廳負責人確認',
            'contact_person' => '聯絡人稱呼',
            'phone' => '聯絡電話'
        );
        
        foreach ($required_fields as $field => $label) {
            if (empty($form_data[$field])) {
                return new WP_Error('missing_field', $field . ' 是必填欄位');
            }
        }
        
        // 驗證 Email 格式
        if (!is_email($form_data['email'])) {
            return new WP_Error('invalid_email', 'Email 格式不正確');
        }
        
        // 檢查 Email 是否已存在
        if (email_exists($form_data['email'])) {
            return new WP_Error('email_exists', '此 Email 已被註冊');
        }
        
        // 驗證餐廳名稱長度
        if (strlen($form_data['restaurant_name']) < 2 || strlen($form_data['restaurant_name']) > 30) {
            return new WP_Error('invalid_restaurant_name', '餐廳名稱長度必須在 2-30 字元之間');
        }
        
        // 驗證地址長度
        if (strlen($form_data['address']) < 8 || strlen($form_data['address']) > 200) {
            return new WP_Error('invalid_address', '地址長度必須在 8-200 字元之間');
        }
        
        // 驗證電話長度
        if (strlen($form_data['phone']) < 8 || strlen($form_data['phone']) > 16) {
            return new WP_Error('invalid_phone', '電話長度必須在 8-16 字元之間');
        }
        
        // 建立用戶帳號
        $user_data = array(
            'user_login' => $form_data['email'],
            'user_email' => $form_data['email'],
            'user_pass' => wp_generate_password(12, false), // 生成隨機密碼
            'display_name' => $form_data['contact_person'],
            'role' => 'restaurant_owner'
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            return new WP_Error('user_creation_failed', '用戶帳號建立失敗：' . $user_id->get_error_message());
        }
        
        // 建立餐廳文章資料
        $restaurant_data = array(
            'restaurant_name' => $form_data['restaurant_name'],
            'contact_person' => $form_data['contact_person'],
            'email' => $form_data['email'],
            'restaurant_type' => $form_data['restaurant_type'],
            'district' => $form_data['district'],
            'address' => $form_data['address'],
            'is_charged' => $form_data['is_charged'],
            'corkage_fee' => isset($form_data['corkage_fee']) ? $form_data['corkage_fee'] : '',
            'equipment' => isset($form_data['equipment']) ? $form_data['equipment'] : array(),
            'open_bottle_service' => isset($form_data['open_bottle_service']) ? $form_data['open_bottle_service'] : '',
            'open_bottle_service_other_note' => isset($form_data['open_bottle_service_other_note']) ? $form_data['open_bottle_service_other_note'] : '',
            'website' => isset($form_data['website']) ? $form_data['website'] : '',
            'social_links' => isset($form_data['social_links']) ? $form_data['social_links'] : '',
            'phone' => $form_data['phone'],
            'user_id' => $user_id,
            'notes' => isset($form_data['notes']) ? $form_data['notes'] : '',
            'is_owner' => $form_data['source']
        );
        
        // 調用共用函數建立餐廳文章
        if (!function_exists('byob_create_restaurant_article')) {
            return new WP_Error('function_not_available', '餐廳建立函數不可用，請檢查系統設定');
        }
        
        $result = byob_create_restaurant_article($restaurant_data, 'direct');
        
        if (is_wp_error($result)) {
            // 如果餐廳建立失敗，刪除已建立的用戶
            wp_delete_user($user_id);
            return $result;
        }
        
        $restaurant_id = $result['post_id'];
        
        // 設定餐廳擁有者
        update_post_meta($restaurant_id, '_restaurant_owner_id', $user_id);
        update_user_meta($user_id, '_owned_restaurant_id', $restaurant_id);
        
        // 記錄註冊來源
        update_post_meta($restaurant_id, '_byob_registration_source', 'direct');
        update_post_meta($restaurant_id, '_byob_registration_time', current_time('mysql'));
        
        // 記錄聯絡人資訊
        update_post_meta($restaurant_id, '_contact_person', $form_data['contact_person']);
        update_post_meta($restaurant_id, '_source_confirmation', $form_data['source']);
        
        // 發送密碼重設郵件
        $reset_key = get_password_reset_key($user_data);
        if ($reset_key) {
            $reset_link = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($form_data['email']));
            
            $subject = '您的餐廳已成功加入 BYOB - 請設定密碼';
            $message = sprintf(
                '您好 %s，<br><br>' .
                '恭喜！您的餐廳「%s」已成功加入 BYOB 平台。<br><br>' .
                '請點擊以下連結設定您的密碼：<br>' .
                '<a href="%s">%s</a><br><br>' .
                '設定密碼後，您就可以登入並編輯餐廳資料。<br><br>' .
                '如有任何問題，請聯絡我們。<br><br>' .
                'BYOB 團隊',
                $form_data['contact_person'],
                $form_data['restaurant_name'],
                $reset_link,
                $reset_link
            );
            
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($form_data['email'], $subject, $message, $headers);
        }
        
        return array(
            'success' => true,
            'user_id' => $user_id,
            'restaurant_id' => $restaurant_id,
            'message' => '餐廳已成功加入！請檢查您的 Email 來設定密碼。',
            'restaurant_url' => get_permalink($restaurant_id)
        );
        
    } catch (Exception $e) {
        return new WP_Error('registration_failed', '註冊過程中發生錯誤：' . $e->getMessage());
    }
}

/**
 * 驗證表單資料
 * 
 * @param array $form_data 表單資料
 * @return array|WP_Error 驗證通過返回清理後的資料，失敗返回錯誤
 */
function byob_validate_direct_registration_form($form_data) {
    $cleaned_data = array();
    
    // 清理和驗證 Email
    $cleaned_data['email'] = sanitize_email($form_data['email']);
    
    // 清理和驗證餐廳名稱
    $cleaned_data['restaurant_name'] = sanitize_text_field($form_data['restaurant_name']);
    
    // 清理和驗證餐廳類型（多選）
    if (isset($form_data['restaurant_type']) && is_array($form_data['restaurant_type'])) {
        $cleaned_data['restaurant_type'] = array_map('sanitize_text_field', $form_data['restaurant_type']);
    } else {
        $cleaned_data['restaurant_type'] = array();
    }
    
    // 清理和驗證行政區
    $cleaned_data['district'] = sanitize_text_field($form_data['district']);
    
    // 清理和驗證地址
    $cleaned_data['address'] = sanitize_textarea_field($form_data['address']);
    
    // 清理和驗證開瓶費
    $cleaned_data['is_charged'] = sanitize_text_field($form_data['is_charged']);
    
    // 清理和驗證開瓶費說明
    if (isset($form_data['corkage_fee'])) {
        $cleaned_data['corkage_fee'] = sanitize_text_field($form_data['corkage_fee']);
    }
    
    // 清理和驗證酒器設備（多選）
    if (isset($form_data['equipment']) && is_array($form_data['equipment'])) {
        $cleaned_data['equipment'] = array_map('sanitize_text_field', $form_data['equipment']);
    } else {
        $cleaned_data['equipment'] = array();
    }
    
    // 清理和驗證開酒服務
    if (isset($form_data['open_bottle_service'])) {
        $cleaned_data['open_bottle_service'] = sanitize_text_field($form_data['open_bottle_service']);
    }
    
    // 清理和驗證開酒服務說明
    if (isset($form_data['open_bottle_service_other_note'])) {
        $cleaned_data['open_bottle_service_other_note'] = sanitize_text_field($form_data['open_bottle_service_other_note']);
    }
    
    // 清理和驗證網站
    if (isset($form_data['website'])) {
        $cleaned_data['website'] = esc_url_raw($form_data['website']);
    }
    
    // 清理和驗證社群連結
    if (isset($form_data['social_links'])) {
        $cleaned_data['social_links'] = esc_url_raw($form_data['social_links']);
    }
    
    // 清理和驗證餐廳負責人確認
    $cleaned_data['source'] = sanitize_text_field($form_data['source']);
    
    // 清理和驗證聯絡人稱呼
    $cleaned_data['contact_person'] = sanitize_text_field($form_data['contact_person']);
    
    // 清理和驗證電話
    $cleaned_data['phone'] = sanitize_text_field($form_data['phone']);
    
    return $cleaned_data;
}

/**
 * 處理 AJAX 表單提交
 */
function byob_handle_direct_registration_ajax() {
    // 檢查 nonce
    if (!wp_verify_nonce($_POST['nonce'], 'byob_direct_registration')) {
        wp_die(json_encode(array('success' => false, 'message' => '安全驗證失敗')));
    }
    
    // 驗證表單資料
    $cleaned_data = byob_validate_direct_registration_form($_POST);
    
    if (is_wp_error($cleaned_data)) {
        wp_die(json_encode(array('success' => false, 'message' => $cleaned_data->get_error_message())));
    }
    
    // 處理註冊
    $result = byob_handle_direct_restaurant_registration($cleaned_data);
    
    if (is_wp_error($result)) {
        wp_die(json_encode(array('success' => false, 'message' => $result->get_error_message())));
    }
    
    wp_die(json_encode($result));
}

// 註冊 AJAX 處理函數
add_action('wp_ajax_byob_direct_registration', 'byob_handle_direct_registration_ajax');
add_action('wp_ajax_nopriv_byob_direct_registration', 'byob_handle_direct_registration_ajax');

/**
 * 獲取餐廳類型選項
 */
function byob_get_restaurant_type_options() {
    return array(
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
        'Buffet' => 'Buffet',
        'Fine dining' => 'Fine dining'
    );
}

/**
 * 獲取行政區選項
 */
function byob_get_district_options() {
    return array(
        '中山區' => '中山區',
        '中正區' => '中正區',
        '大同區' => '大同區',
        '松山區' => '松山區',
        '大安區' => '大安區',
        '萬華區' => '萬華區',
        '信義區' => '信義區',
        '士林區' => '士林區',
        '北投區' => '北投區',
        '內湖區' => '內湖區',
        '南港區' => '南港區',
        '文山區' => '文山區'
    );
}

/**
 * 獲取酒器設備選項
 */
function byob_get_equipment_options() {
    return array(
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
}

/**
 * 獲取開酒服務選項
 */
function byob_get_open_bottle_service_options() {
    return array(
        'yes' => '是',
        'no' => '否',
        'other' => '其他'
    );
}

/**
 * 獲取開瓶費選項
 */
function byob_get_corkage_fee_options() {
    return array(
        'yes' => '酌收',
        'no' => '不收費',
        'other' => '其他'
    );
}

?>
