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
    
    // 新增除錯端點
    register_rest_route('byob/v1', '/debug', array(
        'methods' => 'GET',
        'callback' => 'byob_debug_page',
        'permission_callback' => function() {
            // 允許管理員直接訪問，或者通過 API 金鑰驗證
            if (current_user_can('administrator')) {
                return true;
            }
            return byob_verify_api_key(new WP_REST_Request());
        },
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

        // 聯絡人
        $contact_person = $request->get_param('contact_person');
        error_log("BYOB: contact_person from request: " . var_export($contact_person, true));

        // 是否收開瓶費 (轉換為 ACF 期望的值)
        $is_charged_value = $request->get_param('is_charged');
        error_log("BYOB: Raw is_charged_value from request: " . var_export($is_charged_value, true));
        
        // 檢查所有可能的參數名稱
        $possible_is_charged_params = ['is_charged', '是否收開瓶費', '是否收開瓶費？'];
        foreach ($possible_is_charged_params as $param_name) {
            $value = $request->get_param($param_name);
            if (!empty($value)) {
                $is_charged_value = $value;
                error_log("BYOB: Found is_charged_value in param '{$param_name}': " . var_export($value, true));
                break;
            }
        }
        
        // 轉換中文值為 ACF 期望的值
        $is_charged_map = [
            '酌收' => 'yes',
            '不收費' => 'no', 
            '其他' => 'other',
            '是' => 'yes',
            '否' => 'no'
        ];
        
        // 檢查是否為中文值
        if (isset($is_charged_map[$is_charged_value])) {
            $is_charged_value = $is_charged_map[$is_charged_value];
            error_log("BYOB: is_charged_value mapped from '{$request->get_param('is_charged')}' to: " . $is_charged_value);
        } else {
            // 如果沒有匹配的值，記錄除錯資訊
            error_log("BYOB: is_charged_value not found in map: " . var_export($is_charged_value, true));
            // 如果值為空，設定預設值
            if (empty($is_charged_value)) {
                $is_charged_value = '';
                error_log("BYOB: is_charged_value is empty, setting to empty string");
            }
        }

        // 開瓶費說明
        $corkage_fee = $request->get_param('corkage_fee');
        error_log("BYOB: corkage_fee from request: " . var_export($corkage_fee, true));

        // 提供酒器設備 (checkbox 陣列)
        $equipment = $request->get_param('equipment');
        if (!empty($equipment) && !is_array($equipment)) {
            // 如果是字符串，先分割成陣列
            $equipment = array_map('trim', explode(',', $equipment));
        }
        
        // 確保 equipment 是陣列格式
        if (!is_array($equipment)) {
            $equipment = array();
        }
        
        error_log("BYOB: equipment from request: " . var_export($equipment, true));

        // 是否提供開酒服務 (轉換為 ACF 期望的值)
        $service_value = $request->get_param('open_bottle_service');
        error_log("BYOB: Raw service_value from request: " . var_export($service_value, true));
        
        // 檢查所有可能的參數名稱
        $possible_service_params = ['open_bottle_service', '是否提供開酒服務', '是否提供開酒服務？'];
        foreach ($possible_service_params as $param_name) {
            $value = $request->get_param($param_name);
            if (!empty($value)) {
                $service_value = $value;
                error_log("BYOB: Found service_value in param '{$param_name}': " . var_export($value, true));
                break;
            }
        }
        
        // 轉換中文值為 ACF 期望的值
        $service_map = [
            '有' => 'yes',
            '無' => 'no',
            '其他' => 'other',
            '是' => 'yes',
            '否' => 'no'
        ];
        
        // 檢查是否為中文值
        if (isset($service_map[$service_value])) {
            $service_value = $service_map[$service_value];
            error_log("BYOB: service_value mapped from '{$request->get_param('open_bottle_service')}' to: " . $service_value);
        } else {
            // 如果沒有匹配的值，記錄除錯資訊
            error_log("BYOB: service_value not found in map: " . var_export($service_value, true));
            // 如果值為空，設定預設值
            if (empty($service_value)) {
                $service_value = '';
                error_log("BYOB: service_value is empty, setting to empty string");
            }
        }

        // 行政區
        $district = $request->get_param('district');
        error_log("BYOB: district from request: " . var_export($district, true));

        // 是否為店主
        $is_owner = $request->get_param('is_owner');
        error_log("BYOB: is_owner from request: " . var_export($is_owner, true));

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
            // 除錯：檢查轉換後的值
            error_log("BYOB: Final is_charged_value before ACF update: " . var_export($is_charged_value, true));
            error_log("BYOB: Final service_value before ACF update: " . var_export($service_value, true));
            
            // 修正：確保所有欄位都正確對應
            $acf_updates = array(
                'contact_person' => $contact_person ?: '',
                'email' => $request->get_param('email') ?: '',
                'restaurant_type' => $types ?: array(),
                'address' => $request->get_param('address') ?: '',
                'is_charged' => $is_charged_value ?: '',
                'corkage_fee' => $corkage_fee ?: '',
                'equipment' => $equipment ?: array(),
                'open_bottle_service' => $service_value ?: '',
                'open_bottle_service_other_note' => $request->get_param('open_bottle_service_other_note') ?: '',
                'phone' => $request->get_param('phone') ?: '',
                'website' => $request->get_param('website') ?: '',
                'social_links' => $social_media_primary ?: '',
                'notes' => $request->get_param('notes') ?: '',
                'last_updated' => current_time('Y-m-d'),
                'source' => $is_owner === '是' ? '店主' : '表單填寫者',
                'is_owner' => $is_owner ?: ''
            );
            
            // 除錯：記錄 ACF 更新資料
            error_log('BYOB ACF Updates: ' . print_r($acf_updates, true));
            
            // 修正：逐個更新 ACF 欄位並記錄結果
            foreach ($acf_updates as $field_name => $field_value) {
                error_log("BYOB: Attempting to update field '{$field_name}' with value: " . var_export($field_value, true));
                
                // 直接嘗試更新，不檢查欄位是否存在（因為可能導致問題）
                $update_result = update_field($field_name, $field_value, $post_id);
                error_log("BYOB ACF Update: {$field_name} = " . var_export($field_value, true) . " (result: " . var_export($update_result, true) . ")");
                
                // 驗證更新是否成功
                if (function_exists('get_field')) {
                    $stored_value = get_field($field_name, $post_id);
                    error_log("BYOB ACF Verification - {$field_name}: " . var_export($stored_value, true));
                    
                    // 如果更新失敗，嘗試使用 set_field
                    if ($update_result === false && $stored_value !== $field_value) {
                        error_log("BYOB: update_field failed for {$field_name}, trying set_field...");
                        $set_result = set_field($field_name, $field_value, $post_id);
                        error_log("BYOB set_field result for {$field_name}: " . var_export($set_result, true));
                        
                        // 再次檢查是否成功
                        $final_value = get_field($field_name, $post_id);
                        error_log("BYOB Final check - {$field_name}: " . var_export($final_value, true));
                    }
                }
            }
            
            // 額外除錯：檢查 ACF 欄位是否真的被儲存
            if (function_exists('get_field')) {
                $stored_is_charged = get_field('is_charged', $post_id);
                $stored_open_bottle_service = get_field('open_bottle_service', $post_id);
                $stored_contact_person = get_field('contact_person', $post_id);
                $stored_equipment = get_field('equipment', $post_id);
                $stored_corkage_fee = get_field('corkage_fee', $post_id);
                $stored_district = get_field('district', $post_id);
                $stored_is_owner = get_field('is_owner', $post_id);
                
                error_log("BYOB ACF Verification - is_charged: " . var_export($stored_is_charged, true));
                error_log("BYOB ACF Verification - open_bottle_service: " . var_export($stored_open_bottle_service, true));
                error_log("BYOB ACF Verification - contact_person: " . var_export($stored_contact_person, true));
                error_log("BYOB ACF Verification - equipment: " . var_export($stored_equipment, true));
                error_log("BYOB ACF Verification - corkage_fee: " . var_export($stored_corkage_fee, true));
                error_log("BYOB ACF Verification - district: " . var_export($stored_district, true));
                error_log("BYOB ACF Verification - is_owner: " . var_export($stored_is_owner, true));
            }
        } else {
            error_log("BYOB ERROR: ACF plugin not loaded - update_field function does not exist");
        }

        // 設定地區分類
        $district = $request->get_param('district');
        if ($district) {
            wp_set_object_terms($post_id, $district, 'district');
            error_log("BYOB: Set district taxonomy: {$district}");
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
            'name' => '餐廳清單',
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
    
    // 新增除錯子頁面
    add_submenu_page(
        'byob-api-settings',
        'BYOB 除錯',
        '除錯',
        'manage_options',
        'byob-debug',
        'byob_debug_admin_page'
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

// 新增：除錯頁面函數
function byob_debug_page() {
    if (!current_user_can('administrator')) {
        wp_die('權限不足');
    }
    
    echo '<h1>BYOB 除錯頁面</h1>';
    
    // 檢查最新的餐廳文章
    $args = array(
        'post_type' => 'restaurant',
        'post_status' => array('publish', 'draft', 'pending'),
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    $restaurants = get_posts($args);
    
    foreach ($restaurants as $restaurant) {
        echo '<h2>餐廳：' . esc_html($restaurant->post_title) . ' (ID: ' . $restaurant->ID . ')</h2>';
        
        if (function_exists('get_fields')) {
            $acf_fields = get_fields($restaurant->ID);
            echo '<h3>ACF 欄位：</h3>';
            echo '<pre>' . print_r($acf_fields, true) . '</pre>';
        }
        
        echo '<hr>';
    }
}

// 新增：管理員專用的除錯頁面
function byob_debug_admin_page() {
    if (!current_user_can('administrator')) {
        wp_die('Access denied');
    }
    
    echo '<div class="wrap">';
    echo '<h1>BYOB ACF 欄位除錯</h1>';
    
    // 取得最新的餐廳文章
    $latest_restaurant = get_posts(array(
        'post_type' => 'restaurant',
        'numberposts' => 1,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    if (empty($latest_restaurant)) {
        echo '<p>沒有找到餐廳文章</p>';
        return;
    }
    
    $post_id = $latest_restaurant[0]->ID;
    echo '<p><strong>檢查的文章 ID:</strong> ' . $post_id . '</p>';
    echo '<p><strong>文章標題:</strong> ' . $latest_restaurant[0]->post_title . '</p>';
    
    // 檢查 ACF 插件是否載入
    if (!function_exists('get_field')) {
        echo '<p style="color: red;"><strong>錯誤:</strong> ACF 插件未載入</p>';
        return;
    }
    
    echo '<p style="color: green;"><strong>ACF 插件已載入：是</strong></p>';
    
    // 檢查所有相關的 ACF 欄位
    $fields_to_check = array(
        'contact_person' => '聯絡人',
        'is_charged' => '是否收開瓶費',
        'corkage_fee' => '開瓶費說明',
        'equipment' => '提供酒器設備',
        'open_bottle_service' => '是否提供開酒服務',
        'email' => '電子郵件',
        'restaurant_type' => '餐廳類型',
        'district' => '行政區',
        'address' => '地址',
        'phone' => '聯絡電話',
        'website' => '網站',
        'social_links' => '社群連結',
        'notes' => '備註',
        'is_owner' => '是否為店主'
    );
    
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>欄位名稱</th><th>顯示名稱</th><th>值</th><th>類型</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($fields_to_check as $field_name => $display_name) {
        $value = get_field($field_name, $post_id);
        $type = gettype($value);
        
        echo '<tr>';
        echo '<td>' . esc_html($field_name) . '</td>';
        echo '<td>' . esc_html($display_name) . '</td>';
        echo '<td>' . esc_html(var_export($value, true)) . '</td>';
        echo '<td>' . esc_html($type) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    
    // 檢查所有 ACF 欄位
    echo '<h2>所有 ACF 欄位</h2>';
    if (function_exists('get_fields')) {
        $all_fields = get_fields($post_id);
        echo '<pre>' . print_r($all_fields, true) . '</pre>';
    }
    
    // 檢查 ACF 欄位配置
    echo '<h2>ACF 欄位配置</h2>';
    if (function_exists('get_field_objects')) {
        $field_objects = get_field_objects($post_id);
        echo '<pre>' . print_r($field_objects, true) . '</pre>';
    }
    
    // 測試更新功能
    echo '<h2>測試 ACF 欄位更新</h2>';
    if (isset($_POST['test_update'])) {
        $test_result = byob_test_acf_update($post_id);
        echo '<pre>' . print_r($test_result, true) . '</pre>';
    }
    
    // 測試 ACF 配置
    echo '<h2>測試 ACF 配置</h2>';
    if (isset($_POST['test_config'])) {
        $config_result = byob_test_acf_configuration($post_id);
        echo '<pre>' . print_r($config_result, true) . '</pre>';
    }
    
    echo '<form method="post">';
    echo '<input type="submit" name="test_update" value="測試更新 ACF 欄位" class="button button-primary">';
    echo '<input type="submit" name="test_config" value="測試 ACF 配置" class="button button-secondary">';
    echo '</form>';
    
    echo '</div>';
}

// 新增：註冊管理員頁面
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'BYOB ACF 除錯',
        'BYOB ACF 除錯',
        'administrator',
        'byob-acf-debug',
        'byob_debug_admin_page'
    );
});

// 新增：檢查 ACF 欄位狀態的測試函數
function byob_check_acf_fields($post_id = null) {
    if (!$post_id) {
        // 如果沒有指定 post_id，取得最新的餐廳文章
        $latest_restaurant = get_posts(array(
            'post_type' => 'restaurant',
            'numberposts' => 1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($latest_restaurant)) {
            error_log('BYOB: No restaurant posts found');
            return false;
        }
        
        $post_id = $latest_restaurant[0]->ID;
    }
    
    error_log("BYOB: Checking ACF fields for post ID: {$post_id}");
    
    // 檢查所有相關的 ACF 欄位
    $fields_to_check = array(
        'contact_person',
        'is_charged',
        'corkage_fee',
        'equipment',
        'open_bottle_service',
        'email',
        'restaurant_type',
        'district',
        'address',
        'phone',
        'website',
        'social_links',
        'notes',
        'is_owner'
    );
    
    $results = array();
    foreach ($fields_to_check as $field_name) {
        $value = get_field($field_name, $post_id);
        $results[$field_name] = $value;
        error_log("BYOB ACF Check - {$field_name}: " . var_export($value, true));
    }
    
    return $results;
}

// 新增：手動測試 ACF 欄位更新
function byob_test_acf_update($post_id = null) {
    if (!$post_id) {
        $latest_restaurant = get_posts(array(
            'post_type' => 'restaurant',
            'numberposts' => 1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($latest_restaurant)) {
            error_log('BYOB: No restaurant posts found for testing');
            return false;
        }
        
        $post_id = $latest_restaurant[0]->ID;
    }
    
    error_log("BYOB: Testing ACF update for post ID: {$post_id}");
    
    // 測試更新關鍵欄位
    $test_data = array(
        'contact_person' => '測試聯絡人',
        'is_charged' => 'yes',
        'corkage_fee' => '測試開瓶費說明',
        'equipment' => array('酒杯', '開瓶器'),
        'open_bottle_service' => 'yes'
    );
    
    $results = array();
    foreach ($test_data as $field_name => $field_value) {
        $update_result = update_field($field_name, $field_value, $post_id);
        $stored_value = get_field($field_name, $post_id);
        
        $results[$field_name] = array(
            'update_result' => $update_result,
            'stored_value' => $stored_value,
            'expected_value' => $field_value
        );
        
        error_log("BYOB Test Update - {$field_name}: update_result=" . var_export($update_result, true) . ", stored_value=" . var_export($stored_value, true));
    }
    
    return $results;
}

// 新增：手動測試 ACF 欄位更新
function byob_manual_test_acf() {
    if (!current_user_can('manage_options')) {
        wp_die('權限不足');
    }
    
    // 取得最新的餐廳文章
    $args = array(
        'post_type' => 'restaurant',
        'post_status' => array('publish', 'draft', 'pending'),
        'posts_per_page' => 1,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    $restaurants = get_posts($args);
    
    if (empty($restaurants)) {
        echo '<p>沒有找到餐廳文章。</p>';
        return;
    }
    
    $restaurant = $restaurants[0];
    echo '<h2>測試餐廳：' . esc_html($restaurant->post_title) . ' (ID: ' . $restaurant->ID . ')</h2>';
    
    // 測試更新 ACF 欄位
    $test_result = byob_test_acf_update($restaurant->ID);
    
    echo '<h3>測試結果：</h3>';
    echo '<pre>' . print_r($test_result, true) . '</pre>';
    
    // 檢查更新後的欄位
    if (function_exists('get_fields')) {
        $acf_fields = get_fields($restaurant->ID);
        echo '<h3>更新後的 ACF 欄位：</h3>';
        echo '<pre>' . print_r($acf_fields, true) . '</pre>';
    }
}

// 新增：測試 WordPress API 連接
function byob_test_api_connection() {
    if (!current_user_can('manage_options')) {
        wp_die('權限不足');
    }
    
    echo '<h2>測試 WordPress API 連接</h2>';
    
    // 測試資料
    $test_data = array(
        'restaurant_name' => 'API 測試餐廳 - ' . date('Y-m-d H:i:s'),
        'contact_person' => '測試聯絡人',
        'email' => 'test@example.com',
        'restaurant_type' => '中式',
        'district' => '台北市',
        'address' => '測試地址',
        'is_charged' => '酌收',
        'corkage_fee' => '測試開瓶費',
        'equipment' => '酒杯, 開瓶器',
        'open_bottle_service' => '有',
        'phone' => '02-12345678',
        'website' => 'https://test.com',
        'social_media' => 'https://instagram.com/test',
        'notes' => 'API 測試備註',
        'is_owner' => '是'
    );
    
    echo '<h3>測試資料：</h3>';
    echo '<pre>' . print_r($test_data, true) . '</pre>';
    
    // 模擬 API 請求
    $request = new WP_REST_Request('POST', '/byob/v1/restaurant');
    foreach ($test_data as $key => $value) {
        $request->set_param($key, $value);
    }
    
    // 設定 API 金鑰
    $request->add_header('X-API-Key', 'byob-secret-key-2025');
    
    // 執行請求
    $response = byob_create_restaurant_post($request);
    
    echo '<h3>API 回應：</h3>';
    echo '<pre>' . print_r($response, true) . '</pre>';
    
    if (is_wp_error($response)) {
        echo '<p style="color: red;">❌ API 測試失敗：' . $response->get_error_message() . '</p>';
    } else {
        echo '<p style="color: green;">✅ API 測試成功！</p>';
        echo '<p>建立的文章 ID：' . $response['post_id'] . '</p>';
        echo '<p>文章網址：<a href="' . $response['post_url'] . '" target="_blank">' . $response['post_url'] . '</a></p>';
    }
}

// 新增：檢查 Google 表單資料
function byob_check_google_form_data() {
    if (!current_user_can('manage_options')) {
        wp_die('權限不足');
    }
    
    echo '<h2>檢查 Google 表單資料</h2>';
    
    // 取得最新的餐廳文章
    $args = array(
        'post_type' => 'restaurant',
        'post_status' => array('publish', 'draft', 'pending'),
        'posts_per_page' => 3,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    $restaurants = get_posts($args);
    
    if (empty($restaurants)) {
        echo '<p>沒有找到餐廳文章。</p>';
        return;
    }
    
    foreach ($restaurants as $restaurant) {
        echo '<div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">';
        echo '<h3>餐廳：' . esc_html($restaurant->post_title) . ' (ID: ' . $restaurant->ID . ')</h3>';
        echo '<p><strong>建立時間：</strong>' . esc_html($restaurant->post_date) . '</p>';
        
        // 檢查 ACF 欄位
        if (function_exists('get_fields')) {
            $acf_fields = get_fields($restaurant->ID);
            echo '<h4>ACF 欄位：</h4>';
            echo '<div style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd;">';
            echo '<pre style="white-space: pre-wrap;">' . esc_html(print_r($acf_fields, true)) . '</pre>';
            echo '</div>';
            
            // 特別檢查問題欄位
            echo '<h4>問題欄位檢查：</h4>';
            echo '<ul>';
            echo '<li><strong>is_charged:</strong> ' . (isset($acf_fields['is_charged']) ? var_export($acf_fields['is_charged'], true) : '未設定') . '</li>';
            echo '<li><strong>open_bottle_service:</strong> ' . (isset($acf_fields['open_bottle_service']) ? var_export($acf_fields['open_bottle_service'], true) : '未設定') . '</li>';
            echo '</ul>';
        }
        
        echo '</div>';
    }
}

// 新增：檢查所有可用的 ACF 欄位
function byob_check_all_acf_fields($post_id = null) {
    if (!$post_id) {
        $latest_restaurant = get_posts(array(
            'post_type' => 'restaurant',
            'numberposts' => 1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($latest_restaurant)) {
            error_log('BYOB: No restaurant posts found');
            return false;
        }
        
        $post_id = $latest_restaurant[0]->ID;
    }
    
    error_log("BYOB: Checking all ACF fields for post ID: {$post_id}");
    
    if (!function_exists('get_fields')) {
        error_log('BYOB ERROR: ACF plugin not loaded - get_fields function does not exist');
        return false;
    }
    
    // 取得所有 ACF 欄位
    $all_fields = get_fields($post_id);
    error_log("BYOB All ACF fields: " . print_r($all_fields, true));
    
    return $all_fields;
}

// 新增：檢查 ACF 欄位的實際名稱和狀態
function byob_debug_acf_field_names($post_id = null) {
    if (!$post_id) {
        $latest_restaurant = get_posts(array(
            'post_type' => 'restaurant',
            'numberposts' => 1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($latest_restaurant)) {
            error_log('BYOB: No restaurant posts found');
            return false;
        }
        
        $post_id = $latest_restaurant[0]->ID;
    }
    
    error_log("BYOB: Debugging ACF field names for post ID: {$post_id}");
    
    if (!function_exists('get_fields')) {
        error_log('BYOB ERROR: ACF plugin not loaded - get_fields function does not exist');
        return false;
    }
    
    // 取得所有 ACF 欄位
    $all_fields = get_fields($post_id);
    error_log("BYOB All ACF fields: " . print_r($all_fields, true));
    
    // 檢查每個欄位的詳細資訊
    if (function_exists('get_field_objects')) {
        $field_objects = get_field_objects($post_id);
        error_log("BYOB Field objects: " . print_r($field_objects, true));
    }
    
    return $all_fields;
}

// 新增：測試 ACF 欄位配置
function byob_test_acf_configuration($post_id = null) {
    if (!$post_id) {
        $latest_restaurant = get_posts(array(
            'post_type' => 'restaurant',
            'numberposts' => 1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($latest_restaurant)) {
            error_log('BYOB: No restaurant posts found');
            return false;
        }
        
        $post_id = $latest_restaurant[0]->ID;
    }
    
    error_log("BYOB: Testing ACF configuration for post ID: {$post_id}");
    
    if (!function_exists('get_fields')) {
        error_log('BYOB ERROR: ACF plugin not loaded - get_fields function does not exist');
        return false;
    }
    
    // 檢查所有 ACF 欄位
    $all_fields = get_fields($post_id);
    error_log("BYOB All ACF fields: " . print_r($all_fields, true));
    
    // 檢查 ACF 欄位配置
    if (function_exists('get_field_objects')) {
        $field_objects = get_field_objects($post_id);
        error_log("BYOB Field objects: " . print_r($field_objects, true));
    }
    
    // 測試更新一個簡單的欄位
    $test_field = 'contact_person';
    $test_value = '測試聯絡人_' . time();
    
    error_log("BYOB: Testing update_field for '{$test_field}' with value '{$test_value}'");
    $update_result = update_field($test_field, $test_value, $post_id);
    error_log("BYOB Test update result: " . var_export($update_result, true));
    
    // 檢查更新後的結果
    $stored_value = get_field($test_field, $post_id);
    error_log("BYOB Test stored value: " . var_export($stored_value, true));
    
    return array(
        'all_fields' => $all_fields,
        'field_objects' => isset($field_objects) ? $field_objects : null,
        'test_update_result' => $update_result,
        'test_stored_value' => $stored_value
    );
}
