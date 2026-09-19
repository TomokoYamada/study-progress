<?php
/**
 * Plugin Name: Study Progress
 * Description: Slackから取得した学習状況を表示するプラグイン。
 * Version: 0.1.0
 */

// WordPressを通さない直接アクセスを終了する
if (!defined('ABSPATH')) {
    exit;
}

// 管理画面のメニューを追加する
add_action('admin_menu', 'study_progress_add_admin_menu');

function study_progress_add_admin_menu() {
    add_options_page(
        '学習状況',
        '学習状況',
        'manage_options',
        'study-progress',
        'study_progress_render_admin_page'
    );
}

// 保存された学習状況を管理画面に表示する
function study_progress_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // データベースから保存内容を取得する
    $data = get_option('study_progress_data', []);

    ?>
    <div class="wrap">
        <h1>学習状況</h1>

        <?php if (empty($data)) : ?>
            <p>まだデータが送信されていません。</p>
        <?php else : ?>
            <h2>目標</h2>
            <p>
                <?php echo nl2br(esc_html($data['goal'])); ?>
            </p>

            <h2>今週の合計学習時間</h2>
            <p>
                <?php echo esc_html($data['total_hours']); ?> 時間
            </p>

            <h2>最終更新日時（UTC）</h2>
            <p>
                <?php echo esc_html($data['updated_at']); ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

// REST APIの受付先を登録する
add_action('rest_api_init', 'study_progress_register_api');

function study_progress_register_api() {
    register_rest_route('study-progress/v1', '/progress', [
        'methods' => 'POST',
        'callback' => 'study_progress_save',

        // 管理権限のあるユーザーだけ更新できる
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },

        // 受け取るデータのルール
        'args' => [
            'goal' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ],
            'total_hours' => [
                'required' => true,
                'type' => 'number',
                'minimum' => 0,
            ],
        ],
    ]);
}

// 受け取ったデータを保存する
function study_progress_save(WP_REST_Request $request) {
    $data = [
        'goal' => $request->get_param('goal'),
        'total_hours' => (float) $request->get_param('total_hours'),
        'updated_at' => current_time('mysql', true),
    ];

    $updated = update_option('study_progress_data', $data, false);

    // 値が同じ場合もfalseになるので、保存内容を確認する
    if (!$updated && get_option('study_progress_data') !== $data) {
        return new WP_Error(
            'save_failed',
            '学習状況を保存できませんでした。',
            ['status' => 500]
        );
    }

    return rest_ensure_response([
        'success' => true,
        'data' => $data,
    ]);
}
?>
