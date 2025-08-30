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
        <!-- 表單容器 - 將在 UXBuilder 中插入表單 -->
        <div class="registration-form-container">
            <?php the_content(); ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
