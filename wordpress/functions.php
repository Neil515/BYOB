<?php
// Add custom Theme Functions here

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
                'sanitize_callback' => 'sanitize_text_field', // 允許多網址，用逗號分隔
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
            'post_status' => 'publish', // 修正：改為已發布狀態
            'post_type' => 'restaurant',
            'post_author' => 1,
        );
        
        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            throw new Exception('Failed to create post: ' . $post_id->get_error_message());
        }

        // ====== 轉換欄位格式 ======
        // 餐廳類型 (checkbox 陣列)
        $types = $request->get_param('restaurant_type');
        if (!empty($types) && !is_array($types)) {
            $types = array_map('trim', explode(',', $types));
        }

        // 是否收開瓶費 (中英 mapping)
        $is_charged_map = ['酌收' => 'yes', '不收費' => 'no', '其他' => 'other'];
        $is_charged_value = $request->get_param('is_charged');
        if (isset($is_charged_map[$is_charged_value])) {
            $is_charged_value = $is_charged_map[$is_charged_value];
        }

        // 提供酒器設備 (checkbox 陣列)
        $equipment = $request->get_param('equipment');
        if (!empty($equipment) && !is_array($equipment)) {
            $equipment = array_map('trim', explode(',', $equipment));
        }

        // 是否提供開酒服務 (中英 mapping)
        $open_bottle_service_map = ['有' => 'yes', '無' => 'no', '其他' => 'other'];
        $service_value = $request->get_param('open_bottle_service');
        if (isset($open_bottle_service_map[$service_value])) {
            $service_value = $open_bottle_service_map[$service_value];
        }

        // 社群連結（允許多網址）
        $social_media = $request->get_param('social_media');
        if (!empty($social_media)) {
            $social_links_array = array_map('trim', explode(',', $social_media));
            // 只存第一個網址進 ACF（前台顯示多個時可自行合併）
            $social_media_primary = $social_links_array[0];
        } else {
            $social_media_primary = '';
        }

        // ====== 更新 ACF 欄位 ======
        if (function_exists('update_field')) {
            $acf_updates = array(
                'contact_person' => $request->get_param('contact_person'),
                'email' => $request->get_param('email'),
                'restaurant_type' => $types,
                'address' => $request->get_param('address'),
                'is_charged' => $is_charged_value,
                'corkage_fee' => $request->get_param('corkage_fee'),
                'equipment' => $equipment,
                'open_bottle_service' => $service_value,
                'open_bottle_service_other_note' => $request->get_param('open_bottle_service_other_note'),
                'phone' => $request->get_param('phone'),
                'website' => $request->get_param('website'),
                'social_links' => $social_media_primary,
                'notes' => $request->get_param('notes'),
                'last_updated' => current_time('Y-m-d'),
                'source' => $request->get_param('is_owner') === '是' ? '店主' : '表單填寫者'
            );
            
            foreach ($acf_updates as $field_name => $field_value) {
                update_field($field_name, $field_value, $post_id);
            }
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
            'message' => 'Restaurant post created successfully',
            'post_url' => get_permalink($post_id)
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
    if (count($logs) > 50) {
        $logs = array_slice($logs, -50);
    }
    update_option('byob_api_logs', $logs);
}

// 註冊餐廳自訂文章類型與地區分類
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
    
    register_taxonomy('district', 'restaurant', array(
        'labels' => array(
            'name' => '地區',
            'singular_name' => '地區',
            'search_items' => '搜尋地區',
            'all_items' => '所有地區',
            'parent_item' => '父地區',
            'edit_item' => '編輯地區',
            'add_new_item' => '新增地區',
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
    </div>
    <?php
}

// 新增：檢查現有餐廳文章狀態
function byob_check_existing_restaurants() {
    $args = array(
        'post_type' => 'restaurant',
        'post_status' => array('publish', 'draft', 'pending'),
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    $restaurants = get_posts($args);
    $results = array();
    
    foreach ($restaurants as $restaurant) {
        $acf_fields = array();
        
        if (function_exists('get_fields')) {
            $acf_fields = get_fields($restaurant->ID);
        }
        
        $results[] = array(
            'post_id' => $restaurant->ID,
            'title' => $restaurant->post_title,
            'status' => $restaurant->post_status,
            'date' => $restaurant->post_date,
            'url' => get_permalink($restaurant->ID),
            'acf_fields' => $acf_fields
        );
    }
    
    return $results;
}

// 新增：測試 ACF 欄位更新
function byob_test_acf_update($post_id) {
    if (!function_exists('update_field')) {
        return array('error' => 'ACF 函數不存在');
    }
    
    $test_fields = array(
        'contact_person' => '測試聯絡人',
        'email' => 'test@example.com',
        'restaurant_type' => array('中式', '台式'),
        'address' => '測試地址',
        'is_charged' => 'yes',
        'corkage_fee' => '測試開瓶費說明',
        'equipment' => array('酒杯', '開瓶器'),
        'open_bottle_service' => 'yes',
        'phone' => '02-12345678',
        'website' => 'https://test.com',
        'social_links' => 'https://instagram.com/test',
        'notes' => '測試備註',
        'last_updated' => current_time('Y-m-d'),
        'source' => '測試來源'
    );
    
    $results = array();
    
    foreach ($test_fields as $field_name => $field_value) {
        $result = update_field($field_name, $field_value, $post_id);
        $results[$field_name] = array(
            'value' => $field_value,
            'update_result' => $result,
            'get_result' => get_field($field_name, $post_id)
        );
    }
    
    return $results;
}
