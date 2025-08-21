<?php
/**
 * Template Name: 餐廳直接加入頁面
 * 
 * 此頁面模板用於餐廳直接加入功能
 * 表單內容將在 UXBuilder 中編輯
 */

get_header(); ?>

<div class="restaurant-join-page">
    <div class="container">
        <!-- 頁面標題和描述 -->
        <div class="page-header">
            <h1 class="page-title">餐廳直接加入</h1>
            <p class="page-description">歡迎加入 BYOB 平台！請填寫以下資料，我們將為您建立餐廳頁面。</p>
        </div>

        <!-- 表單容器 - 將在 UXBuilder 中插入表單 -->
        <div class="registration-form-container">
            <?php the_content(); ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
