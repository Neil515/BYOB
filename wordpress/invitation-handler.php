<?php
/**
 * BYOB 邀請處理器
 * 處理邀請連結驗證和註冊流程
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 驗證邀請 token
 */
function byob_verify_invitation_token($token) {
    global $wpdb;
    
    if (empty($token)) {
        return array('valid' => false, 'error' => '邀請連結無效');
    }
    
    $table_name = $wpdb->prefix . 'byob_invitations';
    
    // 查詢邀請記錄
    $invitation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE token = %s",
        $token
    ));
    
    if (!$invitation) {
        return array('valid' => false, 'error' => '邀請連結不存在');
    }
    
    // 檢查是否已使用
    if ($invitation->used) {
        return array('valid' => false, 'error' => '邀請連結已使用');
    }
    
    // 檢查是否過期
    if (strtotime($invitation->expires) < time()) {
        return array('valid' => false, 'error' => '邀請連結已過期');
    }
    
    // 檢查餐廳是否存在
    $restaurant = get_post($invitation->restaurant_id);
    if (!$restaurant || $restaurant->post_type !== 'restaurant') {
        return array('valid' => false, 'error' => '關聯的餐廳不存在');
    }
    
    return array(
        'valid' => true,
        'invitation' => $invitation,
        'restaurant' => $restaurant
    );
}

/**
 * 標記邀請為已使用
 */
function byob_mark_invitation_used($token, $user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'byob_invitations';
    
    $updated = $wpdb->update(
        $table_name,
        array(
            'used' => 1,
            'used_at' => current_time('mysql'),
            'user_id' => $user_id
        ),
        array('token' => $token),
        array('%d', '%s', '%d'),
        array('%s')
    );
    
    return $updated !== false;
}

/**
 * 為用戶設定餐廳業者角色和關聯餐廳
 */
function byob_setup_restaurant_owner($user_id, $restaurant_id) {
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return false;
    }
    
    // 添加餐廳業者角色
    $user->add_role('restaurant_owner');
    
    // 關聯餐廳
    update_post_meta($restaurant_id, '_restaurant_owner_id', $user_id);
    update_user_meta($user_id, '_owned_restaurant_id', $restaurant_id);
    
    // 記錄註冊時間
    update_user_meta($user_id, '_byob_registered_at', current_time('mysql'));
    update_user_meta($user_id, '_byob_registration_type', 'invitation');
    
    return true;
}

/**
 * 檢查用戶是否已經是餐廳業者
 */
function byob_is_restaurant_owner($user_id) {
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return false;
    }
    
    return in_array('restaurant_owner', $user->roles);
}

/**
 * 獲取用戶擁有的餐廳
 */
function byob_get_user_restaurant($user_id) {
    $restaurant_id = get_user_meta($user_id, '_owned_restaurant_id', true);
    if (!$restaurant_id) {
        return null;
    }
    
    $restaurant = get_post($restaurant_id);
    if (!$restaurant || $restaurant->post_type !== 'restaurant') {
        return null;
    }
    
    return $restaurant;
}

/**
 * 重新發送邀請 - 注意：此函數需要 byob_send_invitation_email 和 byob_send_restaurant_invitation 在 functions.php 中定義
 */
function byob_resend_invitation($restaurant_id) {
    // 檢查是否已經發送過邀請且未過期
    $existing_token = get_post_meta($restaurant_id, '_byob_invitation_token', true);
    $existing_expires = get_post_meta($restaurant_id, '_byob_invitation_expires', true);
    
    if ($existing_token && strtotime($existing_expires) > time()) {
        // 邀請還在有效期內，重新發送相同的邀請
        global $wpdb;
        $table_name = $wpdb->prefix . 'byob_invitations';
        
        $invitation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE token = %s AND used = 0",
            $existing_token
        ));
        
        if ($invitation) {
            $restaurant = get_post($restaurant_id);
            $invitation_data = array(
                'token' => $invitation->token,
                'restaurant_id' => $invitation->restaurant_id,
                'email' => $invitation->email,
                'contact_person' => $invitation->contact_person,
                'expires' => $invitation->expires,
                'used' => false,
                'created' => $invitation->created
            );
            
            // 檢查函數是否存在再呼叫
            if (function_exists('byob_send_invitation_email')) {
                $mail_result = byob_send_invitation_email($restaurant, $invitation_data);
                
                if ($mail_result) {
                    return array('success' => true, 'message' => '邀請已重新發送');
                } else {
                    return array('success' => false, 'error' => '邀請重新發送失敗');
                }
            }
        }
    }
    
    // 清除舊的邀請記錄
    delete_post_meta($restaurant_id, '_byob_invitation_sent');
    delete_post_meta($restaurant_id, '_byob_invitation_token');
    delete_post_meta($restaurant_id, '_byob_invitation_expires');
    
    // 發送新邀請 - 檢查函數是否存在
    if (function_exists('byob_send_restaurant_invitation')) {
        return byob_send_restaurant_invitation($restaurant_id);
    }
    
    return array('success' => false, 'error' => '邀請函數不存在');
}

/**
 * 獲取邀請統計資訊
 */
function byob_get_invitation_stats() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'byob_invitations';
    
    $stats = array();
    
    // 總邀請數
    $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    
    // 已使用邀請數
    $stats['used'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE used = 1");
    
    // 未使用邀請數
    $stats['unused'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE used = 0");
    
    // 已過期邀請數
    $stats['expired'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE used = 0 AND expires < %s",
        current_time('mysql')
    ));
    
    // 有效邀請數
    $stats['valid'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE used = 0 AND expires >= %s",
        current_time('mysql')
    ));
    
    // 註冊轉換率
    $stats['conversion_rate'] = $stats['total'] > 0 ? round(($stats['used'] / $stats['total']) * 100, 1) : 0;
    
    return $stats;
}

/**
 * 獲取邀請清單
 */
function byob_get_invitations($limit = 50, $offset = 0, $status = 'all') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'byob_invitations';
    
    $where_clause = '';
    $where_values = array();
    
    switch ($status) {
        case 'used':
            $where_clause = 'WHERE used = 1';
            break;
        case 'unused':
            $where_clause = 'WHERE used = 0';
            break;
        case 'expired':
            $where_clause = 'WHERE used = 0 AND expires < %s';
            $where_values[] = current_time('mysql');
            break;
        case 'valid':
            $where_clause = 'WHERE used = 0 AND expires >= %s';
            $where_values[] = current_time('mysql');
            break;
    }
    
    $query = "SELECT * FROM $table_name $where_clause ORDER BY created DESC LIMIT %d OFFSET %d";
    $where_values[] = $limit;
    $where_values[] = $offset;
    
    if (!empty($where_values)) {
        $invitations = $wpdb->get_results($wpdb->prepare($query, $where_values));
    } else {
        $invitations = $wpdb->get_results($wpdb->prepare($query, $limit, $offset));
    }
    
    // 獲取關聯的餐廳資訊
    foreach ($invitations as $invitation) {
        $restaurant = get_post($invitation->restaurant_id);
        $invitation->restaurant_name = $restaurant ? $restaurant->post_title : '未知餐廳';
        
        if ($invitation->used && $invitation->user_id) {
            $user = get_user_by('id', $invitation->user_id);
            $invitation->username = $user ? $user->user_login : '未知用戶';
        } else {
            $invitation->username = '';
        }
    }
    
    return $invitations;
}

/**
 * 刪除過期邀請
 */
function byob_cleanup_expired_invitations() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'byob_invitations';
    
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE used = 0 AND expires < %s",
        current_time('mysql')
    ));
    
    return $deleted;
}

/**
 * 檢查是否需要創建邀請資料表
 */
function byob_ensure_invitation_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'byob_invitations';
    
    // 檢查表格是否存在
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        byob_create_invitation_table();
        return true;
    }
    
    return false;
}
