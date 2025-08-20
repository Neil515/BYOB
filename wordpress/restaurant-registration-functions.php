<?php
/**
 * 餐廳註冊功能
 * 
 * 提供餐廳業者直接註冊功能，包括：
 * - 註冊表單顯示
 * - 表單資料驗證
 * - 會員帳號創建
 * - 錯誤處理和成功訊息
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 顯示餐廳註冊表單
 */
function byob_display_registration_form($atts = array()) {
    // 檢查用戶是否已登入
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (in_array('restaurant_owner', $current_user->roles)) {
            echo '<div class="alert alert-info">您已經註冊為餐廳業者，無需重複註冊。</div>';
            return;
        }
    }
    
    // 獲取表單資料（如果有錯誤重新提交）
    $form_data = isset($_POST['restaurant_registration']) ? $_POST['restaurant_registration'] : array();
    $errors = isset($_POST['registration_errors']) ? $_POST['registration_errors'] : array();
    
    ?>
    <div class="restaurant-registration-form">
        <form method="post" action="" id="restaurant-registration-form" enctype="multipart/form-data">
            <?php wp_nonce_field('restaurant_registration_nonce', 'registration_nonce'); ?>
            
            <!-- Email -->
            <div class="form-group">
                <label>Email(必填)</label>
                <input type="email" name="restaurant_registration[email]" 
                       value="<?php echo esc_attr($form_data['email'] ?? ''); ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <span class="error-message"><?php echo esc_html($errors['email']); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- 餐廳名稱 -->
                    <div class="form-group">
                <label>餐廳名稱(必填)</label>
                <input type="text" name="restaurant_registration[restaurant_name]" 
                       value="<?php echo esc_attr($form_data['restaurant_name'] ?? ''); ?>" 
                       minlength="2" maxlength="30" required>
                        <?php if (isset($errors['restaurant_name'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['restaurant_name']); ?></span>
                        <?php endif; ?>
                </div>
                
            <!-- 餐廳類型 -->
                    <div class="form-group">
                <label>餐廳類型(必填)</label>
                <div class="checkbox-group">
                    <?php
                    $restaurant_types = array("台式", "法式", "義式", "日式", "美式", "熱炒", "小酒館", "咖啡廳", "私廚", "異國料理", "燒烤", "火鍋", "牛排", "Lounge Bar", "Buffet", "Fine dining");
                    $selected_types = $form_data['restaurant_type'] ?? array();
                    foreach ($restaurant_types as $type) {
                        $checked = in_array($type, $selected_types) ? 'checked' : '';
                        echo '<label><input type="checkbox" name="restaurant_registration[restaurant_type][]" value="' . esc_attr($type) . '" ' . $checked . '> ' . esc_html($type) . '</label>';
                    }
                    ?>
                </div>
                        <?php if (isset($errors['restaurant_type'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['restaurant_type']); ?></span>
                        <?php endif; ?>
                    </div>
            
            <!-- 其他類型說明 -->
            <div class="form-group">
                <label>其他類型說明</label>
                <input type="text" name="restaurant_registration[restaurant_type_other]" 
                       value="<?php echo esc_attr($form_data['restaurant_type_other'] ?? ''); ?>"
                       placeholder="如果選擇其他，請說明餐廳類型">
            </div>
            
            <!-- 行政區 -->
            <div class="form-group">
                <label>行政區(必填)</label>
                <select name="restaurant_registration[district]" required>
                    <option value="">請選擇行政區</option>
                    <?php
                    $districts = array("中山區", "中正區", "大同區", "松山區", "大安區", "萬華區", "信義區", "士林區", "北投區", "內湖區", "南港區", "文山區");
                    $selected_district = $form_data['district'] ?? '';
                    foreach ($districts as $district) {
                        $selected = ($selected_district === $district) ? 'selected' : '';
                        echo '<option value="' . esc_attr($district) . '" ' . $selected . '>' . esc_html($district) . '</option>';
                    }
                    ?>
                </select>
                <?php if (isset($errors['district'])): ?>
                    <span class="error-message"><?php echo esc_html($errors['district']); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- 地址 -->
            <div class="form-group">
                <label>地址(必填)</label>
                <input type="text" name="restaurant_registration[address]" 
                       value="<?php echo esc_attr($form_data['address'] ?? ''); ?>" 
                       minlength="8" maxlength="200" 
                       placeholder="請填完整地址：例如「台北市松山區民生東路三段100號」" required>
                <?php if (isset($errors['address'])): ?>
                    <span class="error-message"><?php echo esc_html($errors['address']); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- 是否收開瓶費 -->
            <div class="form-group">
                <label>是否收開瓶費(必填)</label>
                <div class="radio-group">
                    <?php
                    $is_charged_options = array(
                        'yes' => '酌收',
                        'no' => '不收費',
                        'other' => '其他'
                    );
                    $selected_charged = $form_data['is_charged'] ?? '';
                    foreach ($is_charged_options as $value => $label) {
                        $checked = ($selected_charged === $value) ? 'checked' : '';
                        echo '<label><input type="radio" name="restaurant_registration[is_charged]" value="' . esc_attr($value) . '" ' . $checked . '> ' . esc_html($label) . '</label>';
                    }
                    ?>
                </div>
                <?php if (isset($errors['is_charged'])): ?>
                    <span class="error-message"><?php echo esc_html($errors['is_charged']); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- 開瓶費說明（條件式顯示） -->
            <div class="form-group" id="corkage-fee-group" style="display: none;">
                <label>開瓶費說明</label>
                <input type="text" name="restaurant_registration[corkage_fee]" 
                       value="<?php echo esc_attr($form_data['corkage_fee'] ?? ''); ?>" 
                       minlength="1" maxlength="100" 
                       placeholder="請說明開瓶費收費方式或金額">
                <?php if (isset($errors['corkage_fee'])): ?>
                    <span class="error-message"><?php echo esc_html($errors['corkage_fee']); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- 提供酒器設備 -->
            <div class="form-group">
                <label>提供酒器設備</label>
                <div class="checkbox-group">
                    <?php
                    $equipment_options = array("酒杯", "開瓶器", "冰桶", "醒酒器", "酒塞/瓶塞", "酒架/酒櫃", "溫度計", "濾酒器", "其他", "無提供");
                    $selected_equipment = $form_data['equipment'] ?? array();
                    foreach ($equipment_options as $equipment) {
                        $checked = in_array($equipment, $selected_equipment) ? 'checked' : '';
                        echo '<label><input type="checkbox" name="restaurant_registration[equipment][]" value="' . esc_attr($equipment) . '" ' . $checked . '> ' . esc_html($equipment) . '</label>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- 是否提供開酒服務 -->
            <div class="form-group">
                <label>是否提供開酒服務？</label>
                <select name="restaurant_registration[open_bottle_service]">
                    <option value="">請選擇</option>
                    <?php
                    $open_bottle_options = array(
                        'yes' => '是',
                        'no' => '否',
                        'other' => '其他'
                    );
                    $selected_open_bottle = $form_data['open_bottle_service'] ?? '';
                    foreach ($open_bottle_options as $value => $label) {
                        $selected = ($selected_open_bottle === $value) ? 'selected' : '';
                        echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <!-- 開酒服務其他說明（條件式顯示） -->
            <div class="form-group" id="open-bottle-service-note-group" style="display: none;">
                <label>開酒服務其他說明</label>
                <input type="text" name="restaurant_registration[open_bottle_service_other_note]" 
                       value="<?php echo esc_attr($form_data['open_bottle_service_other_note'] ?? ''); ?>" 
                       maxlength="60" 
                       placeholder="請說明其他開酒服務方式">
            </div>
            
            <!-- 官網/訂位網站 -->
            <div class="form-group">
                <label>官網/訂位網站</label>
                <input type="url" name="restaurant_registration[website]" 
                       value="<?php echo esc_url($form_data['website'] ?? ''); ?>" 
                       minlength="10" maxlength="255" 
                       placeholder="請輸入餐廳官網或訂位網站網址">
            </div>
            
            <!-- 社群連結 -->
            <div class="form-group">
                <label>社群連結</label>
                <input type="url" name="restaurant_registration[social_links]" 
                       value="<?php echo esc_url($form_data['social_links'] ?? ''); ?>" 
                       minlength="10" maxlength="255" 
                       placeholder="請輸入社群媒體連結，例如：Facebook、Instagram 等">
            </div>
            
            <!-- 您是餐廳負責人嗎 -->
            <div class="form-group">
                <label>您是餐廳負責人嗎？(必填)</label>
                <select name="restaurant_registration[source]" required>
                    <option value="">請選擇</option>
                    <?php
                    $source_options = array("是", "否（我是協助填寫）");
                    $selected_source = $form_data['source'] ?? '';
                    foreach ($source_options as $value => $label) {
                        $selected = ($selected_source === $value) ? 'selected' : '';
                        echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
                <?php if (isset($errors['source'])): ?>
                    <span class="error-message"><?php echo esc_html($errors['source']); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- 您的稱呼 -->
            <div class="form-group">
                <label>您的稱呼是？(必填)</label>
                <input type="text" name="restaurant_registration[contact_person]" 
                       value="<?php echo esc_attr($form_data['contact_person'] ?? ''); ?>" required>
                <?php if (isset($errors['contact_person'])): ?>
                    <span class="error-message"><?php echo esc_html($errors['contact_person']); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- 餐廳聯絡電話 -->
            <div class="form-group">
                <label>餐廳聯絡電話</label>
                <input type="tel" name="restaurant_registration[phone]" 
                       value="<?php echo esc_attr($form_data['phone'] ?? ''); ?>" 
                       minlength="8" maxlength="16" 
                       placeholder="請輸入餐廳聯絡電話" required>
                <?php if (isset($errors['phone'])): ?>
                    <span class="error-message"><?php echo esc_html($errors['phone']); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- 提交按鈕 -->
            <div class="form-group">
                <button type="submit" class="submit-button">送出</button>
            </div>
        </form>
                </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('BYOB PHP: 表單JavaScript已載入');
        
        // 開瓶費條件邏輯
        const isChargedRadios = document.querySelectorAll('input[name="restaurant_registration[is_charged]"]');
        const corkageFeeGroup = document.getElementById('corkage-fee-group');
        
        isChargedRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                console.log('BYOB PHP: 開瓶費選擇變更為:', this.value);
                if (this.value === 'yes' || this.value === 'other') {
                    corkageFeeGroup.style.display = 'block';
                } else {
                    corkageFeeGroup.style.display = 'none';
                }
            });
        });
        
        // 開酒服務條件邏輯
        const openBottleServiceSelect = document.querySelector('select[name="restaurant_registration[open_bottle_service]"]');
        const openBottleServiceNoteGroup = document.getElementById('open-bottle-service-note-group');
        
        openBottleServiceSelect.addEventListener('change', function() {
            console.log('BYOB PHP: 開酒服務選擇變更為:', this.value);
            if (this.value === 'other') {
                openBottleServiceNoteGroup.style.display = 'block';
            } else {
                openBottleServiceNoteGroup.style.display = 'none';
            }
        });
        
        // 初始化狀態
        initConditionalFields();
    });
    
    function initConditionalFields() {
        // 檢查開瓶費選擇
        const checkedChargedRadio = document.querySelector('input[name="restaurant_registration[is_charged]"]:checked');
        if (checkedChargedRadio && (checkedChargedRadio.value === 'yes' || checkedChargedRadio.value === 'other')) {
            document.getElementById('corkage-fee-group').style.display = 'block';
        }
        
        // 檢查開酒服務選擇
        const openBottleServiceSelect = document.querySelector('select[name="restaurant_registration[open_bottle_service]"]');
        if (openBottleServiceSelect.value === 'other') {
            document.getElementById('open-bottle-service-note-group').style.display = 'block';
        }
    }
    </script>
    <?php
}
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="restaurant_type_other">其他類型說明</label>
                        <input type="text" id="restaurant_type_other" name="restaurant_registration[restaurant_type_other]" 
                               value="<?php echo esc_attr($form_data['restaurant_type_other'] ?? ''); ?>"
                               placeholder="如果選擇其他，請說明餐廳類型">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-primary next-step" data-next="2">下一步</button>
                </div>
            </div>
            
            <!-- 步驟2：餐廳詳情 -->
            <div class="form-step" data-step="2">
                <h3>餐廳詳情</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="restaurant_address">營業地址 *</label>
                        <input type="text" id="restaurant_address" name="restaurant_registration[restaurant_address]" 
                               value="<?php echo esc_attr($form_data['restaurant_address'] ?? ''); ?>" required>
                        <?php if (isset($errors['restaurant_address'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['restaurant_address']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="business_hours">營業時間 *</label>
                        <input type="text" id="business_hours" name="restaurant_registration[business_hours]" 
                               value="<?php echo esc_attr($form_data['business_hours'] ?? ''); ?>" 
                               placeholder="例：週一至週五 11:00-22:00，週六日 10:00-23:00" required>
                        <?php if (isset($errors['business_hours'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['business_hours']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="open_bottle_service">是否提供開酒服務？ *</label>
                        <select id="open_bottle_service" name="restaurant_registration[open_bottle_service]" required>
                            <option value="">請選擇</option>
                            <option value="yes" <?php selected($form_data['open_bottle_service'] ?? '', 'yes'); ?>>是</option>
                            <option value="no" <?php selected($form_data['open_bottle_service'] ?? '', 'no'); ?>>否</option>
                            <option value="other" <?php selected($form_data['open_bottle_service'] ?? '', 'other'); ?>>其他</option>
                        </select>
                        <?php if (isset($errors['open_bottle_service'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['open_bottle_service']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="open_bottle_service_other_note">開酒服務說明</label>
                        <textarea id="open_bottle_service_other_note" name="restaurant_registration[open_bottle_service_other_note]" 
                                  placeholder="如果選擇其他，請說明開酒服務的詳細內容"><?php echo esc_textarea($form_data['open_bottle_service_other_note'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary prev-step" data-prev="1">上一步</button>
                    <button type="button" class="btn btn-primary next-step" data-next="3">下一步</button>
                </div>
            </div>
            
            <!-- 步驟3：聯絡資訊 -->
            <div class="form-step" data-step="3">
                <h3>聯絡資訊</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_name">聯絡人姓名 *</label>
                        <input type="text" id="contact_name" name="restaurant_registration[contact_name]" 
                               value="<?php echo esc_attr($form_data['contact_name'] ?? ''); ?>" required>
                        <?php if (isset($errors['contact_name'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['contact_name']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_phone">聯絡電話 *</label>
                        <input type="tel" id="contact_phone" name="restaurant_registration[contact_phone]" 
                               value="<?php echo esc_attr($form_data['contact_phone'] ?? ''); ?>" required>
                        <?php if (isset($errors['contact_phone'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['contact_phone']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_email">聯絡Email *</label>
                        <input type="email" id="contact_email" name="restaurant_registration[contact_email]" 
                               value="<?php echo esc_attr($form_data['contact_email'] ?? ''); ?>" required>
                        <?php if (isset($errors['contact_email'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['contact_email']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">密碼 *</label>
                        <input type="password" id="password" name="restaurant_registration[password]" required>
                        <?php if (isset($errors['password'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['password']); ?></span>
                        <?php endif; ?>
                        <small class="form-help">密碼至少8個字元，建議包含字母和數字</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="confirm_password">確認密碼 *</label>
                        <input type="password" id="confirm_password" name="restaurant_registration[confirm_password]" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <span class="error-message"><?php echo esc_html($errors['confirm_password']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary prev-step" data-prev="2">上一步</button>
                    <button type="button" class="btn btn-primary next-step" data-next="4">下一步</button>
                </div>
            </div>
            
            <!-- 步驟4：確認提交 -->
            <div class="form-step" data-step="4">
                <h3>確認資訊</h3>
                
                <div class="confirmation-summary">
                    <div class="summary-item">
                        <strong>餐廳名稱：</strong>
                        <span id="summary-restaurant-name"></span>
                    </div>
                    <div class="summary-item">
                        <strong>餐廳類型：</strong>
                        <span id="summary-restaurant-type"></span>
                    </div>
                    <div class="summary-item">
                        <strong>營業地址：</strong>
                        <span id="summary-restaurant-address"></span>
                    </div>
                    <div class="summary-item">
                        <strong>營業時間：</strong>
                        <span id="summary-business-hours"></span>
                    </div>
                    <div class="summary-item">
                        <strong>開酒服務：</strong>
                        <span id="summary-open-bottle-service"></span>
                    </div>
                    <div class="summary-item">
                        <strong>聯絡人：</strong>
                        <span id="summary-contact-name"></span>
                    </div>
                    <div class="summary-item">
                        <strong>聯絡電話：</strong>
                        <span id="summary-contact-phone"></span>
                    </div>
                    <div class="summary-item">
                        <strong>聯絡Email：</strong>
                        <span id="summary-contact-email"></span>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary prev-step" data-prev="3">上一步</button>
                    <button type="submit" class="btn btn-success" name="submit_registration">提交註冊</button>
                </div>
            </div>
        </form>
    </div>
    
    <?php
    // 只載入JavaScript檔案，CSS放在WordPress自訂→附加CSS中
    wp_enqueue_script('restaurant-registration', get_template_directory_uri() . '/restaurant-registration.js', array('jquery'), '1.0.0', true);
    ?>
    <?php
}

/**
 * 短代碼函數
 */
function byob_restaurant_registration_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_title' => 'true',
        'theme' => 'default'
    ), $atts);
    
    ob_start();
    byob_display_registration_form($atts);
    return ob_get_clean();
}
add_shortcode('restaurant_registration_form', 'byob_restaurant_registration_shortcode');

/**
 * 處理註冊表單提交
 */
function byob_handle_registration_form() {
    // 檢查nonce
    if (!wp_verify_nonce($_POST['registration_nonce'], 'restaurant_registration_nonce')) {
        wp_die('安全驗證失敗');
    }
    
    // 獲取表單資料
    $form_data = $_POST['restaurant_registration'];
    
    // 驗證必填欄位
    $errors = array();
    $required_fields = array(
        'restaurant_name' => '餐廳名稱',
        'restaurant_type' => '餐廳類型',
        'restaurant_address' => '營業地址',
        'business_hours' => '營業時間',
        'open_bottle_service' => '開酒服務',
        'contact_name' => '聯絡人姓名',
        'contact_phone' => '聯絡電話',
        'contact_email' => '聯絡Email',
        'password' => '密碼',
        'confirm_password' => '確認密碼'
    );
    
    foreach ($required_fields as $field => $label) {
        if (empty($form_data[$field])) {
            $errors[$field] = $label . '為必填欄位';
        }
    }
    
    // 驗證email格式
    if (!empty($form_data['contact_email']) && !is_email($form_data['contact_email'])) {
        $errors['contact_email'] = '請輸入有效的Email地址';
    }
    
    // 驗證email是否已被使用
    if (!empty($form_data['contact_email']) && email_exists($form_data['contact_email'])) {
        $errors['contact_email'] = '此email已被註冊';
    }
    
    // 驗證密碼長度
    if (!empty($form_data['password']) && strlen($form_data['password']) < 8) {
        $errors['password'] = '密碼至少需要8個字元';
    }
    
    // 驗證密碼確認
    if (!empty($form_data['password']) && !empty($form_data['confirm_password']) && 
        $form_data['password'] !== $form_data['confirm_password']) {
        $errors['confirm_password'] = '密碼與確認密碼不符';
    }
    
    // 驗證電話號碼格式
    if (!empty($form_data['contact_phone'])) {
        $phone = preg_replace('/[^0-9+\-\(\)]/', '', $form_data['contact_phone']);
        if (strlen($phone) < 8) {
            $errors['contact_phone'] = '請輸入有效的聯絡電話';
        }
    }
    
    // 如果有錯誤，重新顯示表單
    if (!empty($errors)) {
        $_POST['registration_errors'] = $errors;
        return;
    }
    
    // 創建用戶帳號
    $user_data = array(
        'user_login' => sanitize_user($form_data['contact_email']),
        'user_email' => sanitize_email($form_data['contact_email']),
        'user_pass' => $form_data['password'],
        'display_name' => sanitize_text_field($form_data['contact_name']),
        'first_name' => sanitize_text_field($form_data['contact_name']),
        'role' => 'restaurant_owner'
    );
    
    $user_id = wp_insert_user($user_data);
    
    if (is_wp_error($user_id)) {
        $errors['general'] = '創建用戶帳號失敗：' . $user_id->get_error_message();
        $_POST['registration_errors'] = $errors;
        return;
    }
    
    // 創建餐廳文章
    $restaurant_data = array(
        'post_title' => sanitize_text_field($form_data['restaurant_name']),
        'post_content' => '',
        'post_status' => 'publish',
        'post_type' => 'restaurant',
        'post_author' => $user_id
    );
    
    $restaurant_id = wp_insert_post($restaurant_data);
    
    if (is_wp_error($restaurant_id)) {
        // 如果創建餐廳失敗，刪除用戶帳號
        wp_delete_user($user_id);
        $errors['general'] = '創建餐廳資料失敗：' . $restaurant_id->get_error_message();
        $_POST['registration_errors'] = $errors;
        return;
    }
    
    // 設定餐廳meta資料
    update_post_meta($restaurant_id, '_restaurant_owner', $user_id);
    update_post_meta($restaurant_id, '_restaurant_type', sanitize_text_field($form_data['restaurant_type']));
    update_post_meta($restaurant_id, '_restaurant_address', sanitize_text_field($form_data['restaurant_address']));
    update_post_meta($restaurant_id, '_business_hours', sanitize_text_field($form_data['business_hours']));
    update_post_meta($restaurant_id, '_contact_phone', sanitize_text_field($form_data['contact_phone']));
    
    // 設定ACF欄位
    if (function_exists('update_field')) {
        update_field('restaurant_type', sanitize_text_field($form_data['restaurant_type']), $restaurant_id);
        update_field('restaurant_address', sanitize_text_field($form_data['restaurant_address']), $restaurant_id);
        update_field('business_hours', sanitize_text_field($form_data['business_hours']), $restaurant_id);
        update_field('open_bottle_service', sanitize_text_field($form_data['open_bottle_service']), $restaurant_id);
        
        if (!empty($form_data['restaurant_type_other'])) {
            update_field('restaurant_type_other_note', sanitize_text_field($form_data['restaurant_type_other']), $restaurant_id);
        }
        
        if (!empty($form_data['open_bottle_service_other_note'])) {
            update_field('open_bottle_service_other_note', sanitize_textarea_field($form_data['open_bottle_service_other_note']), $restaurant_id);
        }
    }
    
    // 自動登入用戶
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    // 重定向到成功頁面或後台
    wp_redirect(admin_url());
    exit;
}

/**
 * AJAX處理註冊請求
 */
function byob_ajax_registration() {
    // 檢查nonce
    if (!wp_verify_nonce($_POST['nonce'], 'restaurant_registration_nonce')) {
        wp_send_json_error('安全驗證失敗');
    }
    
    // 處理註冊邏輯
    byob_handle_registration_form();
    
    // 返回結果
    if (empty($_POST['registration_errors'])) {
        wp_send_json_success('註冊成功');
    } else {
        wp_send_json_error($_POST['registration_errors']);
    }
}
add_action('wp_ajax_restaurant_registration', 'byob_ajax_registration');
add_action('wp_ajax_nopriv_restaurant_registration', 'byob_ajax_registration');

/**
 * 處理表單提交
 */
if ($_POST && isset($_POST['submit_registration'])) {
    byob_handle_registration_form();
}
