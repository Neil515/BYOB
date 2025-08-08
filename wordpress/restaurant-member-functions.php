<?php
/**
 * 餐廳業者會員系統功能
 * 
 * 主要功能：
 * 1. 邀請碼系統
 * 2. 餐廳業者註冊流程
 * 3. 餐廳管理權限
 * 4. 會員管理介面
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 初始化餐廳會員系統
 */
function byob_init_restaurant_member_system() {
    // 註冊自定義使用者角色
    byob_register_restaurant_owner_role();
    
    // 註冊 REST API 端點
    add_action('rest_api_init', 'byob_register_member_api_endpoints');
    
    // 處理邀請碼驗證
    add_action('init', 'byob_handle_invitation_verification');
    
    // 新增前端會員介面
    add_action('wp_enqueue_scripts', 'byob_enqueue_member_scripts');
    
    // 新增邀請碼註冊頁面
    add_action('init', 'byob_add_rewrite_rules');
    add_action('template_redirect', 'byob_handle_restaurant_registration_page');
    
    // 註冊限制存取功能
    add_action('init', 'byob_restrict_restaurant_owner_access');
    
    // 註冊存取控制
    add_action('admin_init', 'byob_restrict_admin_access');
}

/**
 * 註冊餐廳業者角色
 */
function byob_register_restaurant_owner_role() {
    // 檢查角色是否已存在
    if (!get_role('restaurant_owner')) {
        add_role('restaurant_owner', '餐廳業者', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'edit_restaurant' => true, // 自定義權限
            'edit_own_restaurant' => true, // 編輯自己的餐廳
            'upload_files' => true, // 上傳檔案
            'read_restaurant_stats' => true, // 查看餐廳統計
        ));
    }
}

/**
 * 註冊會員相關 REST API 端點
 */
function byob_register_member_api_endpoints() {
    // 驗證邀請碼
    register_rest_route('byob/v1', '/verify-invitation', array(
        'methods' => 'POST',
        'callback' => 'byob_verify_invitation_code',
        'permission_callback' => '__return_true',
    ));
    
    // 餐廳業者註冊
    register_rest_route('byob/v1', '/register-restaurant-owner', array(
        'methods' => 'POST',
        'callback' => 'byob_register_restaurant_owner',
        'permission_callback' => '__return_true',
    ));
    
    // 獲取餐廳管理資料
    register_rest_route('byob/v1', '/restaurant-management/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'byob_get_restaurant_management_data',
        'permission_callback' => 'byob_check_restaurant_owner_permission',
    ));
    
    // 更新餐廳資料
    register_rest_route('byob/v1', '/restaurant-management/(?P<id>\d+)', array(
        'methods' => 'PUT',
        'callback' => 'byob_update_restaurant_data',
        'permission_callback' => 'byob_check_restaurant_owner_permission',
    ));
}

/**
 * 生成邀請碼
 */
function byob_generate_invitation_code($restaurant_id) {
    $code = wp_generate_password(12, false);
    $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    // 儲存邀請碼到資料庫
    $invitation_data = array(
        'code' => $code,
        'restaurant_id' => $restaurant_id,
        'expires' => $expires,
        'used' => false,
        'created' => current_time('mysql')
    );
    
    update_post_meta($restaurant_id, '_byob_invitation_code', $invitation_data);
    
    return $code;
}

/**
 * 驗證邀請碼
 */
function byob_verify_invitation_code($request) {
    $code = sanitize_text_field($request->get_param('code'));
    
    if (empty($code)) {
        return new WP_Error('invalid_code', '邀請碼不能為空', array('status' => 400));
    }
    
    // 查詢邀請碼
    global $wpdb;
    $meta_key = '_byob_invitation_code';
    $query = $wpdb->prepare(
        "SELECT post_id, meta_value FROM {$wpdb->postmeta} 
         WHERE meta_key = %s AND meta_value LIKE %s",
        $meta_key,
        '%' . $wpdb->esc_like($code) . '%'
    );
    
    $result = $wpdb->get_row($query);
    
    if (!$result) {
        return new WP_Error('invalid_code', '邀請碼無效', array('status' => 404));
    }
    
    $invitation_data = maybe_unserialize($result->meta_value);
    
    // 檢查是否已使用
    if ($invitation_data['used']) {
        return new WP_Error('code_used', '邀請碼已使用', array('status' => 400));
    }
    
    // 檢查是否過期
    if (strtotime($invitation_data['expires']) < time()) {
        return new WP_Error('code_expired', '邀請碼已過期', array('status' => 400));
    }
    
    // 獲取餐廳資訊
    $restaurant = get_post($result->post_id);
    if (!$restaurant || $restaurant->post_type !== 'restaurant') {
        return new WP_Error('restaurant_not_found', '餐廳不存在', array('status' => 404));
    }
    
    return array(
        'success' => true,
        'restaurant_id' => $result->post_id,
        'restaurant_name' => $restaurant->post_title,
        'invitation_code' => $code
    );
}

/**
 * 餐廳業者註冊
 */
function byob_register_restaurant_owner($request) {
    $invitation_code = sanitize_text_field($request->get_param('invitation_code'));
    $email = sanitize_email($request->get_param('email'));
    $password = $request->get_param('password');
    $restaurant_name = sanitize_text_field($request->get_param('restaurant_name'));
    
    // 驗證邀請碼
    $verification = byob_verify_invitation_code(new WP_REST_Request('POST', '', array('code' => $invitation_code)));
    if (is_wp_error($verification)) {
        return $verification;
    }
    
    // 檢查 email 是否已存在
    $existing_user = get_user_by('email', $email);
    if ($existing_user) {
        return new WP_Error('email_exists', '此 email 已被註冊', array('status' => 400));
    }
    
    // 建立使用者
    $user_data = array(
        'user_login' => $email,
        'user_email' => $email,
        'user_pass' => $password,
        'role' => 'restaurant_owner',
        'display_name' => $restaurant_name . ' 負責人'
    );
    
    $user_id = wp_insert_user($user_data);
    
    if (is_wp_error($user_id)) {
        return $user_id;
    }
    
    // 關聯餐廳與使用者
    update_post_meta($verification['restaurant_id'], '_restaurant_owner_id', $user_id);
    update_user_meta($user_id, '_owned_restaurant_id', $verification['restaurant_id']);
    
    // 標記邀請碼為已使用
    $invitation_data = get_post_meta($verification['restaurant_id'], '_byob_invitation_code', true);
    $invitation_data['used'] = true;
    $invitation_data['used_by'] = $user_id;
    $invitation_data['used_at'] = current_time('mysql');
    update_post_meta($verification['restaurant_id'], '_byob_invitation_code', $invitation_data);
    
    // 自動登入
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    return array(
        'success' => true,
        'user_id' => $user_id,
        'restaurant_id' => $verification['restaurant_id'],
        'message' => '註冊成功！'
    );
}

/**
 * 檢查餐廳業者權限
 */
function byob_check_restaurant_owner_permission($request) {
    // 檢查使用者是否已登入
    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }
    
    // 檢查使用者是否為餐廳業者角色
    $user = get_user_by('id', $user_id);
    if (!in_array('restaurant_owner', $user->roles)) {
        return false;
    }
    
    // 檢查使用者是否擁有該餐廳
    $restaurant_id = $request->get_param('id');
    $owner_restaurant_id = get_post_meta($restaurant_id, '_restaurant_owner_id', true);
    
    // 如果餐廳沒有擁有者，拒絕存取
    if (!$owner_restaurant_id) {
        return false;
    }
    
    return $owner_restaurant_id == $user_id;
}

/**
 * 獲取使用者擁有的餐廳
 */
function byob_get_user_restaurants($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return array();
    }
    
    // 查詢該使用者擁有的所有餐廳
    $restaurants = get_posts(array(
        'post_type' => 'restaurant',
        'numberposts' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_restaurant_owner_id',
                'value' => $user_id,
                'compare' => '='
            )
        )
    ));
    
    return $restaurants;
}

/**
 * 檢查使用者是否可以存取餐廳
 */
function byob_can_user_access_restaurant($user_id, $restaurant_id) {
    // 檢查使用者是否為餐廳業者
    $user = get_user_by('id', $user_id);
    if (!in_array('restaurant_owner', $user->roles)) {
        return false;
    }
    
    // 檢查使用者是否擁有該餐廳
    $owner_restaurant_id = get_post_meta($restaurant_id, '_restaurant_owner_id', true);
    
    return $owner_restaurant_id == $user_id;
}

/**
 * 獲取餐廳管理資料
 */
function byob_get_restaurant_management_data($request) {
    $restaurant_id = $request->get_param('id');
    $restaurant = get_post($restaurant_id);
    
    if (!$restaurant || $restaurant->post_type !== 'restaurant') {
        return new WP_Error('restaurant_not_found', '餐廳不存在', array('status' => 404));
    }
    
    // 獲取 ACF 欄位資料
    $acf_fields = array();
    if (function_exists('get_fields')) {
        $acf_fields = get_fields($restaurant_id);
    }
    
    // 獲取統計資料
    $stats = array(
        'views' => get_post_meta($restaurant_id, '_view_count', true) ?: 0,
        'favorites' => get_post_meta($restaurant_id, '_favorite_count', true) ?: 0,
        'last_updated' => get_post_meta($restaurant_id, 'last_updated', true) ?: ''
    );
    
    return array(
        'restaurant_id' => $restaurant_id,
        'restaurant_name' => $restaurant->post_title,
        'acf_fields' => $acf_fields,
        'stats' => $stats,
        'edit_url' => get_edit_post_link($restaurant_id, 'raw')
    );
}

/**
 * 更新餐廳資料
 */
function byob_update_restaurant_data($request) {
    $restaurant_id = $request->get_param('id');
    $restaurant = get_post($restaurant_id);
    
    if (!$restaurant || $restaurant->post_type !== 'restaurant') {
        return new WP_Error('restaurant_not_found', '餐廳不存在', array('status' => 404));
    }
    
    // 更新基本資料
    $post_data = array(
        'ID' => $restaurant_id,
        'post_title' => sanitize_text_field($request->get_param('restaurant_name')),
        'post_content' => sanitize_textarea_field($request->get_param('notes'))
    );
    
    $updated_post = wp_update_post($post_data);
    
    if (is_wp_error($updated_post)) {
        return $updated_post;
    }
    
    // 更新 ACF 欄位
    if (function_exists('update_field')) {
        $acf_fields = array(
            'contact_person' => sanitize_text_field($request->get_param('contact_person')),
            'address' => sanitize_textarea_field($request->get_param('address')),
            'phone' => sanitize_text_field($request->get_param('phone')),
            'website' => esc_url_raw($request->get_param('website')),
            'is_charged' => sanitize_text_field($request->get_param('is_charged')),
            'corkage_fee' => sanitize_text_field($request->get_param('corkage_fee')),
            'equipment' => $request->get_param('equipment'),
            'open_bottle_service' => sanitize_text_field($request->get_param('open_bottle_service')),
            'open_bottle_service_other_note' => sanitize_text_field($request->get_param('open_bottle_service_other_note')),
            'social_media' => sanitize_text_field($request->get_param('social_media')),
            'notes' => sanitize_textarea_field($request->get_param('notes')),
            'last_updated' => current_time('Y-m-d')
        );
        
        foreach ($acf_fields as $field_name => $field_value) {
            update_field($field_name, $field_value, $restaurant_id);
        }
    }
    
    // 更新最後修改時間
    update_post_meta($restaurant_id, 'last_updated', current_time('Y-m-d'));
    
    return array(
        'success' => true,
        'message' => '餐廳資料更新成功！',
        'restaurant_id' => $restaurant_id
    );
}

/**
 * 發送會員邀請郵件
 */
function byob_send_member_invitation_email($restaurant_id) {
    $restaurant = get_post($restaurant_id);
    if (!$restaurant || $restaurant->post_type !== 'restaurant') {
        return false;
    }
    
    // 生成邀請碼
    $invitation_code = byob_generate_invitation_code($restaurant_id);
    
    // 獲取餐廳聯絡資訊
    $contact_email = get_field('email', $restaurant_id);
    $contact_person = get_field('contact_person', $restaurant_id);
    
    if (!$contact_email) {
        return false;
    }
    
    // 建立邀請連結
    $invitation_url = home_url('/register/restaurant?token=' . $invitation_code);
    
    // 郵件內容
    $subject = '歡迎加入 BYOB 餐廳地圖 - 您的餐廳已成功上架！';
    
    $message = '
    <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
        <div style="background-color: #8b2635; color: white; padding: 20px; text-align: center;">
            <h1>BYOB 台北餐廳地圖</h1>
        </div>
        
        <div style="padding: 20px; background-color: #f9f9f9;">
            <h2>親愛的 ' . ($contact_person ?: $restaurant->post_title . ' 負責人') . '，</h2>
            
            <p>恭喜您的餐廳已成功加入台北 BYOB 餐廳地圖！</p>
            
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

/**
 * 處理邀請碼驗證
 */
function byob_handle_invitation_verification() {
    if (isset($_GET['token']) && isset($_GET['page']) && $_GET['page'] === 'register') {
        // 這裡可以加入邀請碼驗證邏輯
        // 並重定向到註冊頁面
    }
}

/**
 * 載入會員相關腳本
 */
function byob_enqueue_member_scripts() {
    if (is_page('register') || is_page('member-dashboard')) {
        wp_enqueue_script('byob-member', get_template_directory_uri() . '/js/member.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('byob-member', get_template_directory_uri() . '/css/member.css', array(), '1.0.0');
    }
}

/**
 * 限制餐廳業者後台存取
 */
function byob_restrict_admin_access() {
    // 只在後台執行
    if (!is_admin()) {
        return;
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        return;
    }
    
    $user = get_user_by('id', $user_id);
    if (!in_array('restaurant_owner', $user->roles)) {
        return;
    }
    
    // 獲取當前頁面
    $current_screen = get_current_screen();
    
    // 如果是餐廳列表頁面，限制只能看到自己的餐廳
    if ($current_screen && $current_screen->post_type === 'restaurant') {
        // 已經在 byob_restrict_restaurant_owner_access() 中處理
        return;
    }
    
    // 如果是其他頁面，檢查是否有權限
    $allowed_pages = array(
        'profile',
        'profile.php',
        'user-edit.php'
    );
    
    $current_page = $_GET['page'] ?? '';
    $current_action = $_GET['action'] ?? '';
    
    // 允許存取個人資料頁面
    if (in_array($current_page, $allowed_pages) || $current_action === 'edit') {
        return;
    }
    
    // 如果是餐廳業者，重定向到自己的儀表板
    if (!in_array($current_page, $allowed_pages)) {
        wp_redirect(admin_url('admin.php?page=restaurant-owner-dashboard'));
        exit;
    }
}

/**
 * 限制餐廳業者只能看到自己的餐廳
 */
function byob_restrict_restaurant_owner_access() {
    // 只在後台執行
    if (!is_admin()) {
        return;
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        return;
    }
    
    $user = get_user_by('id', $user_id);
    if (!in_array('restaurant_owner', $user->roles)) {
        return;
    }
    
    // 如果是餐廳業者，限制只能看到自己的餐廳
    add_action('pre_get_posts', function($query) use ($user_id) {
        if ($query->get('post_type') === 'restaurant' && is_admin()) {
            $query->set('meta_query', array(
                array(
                    'key' => '_restaurant_owner_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ));
        }
    });
}

/**
 * 審核餐廳資料
 */
function byob_review_restaurant($restaurant_id, $status, $review_notes = '') {
    $restaurant = get_post($restaurant_id);
    if (!$restaurant || $restaurant->post_type !== 'restaurant') {
        return new WP_Error('restaurant_not_found', '餐廳不存在');
    }
    
    $contact_email = get_field('email', $restaurant_id);
    $contact_person = get_field('contact_person', $restaurant_id);
    
    if ($status === 'approved') {
        // 審核通過
        $post_data = array(
            'ID' => $restaurant_id,
            'post_status' => 'publish'
        );
        
        $result = wp_update_post($post_data);
        if (is_wp_error($result)) {
            return $result;
        }
        
        // 更新審核狀態
        update_field('review_status', 'approved', $restaurant_id);
        update_field('review_date', current_time('mysql'), $restaurant_id);
        update_field('review_notes', $review_notes, $restaurant_id);
        
        // 發送審核通過通知和邀請郵件
        byob_send_approval_notification($restaurant_id);
        
        // 記錄審核日誌
        byob_log_review_action($restaurant_id, 'approved', $review_notes);
        
        return array(
            'success' => true,
            'message' => '餐廳已審核通過並發布',
            'invitation_code' => $invitation_data['code'] ?? null
        );
        
    } elseif ($status === 'rejected') {
        // 審核未通過
        update_field('review_status', 'rejected', $restaurant_id);
        update_field('review_date', current_time('mysql'), $restaurant_id);
        update_field('review_notes', $review_notes, $restaurant_id);
        
        // 發送審核未通過通知
        byob_send_rejection_notification($restaurant_id, $review_notes);
        
        return array(
            'success' => true,
            'message' => '餐廳審核未通過，已通知業者'
        );
    }
    
    return new WP_Error('invalid_status', '無效的審核狀態');
}

/**
 * 記錄審核操作
 */
function byob_log_review_action($restaurant_id, $action, $notes = '') {
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'restaurant_id' => $restaurant_id,
        'action' => $action,
        'notes' => $notes,
        'reviewer_id' => get_current_user_id(),
        'reviewer_name' => wp_get_current_user()->display_name
    );
    
    $logs = get_option('byob_review_logs', array());
    $logs[] = $log_entry;
    
    // 只保留最近100筆記錄
    if (count($logs) > 100) {
        $logs = array_slice($logs, -100);
    }
    
    update_option('byob_review_logs', $logs);
}

/**
 * 新增重寫規則
 */
function byob_add_rewrite_rules() {
    add_rewrite_rule(
        'register/restaurant/?$',
        'index.php?byob_restaurant_registration=1',
        'top'
    );
}

/**
 * 處理餐廳註冊頁面
 */
function byob_handle_restaurant_registration_page() {
    if (get_query_var('byob_restaurant_registration')) {
        byob_display_restaurant_registration_page();
        exit;
    }
}

/**
 * 顯示餐廳註冊頁面
 */
function byob_display_restaurant_registration_page() {
    $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
    $error_message = '';
    $success_message = '';
    $restaurant_info = null;
    
    // 如果有邀請碼，先驗證
    if ($token) {
        $verification = byob_verify_invitation_code(new WP_REST_Request('POST', '', array('code' => $token)));
        if (!is_wp_error($verification)) {
            $restaurant_info = $verification;
        } else {
            $error_message = $verification->get_error_message();
        }
    }
    
    // 處理註冊表單提交
    if ($_POST && isset($_POST['byob_restaurant_register'])) {
        $result = byob_register_restaurant_owner(new WP_REST_Request('POST', '', $_POST));
        if (is_wp_error($result)) {
            $error_message = $result->get_error_message();
        } else {
            $success_message = '註冊成功！您現在可以登入管理您的餐廳了。';
        }
    }
    
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>餐廳業者註冊 - <?php bloginfo('name'); ?></title>
        <?php wp_head(); ?>
    </head>
    <body <?php body_class(); ?>>
        <div class="byob-registration-page" style="max-width: 600px; margin: 50px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #8b2635;">BYOB 餐廳業者註冊</h1>
                <p>歡迎加入 BYOB 台北餐廳地圖！</p>
            </div>
            
            <?php if ($error_message): ?>
                <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                    <?php echo esc_html($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                    <?php echo esc_html($success_message); ?>
                    <br><br>
                    <a href="<?php echo wp_login_url(); ?>" style="background-color: #8b2635; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">前往登入</a>
                </div>
            <?php endif; ?>
            
            <?php if ($restaurant_info && !$success_message): ?>
                <div style="background-color: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                    <h3>餐廳資訊</h3>
                    <p><strong>餐廳名稱：</strong><?php echo esc_html($restaurant_info['restaurant_name']); ?></p>
                    <p><strong>邀請碼：</strong><?php echo esc_html($restaurant_info['invitation_code']); ?></p>
                </div>
                
                <form method="post" style="margin-top: 20px;">
                    <input type="hidden" name="invitation_code" value="<?php echo esc_attr($token); ?>">
                    <input type="hidden" name="restaurant_name" value="<?php echo esc_attr($restaurant_info['restaurant_name']); ?>">
                    
                    <div style="margin-bottom: 15px;">
                        <label for="email" style="display: block; margin-bottom: 5px; font-weight: bold;">Email 地址 *</label>
                        <input type="email" id="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="password" style="display: block; margin-bottom: 5px; font-weight: bold;">密碼 *</label>
                        <input type="password" id="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="confirm_password" style="display: block; margin-bottom: 5px; font-weight: bold;">確認密碼 *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    
                    <button type="submit" name="byob_restaurant_register" style="width: 100%; background-color: #8b2635; color: white; padding: 15px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
                        完成註冊
                    </button>
                </form>
            <?php elseif (!$token): ?>
                <div style="text-align: center; padding: 40px 20px;">
                    <h3>請使用有效的邀請碼</h3>
                    <p>您需要有效的邀請碼才能註冊餐廳業者帳號。</p>
                    <p>如果您有邀請碼，請將邀請碼加入網址後方：</p>
                    <code style="background-color: #f5f5f5; padding: 10px; display: block; margin: 20px 0; border-radius: 5px;">
                        <?php echo home_url('/register/restaurant?token=您的邀請碼'); ?>
                    </code>
                </div>
            <?php endif; ?>
        </div>
        
        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
}

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
    $subject = '🎉 恭喜！您的餐廳已通過審核並上架 - BYOB 台北餐廳地圖';
    
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

/**
 * 發送審核未通過通知
 */
function byob_send_rejection_notification($restaurant_id, $review_notes = '') {
    $restaurant = get_post($restaurant_id);
    $contact_email = get_field('email', $restaurant_id);
    $contact_person = get_field('contact_person', $restaurant_id);
    
    if (!$contact_email) {
        return false;
    }
    
    // 郵件內容
    $subject = '關於您的餐廳申請 - BYOB 台北餐廳地圖';
    
    $message = '
    <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
        <div style="background-color: #8b2635; color: white; padding: 20px; text-align: center;">
            <h1>BYOB 台北餐廳地圖</h1>
        </div>
        
        <div style="padding: 20px; background-color: #f9f9f9;">
            <h2>親愛的 ' . ($contact_person ?: $restaurant->post_title . ' 負責人') . '，</h2>
            
            <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <h3 style="color: #721c24; margin: 0;">很抱歉，您的餐廳申請未能通過審核</h3>
            </div>
            
            <p>感謝您對 BYOB 台北餐廳地圖的支持。經過我們的審核，很抱歉您的餐廳申請目前無法通過。</p>
            
            ' . ($review_notes ? '<div style="background-color: white; padding: 15px; margin: 20px 0; border-left: 4px solid #dc3545;">
                <strong>審核意見：</strong><br>
                ' . nl2br(esc_html($review_notes)) . '
            </div>' : '') . '
            
            <p>如果您認為這是一個誤會，或者您已經解決了相關問題，歡迎重新提交申請。</p>
            
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

/**
 * 新增審核管理選單
 */
function byob_add_review_management_menu() {
    add_submenu_page(
        'edit.php?post_type=restaurant',
        '審核管理',
        '審核管理',
        'manage_options',
        'byob-review-management',
        'byob_review_management_page'
    );
}

/**
 * 審核管理頁面
 */
function byob_review_management_page() {
    if (isset($_POST['action']) && isset($_POST['restaurant_id'])) {
        $restaurant_id = intval($_POST['restaurant_id']);
        $action = $_POST['action'];
        $review_notes = sanitize_textarea_field($_POST['review_notes'] ?? '');
        
        if ($action === 'approve') {
            $result = byob_review_restaurant($restaurant_id, 'approved', $review_notes);
        } elseif ($action === 'reject') {
            $result = byob_review_restaurant($restaurant_id, 'rejected', $review_notes);
        }
        
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>操作失敗：' . $result->get_error_message() . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>' . $result['message'] . '</p></div>';
        }
    }
    
    ?>
    <div class="wrap">
        <h1>BYOB 餐廳審核管理</h1>
        
        <h2>待審核餐廳</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>餐廳名稱</th>
                    <th>聯絡人</th>
                    <th>Email</th>
                    <th>提交日期</th>
                    <th>狀態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pending_restaurants = get_posts(array(
                    'post_type' => 'restaurant',
                    'numberposts' => -1,
                    'post_status' => 'draft',
                    'meta_query' => array(
                        array(
                            'key' => 'review_status',
                            'value' => 'pending',
                            'compare' => '='
                        )
                    )
                ));
                
                if (empty($pending_restaurants)) {
                    echo '<tr><td colspan="6">目前沒有待審核的餐廳</td></tr>';
                } else {
                    foreach ($pending_restaurants as $restaurant) {
                        $contact_person = get_field('contact_person', $restaurant->ID);
                        $email = get_field('email', $restaurant->ID);
                        $submitted_date = get_field('submitted_date', $restaurant->ID);
                        
                        echo '<tr>';
                        echo '<td><a href="' . get_edit_post_link($restaurant->ID) . '">' . $restaurant->post_title . '</a></td>';
                        echo '<td>' . ($contact_person ?: '未填寫') . '</td>';
                        echo '<td>' . ($email ?: '未填寫') . '</td>';
                        echo '<td>' . ($submitted_date ? date('Y-m-d H:i', strtotime($submitted_date)) : '未知') . '</td>';
                        echo '<td><span style="color: orange;">待審核</span></td>';
                        echo '<td>';
                        echo '<form method="post" style="display: inline;">';
                        echo '<input type="hidden" name="restaurant_id" value="' . $restaurant->ID . '">';
                        echo '<input type="hidden" name="action" value="approve">';
                        echo '<textarea name="review_notes" placeholder="審核意見（可選）" style="width: 200px; height: 60px;"></textarea><br>';
                        echo '<button type="submit" class="button button-primary" onclick="return confirm(\'確定要通過審核嗎？\')">通過審核</button> ';
                        echo '</form>';
                        
                        echo '<form method="post" style="display: inline;">';
                        echo '<input type="hidden" name="restaurant_id" value="' . $restaurant->ID . '">';
                        echo '<input type="hidden" name="action" value="reject">';
                        echo '<textarea name="review_notes" placeholder="拒絕原因（必填）" style="width: 200px; height: 60px;" required></textarea><br>';
                        echo '<button type="submit" class="button button-secondary" onclick="return confirm(\'確定要拒絕審核嗎？\')">拒絕審核</button>';
                        echo '</form>';
                        echo '</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * 會員管理頁面
 */
function byob_member_management_page() {
    ?>
    <div class="wrap">
        <h1>BYOB 會員管理</h1>
        
        <h2>餐廳業者會員</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>餐廳名稱</th>
                    <th>聯絡人</th>
                    <th>Email</th>
                    <th>會員狀態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $restaurants = get_posts(array(
                    'post_type' => 'restaurant',
                    'numberposts' => -1,
                    'post_status' => 'publish'
                ));
                
                foreach ($restaurants as $restaurant) {
                    $owner_id = get_post_meta($restaurant->ID, '_restaurant_owner_id', true);
                    $contact_person = get_field('contact_person', $restaurant->ID);
                    $email = get_field('email', $restaurant->ID);
                    
                    if ($owner_id) {
                        $owner = get_userdata($owner_id);
                        $member_status = '已註冊';
                        $action = '<a href="' . admin_url('user-edit.php?user_id=' . $owner_id) . '">查看會員資料</a>';
                    } else {
                        $member_status = '未註冊';
                        $action = '<button onclick="sendInvitation(' . $restaurant->ID . ')">發送邀請</button>';
                    }
                    
                    echo '<tr>';
                    echo '<td>' . $restaurant->post_title . '</td>';
                    echo '<td>' . ($contact_person ?: '未填寫') . '</td>';
                    echo '<td>' . ($email ?: '未填寫') . '</td>';
                    echo '<td>' . $member_status . '</td>';
                    echo '<td>' . $action . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <script>
    function sendInvitation(restaurantId) {
        if (confirm('確定要發送會員邀請郵件嗎？')) {
            // 這裡可以加入 AJAX 請求來發送邀請
            alert('邀請郵件已發送！');
        }
    }
    </script>
    <?php
}

/**
 * 新增管理員選單
 */
function byob_add_member_management_menu() {
    add_submenu_page(
        'edit.php?post_type=restaurant',
        '會員管理',
        '會員管理',
        'manage_options',
        'byob-member-management',
        'byob_member_management_page'
    );
}

/**
 * 餐廳業者儀表板頁面
 */
function byob_restaurant_owner_dashboard() {
    // 檢查使用者是否已登入且為餐廳業者
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_redirect(wp_login_url(get_permalink()));
        exit;
    }
    
    $user = get_user_by('id', $user_id);
    if (!in_array('restaurant_owner', $user->roles)) {
        wp_die('權限不足，只有餐廳業者才能存取此頁面。');
    }
    
    // 獲取使用者擁有的餐廳
    $user_restaurants = byob_get_user_restaurants($user_id);
    
    if (empty($user_restaurants)) {
        echo '<div class="wrap">';
        echo '<h1>餐廳業者儀表板</h1>';
        echo '<p>您目前沒有關聯的餐廳。請聯絡管理員。</p>';
        echo '</div>';
        return;
    }
    
    echo '<div class="wrap">';
    echo '<h1>餐廳業者儀表板</h1>';
    echo '<p>歡迎，' . esc_html($user->display_name) . '！</p>';
    
    echo '<h2>您的餐廳</h2>';
    echo '<div class="restaurant-list">';
    
    foreach ($user_restaurants as $restaurant) {
        echo '<div class="restaurant-item" style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;">';
        echo '<h3>' . esc_html($restaurant->post_title) . '</h3>';
        echo '<p><strong>地址：</strong>' . esc_html(get_field('address', $restaurant->ID)) . '</p>';
        echo '<p><strong>電話：</strong>' . esc_html(get_field('phone', $restaurant->ID)) . '</p>';
        echo '<p><strong>狀態：</strong>已上架</p>';
        echo '<div class="restaurant-actions">';
        echo '<a href="' . admin_url('post.php?post=' . $restaurant->ID . '&action=edit') . '" class="button">編輯餐廳資料</a> ';
        echo '<a href="' . get_permalink($restaurant->ID) . '" class="button" target="_blank">查看餐廳頁面</a>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

/**
 * 新增餐廳業者儀表板選單
 */
function byob_add_restaurant_owner_menu() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return;
    }
    
    $user = get_user_by('id', $user_id);
    if (!in_array('restaurant_owner', $user->roles)) {
        return;
    }
    
    // 新增餐廳業者儀表板選單
    add_menu_page(
        '餐廳管理',
        '餐廳管理',
        'read',
        'restaurant-owner-dashboard',
        'byob_restaurant_owner_dashboard',
        'dashicons-store',
        30
    );
}

// 初始化系統
add_action('init', 'byob_init_restaurant_member_system'); 