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
            'social_media' => array('social_media', 'social', 'social_links'),
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

        // 更新 ACF 欄位
        if (function_exists('update_field')) {
            $acf_updates = array(
                'contact_person' => get_param_value($request, $param_mapping['contact_person']) ?: '',
                'email' => get_param_value($request, $param_mapping['email']) ?: '',
                'restaurant_type' => $types ?: array(),
                'address' => get_param_value($request, $param_mapping['address']) ?: '',
                'is_charged' => get_param_value($request, $param_mapping['is_charged']) ?: '',
                'corkage_fee' => get_param_value($request, $param_mapping['corkage_fee']) ?: '',
                'equipment' => $equipment ?: array(),
                'open_bottle_service' => get_param_value($request, $param_mapping['open_bottle_service']) ?: '',
                'open_bottle_service_other_note' => get_param_value($request, $param_mapping['open_bottle_service_other_note']) ?: '',
                'phone' => get_param_value($request, $param_mapping['phone']) ?: '',
                'website' => get_param_value($request, $param_mapping['website']) ?: '',
                'social_media' => $social_media_primary ?: '', // 修正欄位名稱
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
