<?php
// Add custom Theme Functions here

// BYOB Google Form 自動導入 WordPress 功能
// 建立自訂 REST API 端點

// 註冊自訂 REST API 端點
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
                'sanitize_callback' => 'esc_url_raw',
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
        // 建立新文章
        $post_data = array(
            'post_title' => $request->get_param('restaurant_name'),
            'post_content' => $request->get_param('notes') ?: '',
            'post_status' => 'draft', // 設為草稿，需要人工審核
            'post_type' => 'restaurant',
            'post_author' => 1, // 管理員 ID
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            throw new Exception('Failed to create post: ' . $post_id->get_error_message());
        }
        
        // 更新 ACF 欄位
        if (function_exists('update_field')) {
            update_field('contact_person', $request->get_param('contact_person'), $post_id);
            update_field('email', $request->get_param('email'), $post_id);
            update_field('restaurant_type', $request->get_param('restaurant_type'), $post_id);
            update_field('address', $request->get_param('address'), $post_id);
            update_field('is_charged', $request->get_param('is_charged'), $post_id);
            update_field('corkage_fee', $request->get_param('corkage_fee'), $post_id);
            update_field('equipment', $request->get_param('equipment'), $post_id);
            update_field('open_bottle_service', $request->get_param('open_bottle_service'), $post_id);
            update_field('phone', $request->get_param('phone'), $post_id);
            update_field('social_links', $request->get_param('website'), $post_id);
            update_field('notes', $request->get_param('notes'), $post_id);
        }
        
        // 設定地區分類
        $district = $request->get_param('district');
        if ($district) {
            wp_set_object_terms($post_id, $district, 'district');
        }
        
        // 記錄 API 呼叫
        byob_log_api_call($post_id, $request->get_params(), 'success');
        
        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => 'Restaurant post created successfully'
        );
        
    } catch (Exception $e) {
        byob_log_api_call(0, $request->get_params(), 'error: ' . $e->getMessage());
        
        return new WP_Error(
            'creation_failed',
            'Failed to create restaurant post: ' . $e->getMessage(),
            array('status' => 500)
        );
    }
}

// 記錄 API 呼叫
function byob_log_api_call($post_id, $params, $status) {
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'post_id' => $post_id,
        'params' => $params,
        'status' => $status,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    );
    
    $logs = get_option('byob_api_logs', array());
    $logs[] = $log_entry;
    
    // 只保留最近 50 筆記錄
    if (count($logs) > 50) {
        $logs = array_slice($logs, -50);
    }
    
    update_option('byob_api_logs', $logs);
}

// 註冊餐廳自訂文章類型
add_action('init', function() {
    register_post_type('restaurant', array(
        'labels' => array(
            'name' => '餐廳',
            'singular_name' => '餐廳',
            'add_new' => '新增餐廳',
            'add_new_item' => '新增餐廳',
            'edit_item' => '編輯餐廳',
            'new_item' => '新餐廳',
            'view_item' => '查看餐廳',
            'search_items' => '搜尋餐廳',
            'not_found' => '找不到餐廳',
            'not_found_in_trash' => '垃圾桶中找不到餐廳'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-food',
        'rewrite' => array('slug' => 'restaurant')
    ));
    
    // 註冊地區分類
    register_taxonomy('district', 'restaurant', array(
        'labels' => array(
            'name' => '地區',
            'singular_name' => '地區',
            'search_items' => '搜尋地區',
            'all_items' => '所有地區',
            'parent_item' => '父地區',
            'parent_item_colon' => '父地區:',
            'edit_item' => '編輯地區',
            'update_item' => '更新地區',
            'add_new_item' => '新增地區',
            'new_item_name' => '新地區名稱',
            'menu_name' => '地區'
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'district')
    ));
});

// 加入管理選單
add_action('admin_menu', function() {
    add_menu_page(
        'BYOB API 設定',
        'BYOB API',
        'manage_options',
        'byob-api-settings',
        'byob_api_settings_page',
        'dashicons-rest-api',
        30
    );
});

// API 設定頁面
function byob_api_settings_page() {
    // 處理表單提交
    if (isset($_POST['submit'])) {
        if (isset($_POST['byob_api_key'])) {
            update_option('byob_api_key', sanitize_text_field($_POST['byob_api_key']));
        }
        echo '<div class="notice notice-success"><p>設定已儲存！</p></div>';
    }
    
    $current_key = get_option('byob_api_key', 'byob-secret-key-2025');
    $logs = get_option('byob_api_logs', array());
    
    ?>
    <div class="wrap">
        <h1>BYOB API 設定</h1>
        
        <h2>API 金鑰設定</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">API 金鑰</th>
                    <td>
                        <input type="text" name="byob_api_key" value="<?php echo esc_attr($current_key); ?>" class="regular-text" />
                        <p class="description">此金鑰用於驗證 Google Apps Script 的 API 呼叫</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        
        <h2>API 呼叫日誌</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>時間</th>
                    <th>文章 ID</th>
                    <th>狀態</th>
                    <th>IP 位址</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($logs) as $log): ?>
                <tr>
                    <td><?php echo esc_html($log['timestamp']); ?></td>
                    <td><?php echo esc_html($log['post_id']); ?></td>
                    <td><?php echo esc_html($log['status']); ?></td>
                    <td><?php echo esc_html($log['ip']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>測試 API 連接</h2>
        <p>API 端點：<code><?php echo esc_url(rest_url('byob/v1/restaurant')); ?></code></p>
        <p>請在 Google Apps Script 中使用此端點進行測試。</p>
    </div>
    <?php
}