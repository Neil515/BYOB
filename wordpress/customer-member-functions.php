<?php
/**
 * 一般客人會員系統功能
 * 
 * 主要功能：
 * 1. 客人註冊和登入
 * 2. 餐廳收藏功能
 * 3. 評論和評等系統
 * 4. 個人化推薦
 * 5. 會員積分系統
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 初始化客人會員系統
 */
function byob_init_customer_member_system() {
    // 註冊自定義使用者角色
    byob_register_customer_role();
    
    // 註冊 REST API 端點
    add_action('rest_api_init', 'byob_register_customer_api_endpoints');
    
    // 新增前端功能
    add_action('wp_enqueue_scripts', 'byob_enqueue_customer_scripts');
    add_action('wp_ajax_byob_toggle_favorite', 'byob_toggle_favorite');
    add_action('wp_ajax_nopriv_byob_toggle_favorite', 'byob_toggle_favorite');
    
    // 新增評論功能
    add_action('wp_ajax_byob_add_review', 'byob_add_review');
    add_action('wp_ajax_nopriv_byob_add_review', 'byob_add_review');
}

/**
 * 註冊客人角色
 */
function byob_register_customer_role() {
    // 檢查角色是否已存在
    if (!get_role('customer')) {
        add_role('customer', '一般客人', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'favorite_restaurants' => true, // 自定義權限
            'add_reviews' => true, // 新增評論權限
            'earn_points' => true, // 賺取積分權限
        ));
    }
}

/**
 * 註冊客人相關 REST API 端點
 */
function byob_register_customer_api_endpoints() {
    // 客人註冊
    register_rest_route('byob/v1', '/register-customer', array(
        'methods' => 'POST',
        'callback' => 'byob_register_customer',
        'permission_callback' => '__return_true',
    ));
    
    // 獲取收藏餐廳
    register_rest_route('byob/v1', '/favorites', array(
        'methods' => 'GET',
        'callback' => 'byob_get_favorites',
        'permission_callback' => 'byob_check_customer_permission',
    ));
    
    // 新增/移除收藏
    register_rest_route('byob/v1', '/favorites/(?P<restaurant_id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'byob_toggle_favorite_api',
        'permission_callback' => 'byob_check_customer_permission',
    ));
    
    // 新增評論
    register_rest_route('byob/v1', '/reviews/(?P<restaurant_id>\d+)', array(
        'methods' => 'POST',
        'callback' => 'byob_add_review_api',
        'permission_callback' => 'byob_check_customer_permission',
    ));
    
    // 獲取餐廳評論
    register_rest_route('byob/v1', '/reviews/(?P<restaurant_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'byob_get_restaurant_reviews',
        'permission_callback' => '__return_true',
    ));
    
    // 獲取個人資料
    register_rest_route('byob/v1', '/customer/profile', array(
        'methods' => 'GET',
        'callback' => 'byob_get_customer_profile',
        'permission_callback' => 'byob_check_customer_permission',
    ));
}

/**
 * 客人註冊
 */
function byob_register_customer($request) {
    $email = sanitize_email($request->get_param('email'));
    $password = $request->get_param('password');
    $display_name = sanitize_text_field($request->get_param('display_name'));
    
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
        'role' => 'customer',
        'display_name' => $display_name ?: 'BYOB 會員'
    );
    
    $user_id = wp_insert_user($user_data);
    
    if (is_wp_error($user_id)) {
        return $user_id;
    }
    
    // 初始化客人資料
    update_user_meta($user_id, '_byob_points', 0);
    update_user_meta($user_id, '_byob_join_date', current_time('mysql'));
    
    // 自動登入
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    return array(
        'success' => true,
        'user_id' => $user_id,
        'message' => '註冊成功！'
    );
}

/**
 * 檢查客人權限
 */
function byob_check_customer_permission($request) {
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        return false;
    }
    
    // 檢查是否為客人
    $user = get_userdata($user_id);
    if (!in_array('customer', $user->roles)) {
        return false;
    }
    
    return true;
}

/**
 * 切換收藏狀態
 */
function byob_toggle_favorite() {
    $restaurant_id = intval($_POST['restaurant_id']);
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        wp_die('請先登入');
    }
    
    // 檢查餐廳是否存在
    $restaurant = get_post($restaurant_id);
    if (!$restaurant || $restaurant->post_type !== 'restaurant') {
        wp_die('餐廳不存在');
    }
    
    // 獲取當前收藏列表
    $favorites = get_user_meta($user_id, '_byob_favorites', true);
    if (!is_array($favorites)) {
        $favorites = array();
    }
    
    // 切換收藏狀態
    if (in_array($restaurant_id, $favorites)) {
        // 移除收藏
        $favorites = array_diff($favorites, array($restaurant_id));
        $action = 'removed';
        $message = '已從收藏中移除';
    } else {
        // 新增收藏
        $favorites[] = $restaurant_id;
        $action = 'added';
        $message = '已加入收藏';
        
        // 增加積分
        byob_add_points($user_id, 5, '收藏餐廳');
    }
    
    // 更新收藏列表
    update_user_meta($user_id, '_byob_favorites', $favorites);
    
    // 更新餐廳收藏數
    $favorite_count = get_post_meta($restaurant_id, '_favorite_count', true) ?: 0;
    if ($action === 'added') {
        update_post_meta($restaurant_id, '_favorite_count', $favorite_count + 1);
    } else {
        update_post_meta($restaurant_id, '_favorite_count', max(0, $favorite_count - 1));
    }
    
    wp_send_json_success(array(
        'action' => $action,
        'message' => $message,
        'favorite_count' => $favorite_count + ($action === 'added' ? 1 : -1)
    ));
}

/**
 * 新增評論
 */
function byob_add_review() {
    $restaurant_id = intval($_POST['restaurant_id']);
    $rating = intval($_POST['rating']);
    $comment = sanitize_textarea_field($_POST['comment']);
    $user_id = get_current_user_id();
    
    if (!$user_id) {
        wp_die('請先登入');
    }
    
    // 檢查餐廳是否存在
    $restaurant = get_post($restaurant_id);
    if (!$restaurant || $restaurant->post_type !== 'restaurant') {
        wp_die('餐廳不存在');
    }
    
    // 檢查評分範圍
    if ($rating < 1 || $rating > 5) {
        wp_die('評分必須在 1-5 之間');
    }
    
    // 檢查是否已評論過
    $existing_review = get_comments(array(
        'post_id' => $restaurant_id,
        'user_id' => $user_id,
        'type' => 'review'
    ));
    
    if (!empty($existing_review)) {
        wp_die('您已經評論過這家餐廳了');
    }
    
    // 新增評論
    $comment_data = array(
        'comment_post_ID' => $restaurant_id,
        'comment_author' => get_userdata($user_id)->display_name,
        'comment_author_email' => get_userdata($user_id)->user_email,
        'comment_content' => $comment,
        'comment_type' => 'review',
        'user_id' => $user_id,
        'comment_approved' => 1
    );
    
    $comment_id = wp_insert_comment($comment_data);
    
    if (is_wp_error($comment_id)) {
        wp_die('評論新增失敗');
    }
    
    // 儲存評分
    add_comment_meta($comment_id, 'rating', $rating);
    
    // 更新餐廳平均評分
    byob_update_restaurant_rating($restaurant_id);
    
    // 增加積分
    byob_add_points($user_id, 10, '新增評論');
    
    wp_send_json_success(array(
        'message' => '評論新增成功！',
        'comment_id' => $comment_id
    ));
}

/**
 * 更新餐廳平均評分
 */
function byob_update_restaurant_rating($restaurant_id) {
    $reviews = get_comments(array(
        'post_id' => $restaurant_id,
        'type' => 'review',
        'status' => 'approve'
    ));
    
    $total_rating = 0;
    $review_count = 0;
    
    foreach ($reviews as $review) {
        $rating = get_comment_meta($review->comment_ID, 'rating', true);
        if ($rating) {
            $total_rating += intval($rating);
            $review_count++;
        }
    }
    
    if ($review_count > 0) {
        $average_rating = round($total_rating / $review_count, 1);
        update_post_meta($restaurant_id, '_average_rating', $average_rating);
        update_post_meta($restaurant_id, '_review_count', $review_count);
    }
}

/**
 * 增加積分
 */
function byob_add_points($user_id, $points, $reason) {
    $current_points = get_user_meta($user_id, '_byob_points', true) ?: 0;
    $new_points = $current_points + $points;
    
    update_user_meta($user_id, '_byob_points', $new_points);
    
    // 記錄積分歷史
    $points_history = get_user_meta($user_id, '_byob_points_history', true);
    if (!is_array($points_history)) {
        $points_history = array();
    }
    
    $points_history[] = array(
        'points' => $points,
        'reason' => $reason,
        'date' => current_time('mysql'),
        'total' => $new_points
    );
    
    update_user_meta($user_id, '_byob_points_history', $points_history);
}

/**
 * 獲取收藏餐廳
 */
function byob_get_favorites($request) {
    $user_id = get_current_user_id();
    $favorites = get_user_meta($user_id, '_byob_favorites', true);
    
    if (!is_array($favorites)) {
        return array();
    }
    
    $favorite_restaurants = array();
    foreach ($favorites as $restaurant_id) {
        $restaurant = get_post($restaurant_id);
        if ($restaurant && $restaurant->post_type === 'restaurant') {
            $favorite_restaurants[] = array(
                'id' => $restaurant_id,
                'title' => $restaurant->post_title,
                'permalink' => get_permalink($restaurant_id),
                'thumbnail' => get_the_post_thumbnail_url($restaurant_id, 'medium'),
                'address' => get_field('address', $restaurant_id),
                'rating' => get_post_meta($restaurant_id, '_average_rating', true) ?: 0
            );
        }
    }
    
    return $favorite_restaurants;
}

/**
 * 獲取餐廳評論
 */
function byob_get_restaurant_reviews($request) {
    $restaurant_id = $request->get_param('restaurant_id');
    $reviews = get_comments(array(
        'post_id' => $restaurant_id,
        'type' => 'review',
        'status' => 'approve',
        'number' => 10
    ));
    
    $review_data = array();
    foreach ($reviews as $review) {
        $rating = get_comment_meta($review->comment_ID, 'rating', true);
        $review_data[] = array(
            'id' => $review->comment_ID,
            'author' => $review->comment_author,
            'content' => $review->comment_content,
            'rating' => intval($rating),
            'date' => $review->comment_date,
            'avatar' => get_avatar_url($review->user_id)
        );
    }
    
    return $review_data;
}

/**
 * 獲取客人個人資料
 */
function byob_get_customer_profile($request) {
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    
    $favorites = get_user_meta($user_id, '_byob_favorites', true);
    if (!is_array($favorites)) {
        $favorites = array();
    }
    
    $points = get_user_meta($user_id, '_byob_points', true) ?: 0;
    $join_date = get_user_meta($user_id, '_byob_join_date', true);
    
    return array(
        'user_id' => $user_id,
        'display_name' => $user->display_name,
        'email' => $user->user_email,
        'favorites_count' => count($favorites),
        'points' => $points,
        'join_date' => $join_date,
        'member_level' => byob_get_member_level($points)
    );
}

/**
 * 獲取會員等級
 */
function byob_get_member_level($points) {
    if ($points >= 1000) {
        return '鑽石會員';
    } elseif ($points >= 500) {
        return '金卡會員';
    } elseif ($points >= 200) {
        return '銀卡會員';
    } else {
        return '一般會員';
    }
}

/**
 * 載入客人相關腳本
 */
function byob_enqueue_customer_scripts() {
    wp_enqueue_script('byob-customer', get_template_directory_uri() . '/js/customer.js', array('jquery'), '1.0.0', true);
    wp_enqueue_style('byob-customer', get_template_directory_uri() . '/css/customer.css', array(), '1.0.0');
    
    // 傳遞 AJAX URL 到 JavaScript
    wp_localize_script('byob-customer', 'byob_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('byob_customer_nonce')
    ));
}

/**
 * 新增收藏按鈕到餐廳頁面
 */
function byob_add_favorite_button() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $restaurant_id = get_the_ID();
    $user_id = get_current_user_id();
    $favorites = get_user_meta($user_id, '_byob_favorites', true);
    
    if (!is_array($favorites)) {
        $favorites = array();
    }
    
    $is_favorited = in_array($restaurant_id, $favorites);
    $button_class = $is_favorited ? 'favorited' : '';
    $button_text = $is_favorited ? '已收藏' : '收藏';
    
    echo '<button class="byob-favorite-btn ' . $button_class . '" data-restaurant-id="' . $restaurant_id . '">';
    echo '<i class="fas fa-heart"></i> ' . $button_text;
    echo '</button>';
}

/**
 * 新增評論表單到餐廳頁面
 */
function byob_add_review_form() {
    if (!is_user_logged_in()) {
        echo '<p>請先 <a href="' . wp_login_url(get_permalink()) . '">登入</a> 後再發表評論</p>';
        return;
    }
    
    $restaurant_id = get_the_ID();
    
    // 檢查是否已評論過
    $existing_review = get_comments(array(
        'post_id' => $restaurant_id,
        'user_id' => get_current_user_id(),
        'type' => 'review'
    ));
    
    if (!empty($existing_review)) {
        echo '<p>您已經評論過這家餐廳了</p>';
        return;
    }
    
    ?>
    <div class="byob-review-form">
        <h3>發表評論</h3>
        <form id="byob-review-form" data-restaurant-id="<?php echo $restaurant_id; ?>">
            <div class="rating-input">
                <label>評分：</label>
                <div class="stars">
                    <input type="radio" name="rating" value="5" id="star5">
                    <label for="star5">★</label>
                    <input type="radio" name="rating" value="4" id="star4">
                    <label for="star4">★</label>
                    <input type="radio" name="rating" value="3" id="star3">
                    <label for="star3">★</label>
                    <input type="radio" name="rating" value="2" id="star2">
                    <label for="star2">★</label>
                    <input type="radio" name="rating" value="1" id="star1">
                    <label for="star1">★</label>
                </div>
            </div>
            <div class="comment-input">
                <label for="review-comment">評論：</label>
                <textarea id="review-comment" name="comment" rows="4" required></textarea>
            </div>
            <button type="submit">發表評論</button>
        </form>
    </div>
    <?php
}

/**
 * 顯示餐廳評論
 */
function byob_display_restaurant_reviews() {
    $restaurant_id = get_the_ID();
    $reviews = get_comments(array(
        'post_id' => $restaurant_id,
        'type' => 'review',
        'status' => 'approve',
        'number' => 10
    ));
    
    if (empty($reviews)) {
        echo '<p>尚無評論</p>';
        return;
    }
    
    echo '<div class="byob-reviews">';
    echo '<h3>顧客評論 (' . count($reviews) . ')</h3>';
    
    foreach ($reviews as $review) {
        $rating = get_comment_meta($review->comment_ID, 'rating', true);
        ?>
        <div class="review-item">
            <div class="review-header">
                <span class="review-author"><?php echo $review->comment_author; ?></span>
                <span class="review-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                    <?php endfor; ?>
                </span>
                <span class="review-date"><?php echo date('Y-m-d', strtotime($review->comment_date)); ?></span>
            </div>
            <div class="review-content">
                <?php echo $review->comment_content; ?>
            </div>
        </div>
        <?php
    }
    
    echo '</div>';
}

// 初始化系統
add_action('init', 'byob_init_customer_member_system'); 