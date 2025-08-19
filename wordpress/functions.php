<?php
// Add custom Theme Functions here

// BYOB 功能開關設定 - 已移至檔案結尾的 byob_get_feature_settings() 函數

// BYOB Google Form 自動導入 WordPress 功能
// 建立自訂 REST API 端點
add_action('rest_api_init', function () {
    register_rest_route('byob/v1', '/restaurant', array(
        'methods' => 'POST',
        'callback' => 'byob_create_restaurant_post',
        'permission_callback' => 'byob_verify_api_key',
        'args' => array(
            'restaurant_name' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'contact_person' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'email' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_email',
            ),
            'restaurant_type' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'district' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'address' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
            'is_charged' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'corkage_fee' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'equipment' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'open_bottle_service' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'open_bottle_service_other_note' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'phone' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'website' => array(
                'required' => false,
                'sanitize_callback' => 'esc_url_raw',
            ),
            'social_media' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'notes' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
            'is_owner' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));
    
    // 新增除錯端點
    register_rest_route('byob/v1', '/debug', array(
        'methods' => 'GET',
        'callback' => 'byob_debug_page',
        'permission_callback' => function() {
            if (current_user_can('administrator')) {
                return true;
            }
            return byob_verify_api_key(new WP_REST_Request());
        },
    ));
    
    // 新增測試端點
    register_rest_route('byob/v1', '/test', array(
        'methods' => 'POST',
        'callback' => 'byob_test_endpoint',
        'permission_callback' => '__return_true',
    ));
});

// API 金鑰驗證
function byob_verify_api_key($request) {
    $api_key = $request->get_header('X-API-Key');
    $valid_key = get_option('byob_api_key', 'byob-secret-key-2025');
    
    if (!$api_key || $api_key !== $valid_key) {
        return new WP_Error('invalid_api_key', 'Invalid API key', array('status' => 401));
    }
    return true;
}

// 建立餐廳文章
function byob_create_restaurant_post($request) {
    try {
        // 除錯：記錄接收到的所有參數
        $received_params = $request->get_params();
        error_log('BYOB API: 接收到的參數: ' . print_r($received_params, true));
        
        // 支援多種參數名稱的映射
        $param_mapping = array(
            'restaurant_name' => array('restaurant_name', 'name', 'restaurant_name'),
            'contact_person' => array('contact_person', 'contact', 'contact_name'),
            'email' => array('email', 'contact_email', 'email_address'),
            'restaurant_type' => array('restaurant_type', 'type', 'category'),
            'district' => array('district', 'area', 'region'),
            'address' => array('address', 'restaurant_address', 'location'),
            'is_charged' => array('is_charged', 'charged', 'corkage_charged'),
            'phone' => array('phone', 'contact_phone', 'phone_number'),
            'corkage_fee' => array('corkage_fee', 'fee', 'corkage_fee_amount'),
            'equipment' => array('equipment', 'equipment_list', 'available_equipment'),
            'open_bottle_service' => array('open_bottle_service', 'bottle_service', 'service_type'),
            'open_bottle_service_other_note' => array('open_bottle_service_other_note', 'service_note', 'other_service'),
            'website' => array('website', 'website_url', 'url'),
            'social_media' => array('social_media', 'social', 'social_links', '餐廳 Instagram 或 Facebook'),
            'notes' => array('notes', 'additional_notes', 'comments'),
            'is_owner' => array('is_owner', 'owner', 'is_restaurant_owner')
        );
        
        // 獲取參數值（支援多種名稱）
        function get_param_value($request, $param_names) {
            foreach ($param_names as $name) {
                $value = $request->get_param($name);
                if (!empty($value)) {
                    return $value;
                }
            }
            return '';
        }
        
        // 檢查必填參數
        $required_params = array(
            'restaurant_name', 'contact_person', 'email', 'restaurant_type', 
            'district', 'address', 'is_charged', 'phone'
        );
        
        $missing_params = array();
        foreach ($required_params as $param) {
            if (empty(get_param_value($request, $param_mapping[$param]))) {
                $missing_params[] = $param;
            }
        }
        
        if (!empty($missing_params)) {
            error_log('BYOB API: 缺少必填參數: ' . implode(', ', $missing_params));
            return new WP_Error('missing_required_params', '缺少必填參數: ' . implode(', ', $missing_params), array('status' => 400));
        }
        
        // 建立新文章 - 改為草稿狀態
        $post_data = array(
            'post_title' => get_param_value($request, $param_mapping['restaurant_name']),
            'post_content' => get_param_value($request, $param_mapping['notes']) ?: '',
            'post_status' => 'draft', // 改為草稿狀態，等待審核
            'post_type' => 'restaurant',
            'post_author' => 1,
        );
        
        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            throw new Exception('Failed to create post: ' . $post_id->get_error_message());
        }

        // 處理餐廳類型
        $types = get_param_value($request, $param_mapping['restaurant_type']);
        if (!empty($types) && !is_array($types)) {
            $types = array_map('trim', explode(',', $types));
        }

        // 處理設備
        $equipment = get_param_value($request, $param_mapping['equipment']);
        if (!empty($equipment) && !is_array($equipment)) {
            $equipment = array_map('trim', explode(',', $equipment));
        }
        
        // 處理社群連結
        $social_media = get_param_value($request, $param_mapping['social_media']);
        if (!empty($social_media)) {
            $social_links_array = array_map('trim', explode(',', $social_media));
            $social_media_primary = $social_links_array[0];
        } else {
            $social_media_primary = '';
        }

        // 處理 ACF 欄位值格式轉換
        $is_charged_raw = get_param_value($request, $param_mapping['is_charged']);
        $is_charged_converted = '';
        if (strpos($is_charged_raw, '酌收') !== false) {
            $is_charged_converted = 'yes';
        } elseif (strpos($is_charged_raw, '不收') !== false) {
            $is_charged_converted = 'no';
        } elseif (strpos($is_charged_raw, '其他') !== false) {
            $is_charged_converted = 'other';
        } else {
            $is_charged_converted = $is_charged_raw; // 保持原值
        }
        
        $open_bottle_service_raw = get_param_value($request, $param_mapping['open_bottle_service']);
        $open_bottle_service_converted = '';
        if (strpos($open_bottle_service_raw, '是') !== false) {
            $open_bottle_service_converted = 'yes';
        } elseif (strpos($open_bottle_service_raw, '否') !== false) {
            $open_bottle_service_converted = 'no';
        } elseif (strpos($open_bottle_service_raw, '其他') !== false) {
            $open_bottle_service_converted = 'other';
        } else {
            $open_bottle_service_converted = $open_bottle_service_raw; // 保持原值
        }

        // 更新 ACF 欄位
        if (function_exists('update_field')) {
            $acf_updates = array(
                'contact_person' => get_param_value($request, $param_mapping['contact_person']) ?: '',
                'email' => get_param_value($request, $param_mapping['email']) ?: '',
                'restaurant_type' => $types ?: array(),
                'address' => get_param_value($request, $param_mapping['address']) ?: '',
                'is_charged' => $is_charged_converted ?: '',
                'corkage_fee' => get_param_value($request, $param_mapping['corkage_fee']) ?: '',
                'equipment' => $equipment ?: array(),
                'open_bottle_service' => $open_bottle_service_converted ?: '',
                'open_bottle_service_other_note' => get_param_value($request, $param_mapping['open_bottle_service_other_note']) ?: '',
                'phone' => get_param_value($request, $param_mapping['phone']) ?: '',
                'website' => get_param_value($request, $param_mapping['website']) ?: '',
                'social_links' => $social_media_primary ?: '', // 修正欄位名稱
                'notes' => get_param_value($request, $param_mapping['notes']) ?: '',
                'last_updated' => current_time('Y-m-d'),
                'source' => get_param_value($request, $param_mapping['is_owner']) === '是' ? '店主' : '表單填寫者',
                'is_owner' => get_param_value($request, $param_mapping['is_owner']) ?: '',
                'review_status' => 'pending', // 新增審核狀態
                'submitted_date' => current_time('mysql'), // 新增提交日期
                'review_date' => '', // 新增審核日期（初始為空）
                'review_notes' => '' // 新增審核備註（初始為空）
            );
            
            foreach ($acf_updates as $field_name => $field_value) {
                update_field($field_name, $field_value, $post_id);
            }
        }
        
        // 記錄 API 呼叫
        byob_log_api_call($post_id, $request->get_params(), 'draft_created');
        
        return array(
            'success' => true,
            'post_id' => $post_id,
            'post_url' => get_edit_post_link($post_id, ''),
            'message' => '餐廳資料已建立為草稿，等待審核'
        );

    } catch (Exception $e) {
        byob_log_api_call($post_id ?? 0, $request->get_params(), 'error: ' . $e->getMessage());
        return new WP_Error('restaurant_creation_failed', $e->getMessage(), array('status' => 500));
    }
}

// 記錄 API 呼叫
function byob_log_api_call($post_id, $params, $status) {
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'post_id' => $post_id,
        'params' => $params,
        'status' => $status
    );
    
    $logs = get_option('byob_api_logs', array());
    $logs[] = $log_entry;
    
    // 只保留最近100筆記錄
    if (count($logs) > 100) {
        $logs = array_slice($logs, -100);
    }
    
    update_option('byob_api_logs', $logs);
}

// 會員系統初始化
function byob_init_membership_systems() {
    $features = byob_get_feature_settings();
    
    // 檢查檔案是否存在再載入 - 使用多個可能的路徑
    // 優先檢查子主題目錄，然後是父主題目錄
    $possible_paths = array(
        get_stylesheet_directory(), // 樣式表目錄（子主題）- 優先
        get_template_directory(), // 當前主題目錄（可能是子主題）
        dirname(__FILE__), // 當前檔案目錄
        ABSPATH . 'wp-content/themes/flatsome-child', // 子主題目錄
        ABSPATH . 'wp-content/themes/flatsome' // 父主題目錄
    );
    
    $restaurant_member_file = null;
    $customer_member_file = null;
    
    // 尋找檔案
    foreach ($possible_paths as $path) {
        $restaurant_path = $path . '/restaurant-member-functions.php';
        $customer_path = $path . '/customer-member-functions.php';
        
        if (!$restaurant_member_file && file_exists($restaurant_path)) {
            $restaurant_member_file = $restaurant_path;
        }
        if (!$customer_member_file && file_exists($customer_path)) {
            $customer_member_file = $customer_path;
        }
    }
    
    // 新增除錯資訊
    error_log('BYOB: 主題目錄: ' . get_template_directory());
    error_log('BYOB: 當前檔案目錄: ' . dirname(__FILE__));
    error_log('BYOB: 餐廳會員檔案路徑: ' . ($restaurant_member_file ?: '未找到'));
    error_log('BYOB: 客人會員檔案路徑: ' . ($customer_member_file ?: '未找到'));
    
    // 載入餐廳業者會員系統（如果啟用）
    if ($features['restaurant_member_system'] && $restaurant_member_file) {
        require_once $restaurant_member_file;
        if (function_exists('byob_init_restaurant_member_system')) {
            byob_init_restaurant_member_system();
        }
        // 立即註冊重寫規則
        if (function_exists('byob_add_rewrite_rules')) {
            byob_add_rewrite_rules();
        }
        if (function_exists('byob_add_query_vars')) {
            add_filter('query_vars', 'byob_add_query_vars');
        }
    } else {
        if (!$features['restaurant_member_system']) {
            error_log('BYOB: 餐廳業者會員系統已停用');
        } else {
            error_log('BYOB: restaurant-member-functions.php 檔案不存在');
        }
    }
    
    // 載入一般客人會員系統（如果啟用）
    if ($features['customer_member_system'] && $customer_member_file) {
        require_once $customer_member_file;
        if (function_exists('byob_init_customer_member_system')) {
            byob_init_customer_member_system();
        }
    } else {
        if (!$features['customer_member_system']) {
            error_log('BYOB: 一般客人會員系統已停用');
        } else {
            error_log('BYOB: customer-member-functions.php 檔案不存在');
        }
    }
}

// 在 WordPress 初始化時載入會員系統
add_action('init', 'byob_init_membership_systems');

// 確保選單在正確時機註冊
add_action('admin_menu', function() {
    // 使用與初始化相同的邏輯尋找檔案
    // 優先檢查子主題目錄，然後是父主題目錄
    $possible_paths = array(
        get_stylesheet_directory(), // 樣式表目錄（子主題）- 優先
        get_template_directory(), // 當前主題目錄（可能是子主題）
        dirname(__FILE__), // 當前檔案目錄
        ABSPATH . 'wp-content/themes/flatsome-child', // 子主題目錄
        ABSPATH . 'wp-content/themes/flatsome' // 父主題目錄
    );
    
    $restaurant_member_file = null;
    foreach ($possible_paths as $path) {
        $restaurant_path = $path . '/restaurant-member-functions.php';
        if (file_exists($restaurant_path)) {
            $restaurant_member_file = $restaurant_path;
            break;
        }
    }
    
    if ($restaurant_member_file) {
        require_once $restaurant_member_file;
        
        // 註冊審核管理選單
        if (function_exists('byob_add_review_management_menu')) {
            byob_add_review_management_menu();
        }
        
        // 註冊會員管理選單
        if (function_exists('byob_add_member_management_menu')) {
            byob_add_member_management_menu();
        }
        
        // 註冊餐廳業者選單
        if (function_exists('byob_add_restaurant_owner_menu')) {
            byob_add_restaurant_owner_menu();
        }
    }
}, 20);

// 統一權限檢查功能
function byob_check_user_permissions($user_id, $restaurant_id, $permission_type) {
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return false;
    }
    
    switch ($permission_type) {
        case 'edit_restaurant':
            // 檢查是否為餐廳業者且擁有該餐廳
            if (in_array('restaurant_owner', $user->roles)) {
                $owner_restaurant_id = get_post_meta($restaurant_id, '_restaurant_owner_id', true);
                return $owner_restaurant_id == $user_id;
            }
            break;
            
        case 'view_restaurant_stats':
            // 檢查是否為餐廳業者
            return in_array('restaurant_owner', $user->roles);
            
        default:
            return false;
    }
}

// 新增會員系統相關 REST API 端點
add_action('rest_api_init', function () {
    $features = byob_get_feature_settings();
    
    // 邀請碼系統 API（如果啟用）
    if ($features['invitation_system']) {
        register_rest_route('byob/v1', '/restaurant/(?P<id>\d+)/invitation', array(
            'methods' => 'POST',
            'callback' => 'byob_generate_restaurant_invitation',
            'permission_callback' => function() {
                return current_user_can('administrator');
            },
        ));
    }

    register_rest_route('byob/v1', '/restaurant/(?P<id>\d+)/owner', array(
        'methods' => 'GET',
        'callback' => 'byob_get_restaurant_owner',
        'permission_callback' => '__return_true',
    ));
});

// 生成餐廳邀請
function byob_generate_restaurant_invitation($request) {
    $restaurant_id = $request->get_param('id');
    $restaurant = get_post($restaurant_id);
    
    if (!$restaurant || $restaurant->post_type !== 'restaurant') {
        return new WP_Error('restaurant_not_found', '餐廳不存在', array('status' => 404));
    }
    
    // 生成邀請碼
    $invitation_code = wp_generate_password(12, false);
    $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    // 儲存邀請碼到資料庫
    $invitation_data = array(
        'code' => $invitation_code,
        'restaurant_id' => $restaurant_id,
        'expires' => $expires,
        'used' => false,
        'created' => current_time('mysql')
    );
    
    update_post_meta($restaurant_id, '_byob_invitation_code', $invitation_data);
    
    return array(
        'success' => true,
        'invitation_code' => $invitation_code,
        'restaurant_name' => $restaurant->post_title
    );
}

// 獲取餐廳業者資訊
function byob_get_restaurant_owner($request) {
    $restaurant_id = $request->get_param('id');
    $owner_id = get_post_meta($restaurant_id, '_restaurant_owner_id', true);
    
    if (!$owner_id) {
        return array('has_owner' => false);
    }
    
    $owner = get_user_by('id', $owner_id);
    if (!$owner) {
        return array('has_owner' => false);
    }
    
    return array(
        'has_owner' => true,
        'owner_id' => $owner_id,
        'owner_name' => $owner->display_name,
        'owner_email' => $owner->user_email
    );
}

// 管理員設定頁面
function byob_api_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('權限不足');
    }
    
    if (isset($_POST['submit'])) {
        update_option('byob_api_key', sanitize_text_field($_POST['api_key']));
        echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
    }
    
    $api_key = get_option('byob_api_key', 'byob-secret-key-2025');
    
    echo '<div class="wrap">';
    echo '<h1>BYOB API 設定</h1>';
    echo '<form method="post">';
    echo '<table class="form-table">';
    echo '<tr><th scope="row">API 金鑰</th><td><input type="text" name="api_key" value="' . esc_attr($api_key) . '" class="regular-text" /></td></tr>';
    echo '</table>';
    echo '<p class="submit"><input type="submit" name="submit" class="button-primary" value="儲存設定" /></p>';
    echo '</form>';
    echo '</div>';
}

// 新增管理員選單
add_action('admin_menu', function() {
    add_options_page('BYOB API 設定', 'BYOB API', 'manage_options', 'byob-api-settings', 'byob_api_settings_page');
    
    // 新增功能開關管理頁面
    add_submenu_page(
        'tools.php',
        'BYOB 功能開關',
        'BYOB 功能開關',
        'manage_options',
        'byob-feature-toggle',
        'byob_feature_toggle_page'
    );
    
    // 新增簡化的會員系統狀態檢查選單
    add_submenu_page(
        'tools.php',
        'BYOB 系統狀態',
        'BYOB 系統狀態',
        'manage_options',
        'byob-system-status',
        'byob_system_status_page'
    );
    
    // 移除檔案上傳工具選單 - 不再需要
});

// 除錯頁面
function byob_debug_page() {
    if (!current_user_can('administrator')) {
        return new WP_Error('permission_denied', '權限不足', array('status' => 403));
    }
    
    // 檢查會員系統檔案 - 使用與初始化相同的邏輯
    $possible_paths = array(
        get_template_directory(), // 當前主題目錄（可能是子主題）
        get_stylesheet_directory(), // 樣式表目錄（子主題）
        get_template_directory(), // 父主題目錄
        dirname(__FILE__), // 當前檔案目錄
        ABSPATH . 'wp-content/themes/flatsome',
        ABSPATH . 'wp-content/themes/flatsome-child'
    );
    
    $restaurant_member_file = null;
    $customer_member_file = null;
    
    // 尋找檔案
    foreach ($possible_paths as $path) {
        $restaurant_path = $path . '/restaurant-member-functions.php';
        $customer_path = $path . '/customer-member-functions.php';
        
        if (!$restaurant_member_file && file_exists($restaurant_path)) {
            $restaurant_member_file = $restaurant_path;
        }
        if (!$customer_member_file && file_exists($customer_path)) {
            $customer_member_file = $customer_path;
        }
    }
    
    $debug_info = array(
        'wordpress_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'acf_loaded' => function_exists('get_field'),
        'restaurant_posts_count' => wp_count_posts('restaurant')->publish,
        'api_key' => get_option('byob_api_key', 'byob-secret-key-2025'),
        'template_directory' => get_template_directory(),
        'stylesheet_directory' => get_stylesheet_directory(),
        'membership_system' => array(
            'restaurant_member_file_exists' => $restaurant_member_file !== null,
            'customer_member_file_exists' => $customer_member_file !== null,
            'restaurant_member_file_path' => $restaurant_member_file,
            'customer_member_file_path' => $customer_member_file,
            'restaurant_owner_role_exists' => get_role('restaurant_owner') !== null,
            'customer_role_exists' => get_role('customer') !== null,
            'restaurant_owner_users_count' => count(get_users(array('role' => 'restaurant_owner'))),
            'customer_users_count' => count(get_users(array('role' => 'customer')))
        )
    );
    
    return $debug_info;
}

// 測試端點
function byob_test_endpoint($request) {
    $received_params = $request->get_params();
    $headers = $request->get_headers();
    
    return array(
        'success' => true,
        'message' => '測試端點正常運作',
        'received_params' => $received_params,
        'headers' => $headers,
        'timestamp' => current_time('mysql'),
        'server_info' => array(
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'rest_api_url' => rest_url('byob/v1/')
        )
    );
}

// 簡化的系統狀態檢查頁面
function byob_system_status_page() {
    if (!current_user_can('manage_options')) {
        wp_die('權限不足');
    }
    
    // 檢查會員系統檔案 - 使用與初始化相同的邏輯
    $possible_paths = array(
        get_template_directory(), // 當前主題目錄（可能是子主題）
        get_stylesheet_directory(), // 樣式表目錄（子主題）
        get_template_directory(), // 父主題目錄
        dirname(__FILE__), // 當前檔案目錄
        ABSPATH . 'wp-content/themes/flatsome',
        ABSPATH . 'wp-content/themes/flatsome-child'
    );
    
    $restaurant_member_file = null;
    $customer_member_file = null;
    
    // 尋找檔案
    foreach ($possible_paths as $path) {
        $restaurant_path = $path . '/restaurant-member-functions.php';
        $customer_path = $path . '/customer-member-functions.php';
        
        if (!$restaurant_member_file && file_exists($restaurant_path)) {
            $restaurant_member_file = $restaurant_path;
        }
        if (!$customer_member_file && file_exists($customer_path)) {
            $customer_member_file = $customer_path;
        }
    }
    
    // 檢查角色
    $restaurant_owner_role = get_role('restaurant_owner');
    $customer_role = get_role('customer');
    
    // 統計使用者
    $restaurant_owners = get_users(array('role' => 'restaurant_owner'));
    $customers = get_users(array('role' => 'customer'));
    
    echo '<div class="wrap">';
    echo '<h1>BYOB 系統狀態檢查</h1>';
    
    echo '<h2>📁 檔案狀態</h2>';
    echo '<table class="widefat">';
    echo '<tr><th>檔案</th><th>狀態</th><th>路徑</th></tr>';
    echo '<tr><td>餐廳業者會員系統</td><td>' . ($restaurant_member_file ? '✅ 存在' : '❌ 不存在') . '</td><td>' . ($restaurant_member_file ?: '未找到') . '</td></tr>';
    echo '<tr><td>一般客人會員系統</td><td>' . ($customer_member_file ? '✅ 存在' : '❌ 不存在') . '</td><td>' . ($customer_member_file ?: '未找到') . '</td></tr>';
    echo '</table>';
    
    echo '<h2>👥 角色狀態</h2>';
    echo '<table class="widefat">';
    echo '<tr><th>角色</th><th>狀態</th><th>使用者數量</th></tr>';
    echo '<tr><td>餐廳業者 (restaurant_owner)</td><td>' . ($restaurant_owner_role ? '✅ 已建立' : '❌ 未建立') . '</td><td>' . count($restaurant_owners) . '</td></tr>';
    echo '<tr><td>一般客人 (customer)</td><td>' . ($customer_role ? '✅ 已建立' : '❌ 未建立') . '</td><td>' . count($customers) . '</td></tr>';
    echo '</table>';
    
    echo '<h2>🔧 功能狀態</h2>';
    echo '<table class="widefat">';
    echo '<tr><th>功能</th><th>設定狀態</th><th>實際狀態</th></tr>';
    
    $features = byob_get_feature_settings();
    
    echo '<tr><td>餐廳業者會員系統</td><td>' . ($features['restaurant_member_system'] ? '✅ 啟用' : '❌ 停用') . '</td><td>' . (function_exists('byob_init_restaurant_member_system') ? '✅ 已載入' : '❌ 未載入') . '</td></tr>';
    echo '<tr><td>一般客人會員系統</td><td>' . ($features['customer_member_system'] ? '✅ 啟用' : '❌ 停用') . '</td><td>' . (function_exists('byob_init_customer_member_system') ? '✅ 已載入' : '❌ 未載入') . '</td></tr>';
    echo '<tr><td>邀請碼系統</td><td>' . ($features['invitation_system'] ? '✅ 啟用' : '❌ 停用') . '</td><td>' . (function_exists('byob_generate_restaurant_invitation') ? '✅ 可用' : '❌ 不可用') . '</td></tr>';
    echo '<tr><td>收藏系統</td><td>' . ($features['favorite_system'] ? '✅ 啟用' : '❌ 停用') . '</td><td>' . (function_exists('byob_toggle_favorite') ? '✅ 可用' : '❌ 不可用') . '</td></tr>';
    echo '<tr><td>評論系統</td><td>' . ($features['review_system'] ? '✅ 啟用' : '❌ 停用') . '</td><td>' . (function_exists('byob_add_review') ? '✅ 可用' : '❌ 不可用') . '</td></tr>';
    echo '<tr><td>積分系統</td><td>' . ($features['points_system'] ? '✅ 啟用' : '❌ 停用') . '</td><td>' . (function_exists('byob_add_points') ? '✅ 可用' : '❌ 不可用') . '</td></tr>';
    echo '<tr><td>REST API 端點</td><td>' . ($features['api_endpoints'] ? '✅ 啟用' : '❌ 停用') . '</td><td>✅ 已註冊</td></tr>';
    echo '</table>';
    
    echo '<h2>📊 統計資訊</h2>';
    echo '<table class="widefat">';
    echo '<tr><th>項目</th><th>數量</th></tr>';
    echo '<tr><td>餐廳文章總數</td><td>' . wp_count_posts('restaurant')->publish . '</td></tr>';
    echo '<tr><td>待審核餐廳</td><td>' . wp_count_posts('restaurant')->draft . '</td></tr>';
    echo '<tr><td>餐廳業者會員</td><td>' . count($restaurant_owners) . '</td></tr>';
    echo '<tr><td>一般客人會員</td><td>' . count($customers) . '</td></tr>';
    echo '</table>';
    
    echo '<h2>📋 手動部署說明</h2>';
    echo '<div class="notice notice-info">';
    echo '<p><strong>如果檔案狀態顯示「不存在」，請手動上傳以下檔案到主題目錄：</strong></p>';
    echo '<ul>';
    echo '<li><code>restaurant-member-functions.php</code></li>';
    echo '<li><code>customer-member-functions.php</code></li>';
    echo '</ul>';
    echo '<p><strong>上傳路徑：</strong> <code>' . get_template_directory() . '/</code></p>';
    echo '<p><strong>當前檢查路徑：</strong></p>';
    echo '<ul>';
    echo '<li>餐廳業者檔案：<code>' . ($restaurant_member_file ?: '未找到') . '</code></li>';
    echo '<li>一般客人檔案：<code>' . ($customer_member_file ?: '未找到') . '</code></li>';
    echo '</ul>';
    echo '<p><strong>系統會檢查以下路徑：</strong></p>';
    echo '<ul>';
    foreach ($possible_paths as $path) {
        echo '<li><code>' . $path . '/</code></li>';
    }
    echo '</ul>';
    echo '<p><strong>主題目錄資訊：</strong></p>';
    echo '<ul>';
    echo '<li>當前主題目錄：<code>' . get_template_directory() . '</code></li>';
    echo '<li>樣式表目錄（子主題）：<code>' . get_stylesheet_directory() . '</code></li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<h2>🧪 快速連結</h2>';
    echo '<p><a href="' . admin_url('admin.php?page=byob-api-settings') . '" class="button">API 設定</a> ';
    echo '<a href="' . admin_url('edit.php?post_type=restaurant&page=byob-review-management') . '" class="button">審核管理</a> ';
    echo '<a href="' . admin_url('edit.php?post_type=restaurant&page=byob-member-management') . '" class="button">會員管理</a> ';
    echo '<a href="' . admin_url('tools.php?page=byob-feature-toggle') . '" class="button">功能開關</a></p>';
    
    echo '</div>';
}

// 功能開關管理頁面
function byob_feature_toggle_page() {
    if (!current_user_can('manage_options')) {
        wp_die('權限不足');
    }
    
    if (isset($_POST['submit'])) {
        $features = array(
            'restaurant_member_system' => isset($_POST['restaurant_member_system']),
            'customer_member_system' => isset($_POST['customer_member_system']),
            'invitation_system' => isset($_POST['invitation_system']),
            'favorite_system' => isset($_POST['favorite_system']),
            'review_system' => isset($_POST['review_system']),
            'points_system' => isset($_POST['points_system']),
            'api_endpoints' => isset($_POST['api_endpoints'])
        );
        
        update_option('byob_feature_settings', $features);
        echo '<div class="notice notice-success"><p>功能設定已儲存！</p></div>';
    }
    
    $current_features = get_option('byob_feature_settings', byob_get_feature_settings());
    
    echo '<div class="wrap">';
    echo '<h1>BYOB 功能開關管理</h1>';
    echo '<p>在此頁面可以控制 BYOB 系統的各項功能啟用狀態。</p>';
    
    echo '<form method="post">';
    echo '<table class="form-table">';
    
    echo '<tr><th scope="row">餐廳業者會員系統</th><td>';
    echo '<label><input type="checkbox" name="restaurant_member_system" ' . ($current_features['restaurant_member_system'] ? 'checked' : '') . ' /> 啟用餐廳業者會員系統</label>';
    echo '<p class="description">允許餐廳業者註冊、登入和管理餐廳資料</p>';
    echo '</td></tr>';
    
    echo '<tr><th scope="row">一般客人會員系統</th><td>';
    echo '<label><input type="checkbox" name="customer_member_system" ' . ($current_features['customer_member_system'] ? 'checked' : '') . ' /> 啟用一般客人會員系統</label>';
    echo '<p class="description">允許一般客人註冊、登入和使用收藏功能</p>';
    echo '</td></tr>';
    
    echo '<tr><th scope="row">邀請碼系統</th><td>';
    echo '<label><input type="checkbox" name="invitation_system" ' . ($current_features['invitation_system'] ? 'checked' : '') . ' /> 啟用邀請碼系統</label>';
    echo '<p class="description">允許管理員為餐廳生成邀請碼</p>';
    echo '</td></tr>';
    
    echo '<tr><th scope="row">收藏系統</th><td>';
    echo '<label><input type="checkbox" name="favorite_system" ' . ($current_features['favorite_system'] ? 'checked' : '') . ' /> 啟用收藏系統</label>';
    echo '<p class="description">允許客人收藏喜歡的餐廳</p>';
    echo '</td></tr>';
    
    echo '<tr><th scope="row">評論系統</th><td>';
    echo '<label><input type="checkbox" name="review_system" ' . ($current_features['review_system'] ? 'checked' : '') . ' /> 啟用評論系統</label>';
    echo '<p class="description">允許客人對餐廳進行評論和評分</p>';
    echo '</td></tr>';
    
    echo '<tr><th scope="row">積分系統</th><td>';
    echo '<label><input type="checkbox" name="points_system" ' . ($current_features['points_system'] ? 'checked' : '') . ' /> 啟用積分系統</label>';
    echo '<p class="description">允許客人透過各種活動賺取積分</p>';
    echo '</td></tr>';
    
    echo '<tr><th scope="row">REST API 端點</th><td>';
    echo '<label><input type="checkbox" name="api_endpoints" ' . ($current_features['api_endpoints'] ? 'checked' : '') . ' /> 啟用 REST API 端點</label>';
    echo '<p class="description">提供外部系統整合的 API 介面</p>';
    echo '</td></tr>';
    
    echo '</table>';
    echo '<p class="submit"><input type="submit" name="submit" class="button-primary" value="儲存設定" /></p>';
    echo '</form>';
    
    echo '<h2>📋 功能說明</h2>';
    echo '<div class="notice notice-info">';
    echo '<p><strong>注意事項：</strong></p>';
    echo '<ul>';
    echo '<li>修改功能設定後，建議重新載入系統狀態檢查頁面確認變更</li>';
    echo '<li>停用功能後，相關的 API 端點和前端功能將無法使用</li>';
    echo '<li>評論系統和積分系統建議在系統穩定後再啟用</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '</div>';
}

// 更新功能設定函數，支援資料庫儲存
function byob_get_feature_settings() {
    $db_features = get_option('byob_feature_settings');
    if ($db_features) {
        return $db_features;
    }
    
    // 預設設定
    return array(
        'restaurant_member_system' => true,    // 餐廳業者會員系統
        'customer_member_system' => true,      // 一般客人會員系統
        'invitation_system' => true,           // 邀請碼系統
        'favorite_system' => true,             // 收藏系統
        'review_system' => false,              // 評論系統 - 初期關閉
        'points_system' => false,              // 積分系統 - 初期關閉
        'api_endpoints' => true,               // REST API 端點
    );
}

// =============================================================================
// 一鍵註冊邀請系統
// =============================================================================

// 當餐廳文章發布時自動發送邀請（使用審核通過時的email格式）
add_action('transition_post_status', 'byob_auto_send_invitation_on_publish', 10, 3);

function byob_auto_send_invitation_on_publish($new_status, $old_status, $post) {
    // 檢查是否為餐廳文章且從草稿變為發布
    if ($post->post_type !== 'restaurant') {
        return;
    }
    
    if ($new_status !== 'publish') {
        return;
    }
    
    if ($old_status === 'publish') {
        // 如果已經是發布狀態，不重複發送邀請
        return;
    }
    
    // 檢查功能是否啟用
    $features = byob_get_feature_settings();
    if (!$features['invitation_system']) {
        return;
    }
    
    // 檢查是否已經發送過邀請
    $invitation_sent = get_post_meta($post->ID, '_byob_invitation_sent', true);
    if ($invitation_sent) {
        return;
    }
    
    error_log('BYOB: 餐廳文章發布，準備發送邀請 - 文章ID: ' . $post->ID);
    
    // 使用審核通過時的email格式發送邀請
    $result = byob_send_approval_notification($post->ID);
    
    if ($result) {
        // 標記已發送邀請
        update_post_meta($post->ID, '_byob_invitation_sent', current_time('mysql'));
        
        error_log('BYOB: 邀請發送成功 - 文章ID: ' . $post->ID);
    } else {
        error_log('BYOB: 邀請發送失敗 - 文章ID: ' . $post->ID);
    }
}

// 注意：byob_send_restaurant_invitation 函數已被移除
// 改為使用 byob_send_approval_notification 函數統一發送email

// 創建邀請資料表
function byob_create_invitation_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'byob_invitations';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        token varchar(32) NOT NULL,
        restaurant_id bigint(20) NOT NULL,
        email varchar(100) NOT NULL,
        contact_person varchar(100) NOT NULL,
        expires datetime NOT NULL,
        used tinyint(1) DEFAULT 0,
        used_at datetime NULL,
        user_id bigint(20) NULL,
        created datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY token (token),
        KEY restaurant_id (restaurant_id),
        KEY email (email)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// 注意：byob_send_invitation_email 函數已被移除
// 改為使用 byob_send_approval_notification 函數統一發送email

// =============================================================================
// 註冊流程攔截和自動設定
// =============================================================================

// 載入邀請處理器
$invitation_handler_path = __DIR__ . '/invitation-handler.php';
if (file_exists($invitation_handler_path)) {
    require_once $invitation_handler_path;
} else {
    error_log('BYOB: invitation-handler.php 檔案不存在: ' . $invitation_handler_path);
}

// 確保重寫規則被正確載入
add_action('init', 'byob_maybe_flush_rewrite_rules');

function byob_maybe_flush_rewrite_rules() {
    // 檢查是否需要刷新重寫規則
    $rewrite_rules_version = get_option('byob_rewrite_rules_version', '0');
    $current_version = '1.0'; // 當重寫規則有更新時，增加這個版本號
    
    if ($rewrite_rules_version !== $current_version) {
        flush_rewrite_rules();
        update_option('byob_rewrite_rules_version', $current_version);
        error_log('BYOB: 重寫規則已刷新');
    }
}

// =============================================================================
// 審核通過通知email發送函數
// =============================================================================

/**
 * 發送審核通過通知和邀請郵件
 */
function byob_send_approval_notification($restaurant_id) {
    $restaurant = get_post($restaurant_id);
    $contact_email = get_field('email', $restaurant_id);
    $contact_person = get_field('contact_person', $restaurant_id);
    
    if (!$contact_email) {
        return false;
    }
    
    // 生成邀請碼
    $invitation_code = wp_generate_password(12, false);
    $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    // 儲存邀請碼
    $invitation_data = array(
        'code' => $invitation_code,
        'restaurant_id' => $restaurant_id,
        'expires' => $expires,
        'used' => false,
        'created' => current_time('mysql')
    );
    
    update_post_meta($restaurant_id, '_byob_invitation_code', $invitation_data);
    
    // 建立邀請連結
    $invitation_url = home_url('/register/restaurant?token=' . $invitation_code);
    
    // 郵件內容
    $subject = '🎉 恭喜！您的餐廳「' . $restaurant->post_title . '」已通過審核並上架 - BYOB 台北餐廳地圖';
    
    $message = '
    <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
        <div style="background-color: #8b2635; color: white; padding: 20px; text-align: center;">
            <h1>BYOB 台北餐廳地圖</h1>
        </div>
        
        <div style="padding: 20px; background-color: #f9f9f9;">
            <h2>親愛的 ' . ($contact_person ?: $restaurant->post_title . ' 負責人') . '，</h2>
            
            <div style="background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <h3 style="color: #155724; margin: 0;">🎉 恭喜！您的餐廳已通過審核並成功上架！</h3>
            </div>
            
            <div style="background-color: white; padding: 15px; margin: 20px 0; border-left: 4px solid #8b2635;">
                <strong>您的餐廳頁面：</strong><br>
                <a href="' . get_permalink($restaurant_id) . '">' . get_permalink($restaurant_id) . '</a>
            </div>
            
            <p>為了讓您能更好地管理餐廳資訊，我們邀請您註冊會員帳號：</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $invitation_url . '" style="background-color: #8b2635; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    🔗 立即註冊會員
                </a>
            </div>
            
            <h3>會員功能包括：</h3>
            <ul>
                <li>✅ 修改餐廳基本資訊</li>
                <li>✅ 上傳餐廳照片</li>
                <li>✅ 更新 BYOB 政策</li>
                <li>✅ 查看瀏覽統計</li>
                <li>✅ 回覆顧客評論</li>
            </ul>
            
            <p><strong>邀請碼：</strong> ' . $invitation_code . '</p>
            <p><small>此邀請碼將於 7 天後過期</small></p>
            
            <p>如有任何問題，請隨時聯絡我們。</p>
            
            <p>BYOB 台北餐廳地圖團隊</p>
        </div>
    </div>
    ';
    
    // 發送郵件
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $sent = wp_mail($contact_email, $subject, $message, $headers);
    
    return $sent;
}

// 攔截註冊頁面，處理邀請token
add_action('login_init', 'byob_handle_invitation_registration');

function byob_handle_invitation_registration() {
    // 只在註冊頁面處理
    if (!isset($_GET['action']) || $_GET['action'] !== 'register') {
        return;
    }
    
    // 檢查是否有邀請token
    $invitation_token = isset($_GET['invitation_token']) ? sanitize_text_field($_GET['invitation_token']) : '';
    $restaurant_id = isset($_GET['restaurant_id']) ? intval($_GET['restaurant_id']) : 0;
    
    if (empty($invitation_token) || empty($restaurant_id)) {
        return;
    }
    
    // 驗證邀請token
    $verification = byob_verify_invitation_token($invitation_token);
    
    if (!$verification['valid']) {
        // 如果token無效，顯示錯誤訊息並重導向
        wp_redirect(wp_login_url() . '?byob_error=' . urlencode($verification['error']));
        exit;
    }
    
    // 儲存邀請資訊到session（用於註冊完成後處理）
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['byob_invitation_token'] = $invitation_token;
    $_SESSION['byob_restaurant_id'] = $restaurant_id;
    $_SESSION['byob_invitation_data'] = $verification;
}

// 在註冊頁面顯示歡迎訊息
add_action('login_form_register', 'byob_add_invitation_welcome_message');

function byob_add_invitation_welcome_message() {
    $invitation_token = isset($_GET['invitation_token']) ? sanitize_text_field($_GET['invitation_token']) : '';
    $restaurant_id = isset($_GET['restaurant_id']) ? intval($_GET['restaurant_id']) : 0;
    
    if (empty($invitation_token) || empty($restaurant_id)) {
        return;
    }
    
    // 驗證邀請
    $verification = byob_verify_invitation_token($invitation_token);
    
    if ($verification['valid']) {
        $restaurant_name = $verification['restaurant']->post_title;
        $contact_person = $verification['invitation']->contact_person;
        
        echo '<div style="background: #e8f5e8; border: 1px solid #4caf50; padding: 20px; margin-bottom: 25px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        echo '<h3 style="margin: 0 0 15px 0; color: #2e7d32; font-size: 20px;">🎉 歡迎加入 BYOBMAP！</h3>';
        echo '<p style="margin: 0; font-size: 16px;">親愛的 <strong>' . esc_html($contact_person) . '</strong>，</p>';
        echo '<p style="margin: 8px 0; font-size: 16px;">您的餐廳「<strong>' . esc_html($restaurant_name) . '</strong>」已成功上架！</p>';
        echo '<p style="margin: 15px 0 0 0; font-size: 14px; color: #666; font-style: italic;">✨ 請填寫以下資訊完成會員註冊，開始享受專業的餐廳管理工具</p>';
        echo '</div>';
    }
}

// 顯示邀請錯誤訊息
add_action('login_form_login', 'byob_show_invitation_error');

function byob_show_invitation_error() {
    if (isset($_GET['byob_error'])) {
        $error_message = sanitize_text_field($_GET['byob_error']);
        echo '<div style="background: #ffe6e6; border: 1px solid #f44336; padding: 15px; margin-bottom: 20px; border-radius: 5px;">';
        echo '<h3 style="margin: 0 0 10px 0; color: #c62828;">⚠️ 邀請連結問題</h3>';
        echo '<p style="margin: 0; color: #d32f2f;">' . esc_html($error_message) . '</p>';
        echo '<p style="margin: 10px 0 0 0; font-size: 14px;">如需協助，請聯繫 BYOBMAP 客服。</p>';
        echo '</div>';
    }
}

// 自訂註冊頁面標題和說明
add_filter('gettext', 'byob_customize_registration_texts', 20, 3);

function byob_customize_registration_texts($translated_text, $text, $domain) {
    // 只在註冊頁面修改文字
    if (!isset($_GET['action']) || $_GET['action'] !== 'register') {
        return $translated_text;
    }
    
    // 檢查是否有邀請 token
    $invitation_token = isset($_GET['invitation_token']) ? sanitize_text_field($_GET['invitation_token']) : '';
    if (empty($invitation_token)) {
        return $translated_text;
    }
    
    // 自訂文字
    switch ($translated_text) {
        case '在這個網站註冊帳號':
            return '🚀 完成會員註冊，開啟餐廳管理新體驗';
        case '註冊確認通知會以電子郵件方式傳送至用於註冊帳號的電子郵件地址。':
            return '✨ 註冊完成後，您將收到確認通知，並可立即開始管理餐廳資料';
        case '註冊':
            return '🎉 立即註冊';
        case '登入':
            return '已有帳號？登入';
        case '忘記密碼?':
            return '忘記密碼？';
    }
    
    return $translated_text;
}

// 註冊完成後自動設定餐廳業者
add_action('user_register', 'byob_auto_setup_restaurant_owner');

function byob_auto_setup_restaurant_owner($user_id) {
    // 啟動session（如果尚未啟動）
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 檢查是否有邀請資訊
    if (!isset($_SESSION['byob_invitation_token']) || !isset($_SESSION['byob_restaurant_id'])) {
        return;
    }
    
    $invitation_token = $_SESSION['byob_invitation_token'];
    $restaurant_id = $_SESSION['byob_restaurant_id'];
    $invitation_data = $_SESSION['byob_invitation_data'];
    
    // 再次驗證邀請（安全起見）
    $verification = byob_verify_invitation_token($invitation_token);
    
    if (!$verification['valid']) {
        return;
    }
    
    // 設定餐廳業者角色和關聯
    $setup_result = byob_setup_restaurant_owner($user_id, $restaurant_id);
    
    if ($setup_result) {
        // 標記邀請為已使用
        byob_mark_invitation_used($invitation_token, $user_id);
        
        // 更新餐廳文章的業者資訊
        update_post_meta($restaurant_id, '_byob_owner_registered', current_time('mysql'));
        
        // 發送歡迎郵件給新註冊的業者
        byob_send_welcome_email($user_id, $restaurant_id);
        
        // 記錄日誌
        error_log("BYOB: 餐廳業者註冊成功 - 用戶ID: {$user_id}, 餐廳ID: {$restaurant_id}");
    }
    
    // 清除session資料
    unset($_SESSION['byob_invitation_token']);
    unset($_SESSION['byob_restaurant_id']);
    unset($_SESSION['byob_invitation_data']);
}

// 發送歡迎郵件給新註冊的餐廳業者
function byob_send_welcome_email($user_id, $restaurant_id) {
    $user = get_user_by('id', $user_id);
    $restaurant = get_post($restaurant_id);
    
    if (!$user || !$restaurant) {
        return false;
    }
    
    $restaurant_name = $restaurant->post_title;
    $user_name = $user->display_name ?: $user->user_login;
    $login_url = wp_login_url();
    $restaurant_url = get_permalink($restaurant_id);
    
    $subject = "歡迎加入 BYOBMAP！註冊成功通知";
    
    $message = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #8b2635; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .button { display: inline-block; background-color: #8b2635; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .info-box { background-color: #fff; padding: 15px; border-left: 4px solid #8b2635; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 註冊成功！</h1>
            </div>
            
            <div class='content'>
                <h2>親愛的 {$user_name}，您好！</h2>
                
                <p>恭喜您成功註冊為 BYOBMAP 餐廳業者會員！</p>
                
                <div class='info-box'>
                    <h3>📋 您的會員資訊</h3>
                    <p><strong>用戶名稱：</strong>{$user_name}</p>
                    <p><strong>關聯餐廳：</strong>{$restaurant_name}</p>
                    <p><strong>會員類型：</strong>餐廳業者</p>
                </div>
                
                <div class='info-box'>
                    <h3>🔗 重要連結</h3>
                    <p><strong>登入會員系統：</strong><br>
                    <a href='{$login_url}' class='button'>立即登入</a></p>
                    
                    <p><strong>您的餐廳頁面：</strong><br>
                    <a href='{$restaurant_url}'>{$restaurant_url}</a></p>
                </div>
                
                <h3>✨ 會員專屬功能</h3>
                <ul>
                    <li>✓ 更新餐廳資訊和營業時間</li>
                    <li>✓ 上傳餐廳照片和菜單</li>
                    <li>✓ 查看餐廳統計數據</li>
                    <li>✓ 回應客戶評價和問題</li>
                    <li>✓ 參與平台行銷活動</li>
                </ul>
                
                <p>如有任何問題，歡迎隨時與我們聯繫。</p>
                
                <p>
                    再次歡迎您的加入！<br>
                    BYOBMAP 團隊
                </p>
            </div>
        </div>
    </body>
    </html>";
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: BYOBMAP <noreply@byobmap.com>'
    );
    
    $sent = wp_mail($user->user_email, $subject, $message, $headers);
    
    if ($sent) {
        error_log("BYOB: 歡迎郵件發送成功 - 收件人: {$user->user_email}, 餐廳: {$restaurant_name}");
    } else {
        error_log("BYOB: 歡迎郵件發送失敗 - 收件人: {$user->user_email}, 餐廳: {$restaurant_name}");
    }
    
    return $sent;
}

// 確保session在WordPress中可用
add_action('init', function() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
});

// 手動重發邀請功能（後台使用）
function byob_manual_resend_invitation($restaurant_id) {
    if (!current_user_can('administrator')) {
        return array('success' => false, 'error' => '權限不足');
    }
    
    return byob_resend_invitation($restaurant_id);
}

// =============================================================================
// 診斷工具（僅管理員可用）
// =============================================================================

// 在後台新增診斷頁面
add_action('admin_menu', 'byob_add_diagnostic_menu');

function byob_add_diagnostic_menu() {
    add_submenu_page(
        'tools.php',
        'BYOB 系統診斷',
        'BYOB 診斷',
        'administrator',
        'byob-diagnostic',
        'byob_diagnostic_page'
    );
}

function byob_diagnostic_page() {
    echo '<div class="wrap">';
    echo '<h1>BYOB 系統診斷</h1>';
    
    if (isset($_POST['run_test']) && check_admin_referer('byob_diagnostic')) {
        byob_run_invitation_test();
    }
    
    echo '<form method="post">';
    wp_nonce_field('byob_diagnostic');
    echo '<h2>📋 系統狀態檢查</h2>';
    
    // 檢查基本資訊
    echo '<h3>1. 基本資訊</h3>';
    echo '<table class="widefat">';
    echo '<tr><td>PHP 版本</td><td>' . phpversion() . '</td></tr>';
    echo '<tr><td>WordPress 版本</td><td>' . get_bloginfo('version') . '</td></tr>';
    echo '<tr><td>主題</td><td>' . get_template() . '</td></tr>';
    echo '<tr><td>子主題</td><td>' . get_stylesheet() . '</td></tr>';
    echo '</table>';
    
    // 檢查檔案路徑
    echo '<h3>2. 檔案路徑檢查</h3>';
    $invitation_handler_path = __DIR__ . '/invitation-handler.php';
    echo '<table class="widefat">';
    echo '<tr><td>當前目錄</td><td>' . __DIR__ . '</td></tr>';
    echo '<tr><td>invitation-handler.php 路徑</td><td>' . $invitation_handler_path . '</td></tr>';
    echo '<tr><td>檔案存在</td><td>' . (file_exists($invitation_handler_path) ? '✅ 是' : '❌ 否') . '</td></tr>';
    if (file_exists($invitation_handler_path)) {
        echo '<tr><td>檔案大小</td><td>' . filesize($invitation_handler_path) . ' bytes</td></tr>';
    }
    echo '</table>';
    
    // 檢查函數存在
    echo '<h3>3. 邀請系統函數檢查</h3>';
    $functions_to_check = [
        'byob_verify_invitation_token',
        'byob_mark_invitation_used',
        'byob_setup_restaurant_owner',
        'byob_send_approval_notification',
        'byob_create_invitation_table'
    ];
    
    echo '<table class="widefat">';
    foreach ($functions_to_check as $func) {
        echo '<tr><td>' . $func . '</td><td>' . (function_exists($func) ? '✅ 存在' : '❌ 不存在') . '</td></tr>';
    }
    echo '</table>';
    
    // 檢查資料庫表格
    echo '<h3>4. 資料庫檢查</h3>';
    global $wpdb;
    $table_name = $wpdb->prefix . 'byob_invitations';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    echo '<table class="widefat">';
    echo '<tr><td>邀請資料表</td><td>' . ($table_exists ? '✅ 存在' : '❌ 不存在') . '</td></tr>';
    if ($table_exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo '<tr><td>邀請記錄數</td><td>' . $count . '</td></tr>';
    }
    echo '</table>';
    
    // 檢查功能設定
    echo '<h3>5. 功能設定檢查</h3>';
    $features = byob_get_feature_settings();
    echo '<table class="widefat">';
    foreach ($features as $key => $value) {
        echo '<tr><td>' . $key . '</td><td>' . ($value ? '✅ 啟用' : '❌ 停用') . '</td></tr>';
    }
    echo '</table>';
    
    echo '<h3>6. 測試邀請功能</h3>';
    echo '<p><strong>注意：</strong>此測試會檢查邀請系統是否正常運作，但不會真的發送郵件。</p>';
    echo '<p class="submit"><input type="submit" name="run_test" class="button-primary" value="執行邀請功能測試" /></p>';
    
    echo '</form>';
    echo '</div>';
}

function byob_run_invitation_test() {
    echo '<div class="notice notice-info"><p><strong>正在執行邀請功能測試...</strong></p></div>';
    
    // 檢查是否有餐廳文章可以測試
    $restaurants = get_posts(array(
        'post_type' => 'restaurant',
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ));
    
    if (empty($restaurants)) {
        echo '<div class="notice notice-error"><p>❌ 沒有找到已發布的餐廳文章，無法測試</p></div>';
        return;
    }
    
    $restaurant = $restaurants[0];
    echo '<div class="notice notice-success"><p>✅ 找到測試餐廳：' . $restaurant->post_title . '</p></div>';
    
    // 檢查餐廳是否有必要欄位
    $contact_person = get_field('contact_person', $restaurant->ID);
    $email = get_field('email', $restaurant->ID);
    
    echo '<h4>餐廳資料檢查：</h4>';
    echo '<ul>';
    echo '<li>聯絡人：' . ($contact_person ? '✅ ' . $contact_person : '❌ 未設定') . '</li>';
    echo '<li>Email：' . ($email ? '✅ ' . $email : '❌ 未設定') . '</li>';
    echo '</ul>';
    
    if (!$email || !is_email($email)) {
        echo '<div class="notice notice-error"><p>❌ 餐廳缺少有效的 Email 地址，無法繼續測試</p></div>';
        return;
    }
    
    // 測試邀請函數
    if (function_exists('byob_send_approval_notification')) {
        echo '<h4>測試邀請生成（不發送郵件）：</h4>';
        
        // 暫時覆蓋郵件函數以避免真的發送
        add_filter('pre_wp_mail', function($return, $atts) {
            echo '<div class="notice notice-info"><p>📧 模擬發送郵件到：' . $atts['to'] . '</p></div>';
            echo '<div class="notice notice-info"><p>📧 郵件主旨：' . $atts['subject'] . '</p></div>';
            return true; // 阻止真的發送郵件
        }, 10, 2);
        
        $result = byob_send_approval_notification($restaurant->ID);
        
        if ($result) {
            echo '<div class="notice notice-success"><p>✅ 邀請生成成功！</p></div>';
            echo '<p>邀請碼已生成並儲存到餐廳的 post meta 中</p>';
        } else {
            echo '<div class="notice notice-error"><p>❌ 邀請生成失敗</p></div>';
        }
        
        // 移除郵件過濾器
        remove_all_filters('pre_wp_mail');
    } else {
        echo '<div class="notice notice-error"><p>❌ byob_send_approval_notification 函數不存在</p></div>';
    }
}

/**
 * 使用 WooCommerce 內容鉤子載入餐廳資料編輯表單
 */
function byob_load_restaurant_profile_content() {
    global $wp_query;
    
    // 檢查是否為餐廳資料編輯頁面
    if (is_account_page() && isset($wp_query->query_vars['restaurant-profile'])) {
        // 移除 WooCommerce 預設的帳戶內容
        remove_action('woocommerce_account_content', 'woocommerce_account_content', 10);
        
        // 載入我們的表單內容
        $template_path = get_stylesheet_directory() . '/woocommerce/myaccount/restaurant-profile.php';
        
        if (file_exists($template_path)) {
            error_log('BYOB: 載入餐廳資料編輯表單: ' . $template_path);
            include $template_path;
        } else {
            error_log('BYOB: 餐廳資料編輯表單檔案不存在: ' . $template_path);
        }
    }
}

// 使用 WooCommerce 內容鉤子
add_action('woocommerce_account_content', 'byob_load_restaurant_profile_content', 5);

// 載入餐廳註冊功能（安全載入）
$restaurant_registration_file = get_template_directory() . '/restaurant-registration-functions.php';
if (file_exists($restaurant_registration_file)) {
    require_once $restaurant_registration_file;
} else {
    // 記錄錯誤但不中斷網站
    error_log('BYOB: 餐廳註冊功能檔案不存在: ' . $restaurant_registration_file);
}

// 餐廳註冊短代碼函數（直接嵌入，確保功能正常）
function byob_restaurant_registration_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_title' => 'true',
        'theme' => 'default'
    ), $atts);
    
    ob_start();
    
    // 載入JavaScript檔案（修正路徑）
    $js_file = get_template_directory_uri() . '/restaurant-registration.js';
    $js_file_alt = get_stylesheet_directory_uri() . '/restaurant-registration.js';
    
    // 檢查檔案是否存在，優先使用子主題路徑
    if (file_exists(get_stylesheet_directory() . '/restaurant-registration.js')) {
        wp_enqueue_script('restaurant-registration', $js_file_alt, array('jquery'), '1.0.0', true);
    } elseif (file_exists(get_template_directory() . '/restaurant-registration.js')) {
        wp_enqueue_script('restaurant-registration', $js_file, array('jquery'), '1.0.0', true);
    } else {
        // 如果檔案都不存在，記錄錯誤
        error_log('BYOB: restaurant-registration.js 檔案不存在於以下路徑：');
        error_log('BYOB: 子主題路徑：' . get_stylesheet_directory() . '/restaurant-registration.js');
        error_log('BYOB: 父主題路徑：' . get_template_directory() . '/restaurant-registration.js');
    }
    
    // 顯示註冊表單
    ?>
    <div class="restaurant-registration-container">

        
        <?php
        // 檢查是否為成功狀態
        if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
            // 獲取餐廳ID和名稱
            $restaurant_id = isset($_GET['restaurant_id']) ? intval($_GET['restaurant_id']) : 0;
            
            // 調試信息（暫時顯示）
            echo '<!-- 調試信息: restaurant_id = ' . $restaurant_id . ' -->';
            
            // 建立單一餐廳頁面連結
            if ($restaurant_id) {
                $restaurant = get_post($restaurant_id);
                if ($restaurant && $restaurant->post_type === 'restaurant') {
                    // 使用餐廳的 slug 建立正確的單一餐廳頁面連結
                    $restaurant_slug = $restaurant->post_name;
                    $restaurant_permalink = home_url('/byob-restaurant/' . $restaurant_slug . '/');
                    echo '<!-- 調試信息: restaurant_slug = ' . $restaurant_slug . ' -->';
                    echo '<!-- 調試信息: restaurant_permalink = ' . $restaurant_permalink . ' -->';
                } else {
                    $restaurant_permalink = home_url('/byob-restaurant/');
                    echo '<!-- 調試信息: 無法獲取餐廳資訊或類型不正確 -->';
                }
            } else {
                $restaurant_permalink = home_url('/byob-restaurant/');
                echo '<!-- 調試信息: 沒有餐廳ID -->';
            }
            
            // 獲取業者登入資訊
            $owner_id = get_post_meta($restaurant_id, '_restaurant_owner_id', true);
            $login_info = '';
            
            if ($owner_id) {
                $user = get_user_by('id', $owner_id);
                if ($user) {
                    $login_info = '<div class="login-info" style="background: #e8f5e8; border: 1px solid #4caf50; padding: 20px; margin: 20px 0; border-radius: 8px;">';
                    $login_info .= '<h4 style="margin: 0 0 15px 0; color: #2e7d32;">🔐 您的登入資訊</h4>';
                    $login_info .= '<p style="margin: 0 0 10px 0;"><strong>登入網址：</strong> <a href="https://byobmap.com/my-account/" target="_blank">https://byobmap.com/my-account/</a></p>';
                    $login_info .= '<p style="margin: 0 0 10px 0;"><strong>用戶名稱：</strong> ' . esc_html($user->user_email) . '</p>';
                    $login_info .= '<p style="margin: 0; font-size: 14px; color: #666;"><strong>✅ 登入提醒：</strong>您已成功設定密碼，請使用剛才填寫的密碼登入。</p>';
                    $login_info .= '</div>';
                }
            }
            
            // 顯示成功訊息
            echo '<div class="success-message">';
            echo '<h3>🎉 餐廳上架成功！</h3>';
            echo '<p>恭喜！您的餐廳已經成功上架並出現在網站上。</p>';
            
            // 顯示登入資訊
            if ($login_info) {
                echo $login_info;
            }
            
            echo '<div class="success-actions">';
            echo '<h4>🚀 立即開始使用</h4>';
            echo '<div class="action-buttons">';
            echo '<a href="https://byobmap.com/my-account/" class="btn btn-primary">立即登入</a>';
            echo '<a href="' . $restaurant_permalink . '" class="btn btn-primary">查看餐廳</a>';
            echo '</div>';
            echo '</div>';
            echo '<div class="success-info">';
            echo '<h4>✨ 您現在可以：</h4>';
            echo '<ul>';
            echo '<li>✅ 登入後台管理餐廳資訊</li>';
            echo '<li>✅ 上傳餐廳照片</li>';
            echo '<li>✅ 更新BYOB政策</li>';
            echo '<li>✅ 查看餐廳瀏覽統計</li>';
            echo '</ul>';
            echo '</div>';
            echo '<div class="next-steps">';
            echo '<h4>📋 下一步操作：</h4>';
            echo '<ol>';
            echo '<li>點擊上方「立即登入」按鈕</li>';
            echo '<li>使用剛才填寫的Email登入帳號</li>';
            echo '<li>登入後即可開始管理餐廳</li>';
            echo '</ol>';
            echo '</div>';
            echo '</div>';
        } else {
            // 顯示註冊表單
            ?>
            <form id="restaurant-registration-form" class="registration-form" method="post">
            <?php wp_nonce_field('restaurant_registration', 'registration_nonce'); ?>
            
            <!-- 基本資訊 -->
            <div class="form-section">
                <h3>基本資訊</h3>
                
                <div class="form-group">
                    <label for="restaurant_name">餐廳名稱 *</label>
                    <input type="text" id="restaurant_name" name="restaurant_name" required>
                </div>
                
                <div class="form-group">
                    <label for="contact_person">聯絡人姓名 *</label>
                    <input type="text" id="contact_person" name="contact_person" required>
                </div>
                
                <div class="form-group">
                    <label for="email">聯絡Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">聯絡電話 *</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="password">設定密碼 *</label>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="請設定6位以上密碼">
                    <small class="form-text">此密碼將用於登入後台管理餐廳</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">確認密碼 *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="請再次輸入密碼">
                </div>
                
                <div class="form-group">
                    <label for="is_owner">您是否為餐廳業者？</label>
                    <select id="is_owner" name="is_owner">
                        <option value="是">是</option>
                        <option value="否">否</option>
                    </select>
                </div>
            </div>
            
            <!-- 餐廳詳情 -->
            <div class="form-section">
                <h3>餐廳詳情</h3>
                
                <div class="form-group">
                    <label for="restaurant_type">餐廳類型 *</label>
                    <select id="restaurant_type" name="restaurant_type" required>
                        <option value="">請選擇</option>
                        <option value="牛排">牛排</option>
                        <option value="燒烤">燒烤</option>
                        <option value="火鍋">火鍋</option>
                        <option value="其他">其他</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="district">行政區 *</label>
                    <select id="district" name="district" required>
                        <option value="">請選擇</option>
                        <option value="中正區">中正區</option>
                        <option value="大同區">大同區</option>
                        <option value="中山區">中山區</option>
                        <option value="松山區">松山區</option>
                        <option value="大安區">大安區</option>
                        <option value="萬華區">萬華區</option>
                        <option value="信義區">信義區</option>
                        <option value="士林區">士林區</option>
                        <option value="北投區">北投區</option>
                        <option value="內湖區">內湖區</option>
                        <option value="南港區">南港區</option>
                        <option value="文山區">文山區</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="address">餐廳地址 *</label>
                    <textarea id="address" name="address" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="website">餐廳網站</label>
                    <input type="url" id="website" name="website" placeholder="https://">
                </div>
                
                <div class="form-group">
                    <label for="social_media">社群媒體連結</label>
                    <input type="text" id="social_media" name="social_media" placeholder="Instagram 或 Facebook 連結">
                </div>
            </div>
            
            <!-- BYOB政策 -->
            <div class="form-section">
                <h3>BYOB政策</h3>
                
                <div class="form-group">
                    <label for="is_charged">是否酌收開瓶費？ *</label>
                    <select id="is_charged" name="is_charged" required>
                        <option value="">請選擇</option>
                        <option value="酌收">酌收</option>
                        <option value="不收">不收</option>
                        <option value="其他">其他</option>
                    </select>
                </div>
                
                <div class="form-group" id="corkage_fee_group" style="display: none;">
                    <label for="corkage_fee">開瓶費金額</label>
                    <input type="text" id="corkage_fee" name="corkage_fee" placeholder="例：100元/瓶">
                </div>
                
                <div class="form-group">
                    <label for="equipment">提供哪些開瓶設備？</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="equipment[]" value="開瓶器"> 開瓶器</label>
                        <label><input type="checkbox" name="equipment[]" value="醒酒器"> 醒酒器</label>
                        <label><input type="checkbox" name="equipment[]" value="冰桶"> 冰桶</label>
                        <label><input type="checkbox" name="equipment[]" value="酒杯"> 酒杯</label>
                        <label><input type="checkbox" name="equipment[]" value="其他"> 其他</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="open_bottle_service">是否提供開瓶服務？</label>
                    <select id="open_bottle_service" name="open_bottle_service">
                        <option value="">請選擇</option>
                        <option value="是">是</option>
                        <option value="否">否</option>
                        <option value="其他">其他</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes">其他備註</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="請描述您的餐廳特色、BYOB政策細節或其他重要資訊"></textarea>
                </div>
            </div>
            
            <!-- 同意條款 -->
            <div class="form-section">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="agree_terms" name="agree_terms" required>
                        我同意遵守BYOB平台的使用條款和隱私政策
                    </label>
                </div>
            </div>
            
            <!-- 提交按鈕 -->
            <div class="form-submit">
                <button type="submit" class="btn btn-success">立即上架餐廳</button>
            </div>
        </form>
        
        <!-- 載入中提示 -->
        <div id="loading" class="loading-overlay" style="display: none;">
            <div class="loading-spinner"></div>
            <p>正在處理您的申請...</p>
        </div>
        <?php
        } // 關閉 else 條件語句
        ?>
    </div>
    <?php
    
    return ob_get_clean();
}

// 註冊短代碼
add_shortcode('restaurant_registration_form', 'byob_restaurant_registration_shortcode');

// 處理表單提交
add_action('init', 'byob_handle_restaurant_registration');

function byob_handle_restaurant_registration() {
    // 檢查是否為表單提交
    if (!isset($_POST['registration_nonce']) || !wp_verify_nonce($_POST['registration_nonce'], 'restaurant_registration')) {
        return;
    }
    
    // 處理表單資料
    $restaurant_name = sanitize_text_field($_POST['restaurant_name']);
    $contact_person = sanitize_text_field($_POST['contact_person']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    
    // 建立餐廳文章（直接發布狀態）
    $post_data = array(
        'post_title' => $restaurant_name,
        'post_content' => sanitize_textarea_field($_POST['notes']),
        'post_status' => 'publish',  // 改為直接發布
        'post_type' => 'restaurant',
        'post_author' => 1,
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        wp_die('建立餐廳文章失敗：' . $post_id->get_error_message());
    }
    
    // 更新ACF欄位
    if (function_exists('update_field')) {
        update_field('contact_person', $contact_person, $post_id);
        update_field('email', $email, $post_id);
        update_field('phone', $phone, $post_id);
        update_field('restaurant_type', $_POST['restaurant_type'], $post_id);
        update_field('district', $_POST['district'], $post_id);
        update_field('address', sanitize_textarea_field($_POST['address']), $post_id);
        update_field('is_charged', $_POST['is_charged'], $post_id);
        update_field('corkage_fee', sanitize_text_field($_POST['corkage_fee']), $post_id);
        update_field('equipment', $_POST['equipment'], $post_id);
        update_field('open_bottle_service', $_POST['open_bottle_service'], $post_id);
        update_field('website', esc_url_raw($_POST['website']), $post_id);
        update_field('social_links', sanitize_text_field($_POST['social_media']), $post_id);
        update_field('notes', sanitize_textarea_field($_POST['notes']), $post_id);
        update_field('is_owner', $_POST['is_owner'], $post_id);
        update_field('source', '網站直接註冊', $post_id);
        update_field('submitted_date', current_time('mysql'), $post_id);
        update_field('review_status', 'pending', $post_id);
    }
    
    // 新增：自動為所有註冊者建立業者帳號
    $user_id = email_exists($email);
    
    // 驗證密碼
    $password = sanitize_text_field($_POST['password']);
    $confirm_password = sanitize_text_field($_POST['confirm_password']);
    
    if ($password !== $confirm_password) {
        wp_die('密碼與確認密碼不一致，請重新填寫。');
    }
    
    if (strlen($password) < 6) {
        wp_die('密碼長度至少需要6位，請重新設定。');
    }
    
    if (!$user_id) {
        // 建立新用戶
        $user_data = array(
            'user_login' => $email,
            'user_email' => $email,
            'user_pass' => $password, // 使用業者自訂的密碼
            'display_name' => $contact_person,
            'role' => 'restaurant_owner'
        );
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            error_log('BYOB: 建立餐廳業者用戶失敗: ' . $user_id->get_error_message());
        } else {
            error_log("BYOB: 餐廳業者用戶建立成功 - 用戶ID: {$user_id}, Email: {$email}");
        }
    } else {
        // 現有用戶，更新密碼並設定為餐廳業者角色
        $user = get_user_by('id', $user_id);
        if ($user) {
            // 更新密碼
            wp_set_password($password, $user_id);
            // 設定為餐廳業者角色
            if (!in_array('restaurant_owner', $user->roles)) {
                $user->add_role('restaurant_owner');
            }
            error_log("BYOB: 現有用戶密碼更新成功 - 用戶ID: {$user_id}, Email: {$email}");
        }
    }
    
    // 關聯餐廳與業者
    if ($user_id && !is_wp_error($user_id)) {
        update_post_meta($post_id, '_restaurant_owner_id', $user_id);
        update_user_meta($user_id, '_owned_restaurant_id', $post_id);
        
        // 記錄註冊時間和類型
        update_user_meta($user_id, '_byob_registered_at', current_time('mysql'));
        update_user_meta($user_id, '_byob_registration_type', 'direct_website');
        
        error_log("BYOB: 餐廳業者關聯成功 - 用戶ID: {$user_id}, 餐廳ID: {$post_id}");
    }
    
    // 重導向到成功頁面，包含餐廳ID
    wp_redirect(add_query_arg(array('registration' => 'success', 'restaurant_id' => $post_id), wp_get_referer()));
    exit;
}
