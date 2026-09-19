<?php
/**
 * Plugin Name: Study Progress
 * Description: Slackから取得した学習状況を表示するプラグイン。
 * Version: 0.2.0
 */

// WordPressを通さない直接アクセスを終了する
if (!defined('ABSPATH')) {
    exit;
}

define('STUDY_PROGRESS_VERSION', '0.2.0');

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

// 公開画面で必要なCSSとJavaScriptを読み込む
add_action('wp_enqueue_scripts', 'study_progress_enqueue_front_assets');

function study_progress_enqueue_front_assets() {
    if (!study_progress_has_display_data()) {
        return;
    }

    wp_enqueue_style(
        'study-progress-card',
        plugin_dir_url(__FILE__) . 'assets/css/study-progress-card.css',
        [],
        STUDY_PROGRESS_VERSION
    );

    wp_enqueue_script(
        'study-progress-card',
        plugin_dir_url(__FILE__) . 'assets/js/study-progress-card.js',
        [],
        STUDY_PROGRESS_VERSION,
        true
    );
}

// 公開ブログの右下に学習状況カードを表示する
add_action('wp_footer', 'study_progress_render_front_card');

function study_progress_render_front_card() {
    $data = study_progress_get_display_data();

    if (!$data) {
        return;
    }

    ?>
    <aside class="study-progress-card" data-study-progress-card aria-label="学習状況">
        <button class="study-progress-card__close" type="button" data-study-progress-close aria-label="学習状況カードを閉じる">
            <span aria-hidden="true">&times;</span>
        </button>

        <div class="study-progress-card__content">
            <p class="study-progress-card__label">学習の目標</p>
            <p class="study-progress-card__goal"><?php echo nl2br(esc_html($data['goal'])); ?></p>

            <p class="study-progress-card__label">今週の学習時間</p>
            <p class="study-progress-card__hours"><?php echo esc_html(study_progress_format_hours($data['total_hours'])); ?></p>

            <p class="study-progress-card__message">今週も少しずつ、一緒に頑張ろう</p>
            <p class="study-progress-card__updated">最終同期：<?php echo esc_html(study_progress_format_updated_at($data['updated_at'])); ?></p>
        </div>
    </aside>
    <?php
}

// 公開表示に必要な保存済みデータがあるか確認する
function study_progress_has_display_data() {
    return (bool) study_progress_get_display_data();
}

// 保存済みデータを表示用に取り出す。学習時間0は有効な値として扱う
function study_progress_get_display_data() {
    $data = get_option('study_progress_data', []);

    if (!is_array($data)) {
        return false;
    }

    if (
        !array_key_exists('goal', $data) ||
        !array_key_exists('total_hours', $data) ||
        !array_key_exists('updated_at', $data)
    ) {
        return false;
    }

    if ($data['updated_at'] === '' || !is_numeric($data['total_hours'])) {
        return false;
    }

    return [
        'goal' => (string) $data['goal'],
        'total_hours' => (float) $data['total_hours'],
        'updated_at' => (string) $data['updated_at'],
    ];
}

// 学習時間を「2時間」「1.5時間」のように表示する
function study_progress_format_hours($hours) {
    $hours = (float) $hours;

    if (abs($hours - round($hours)) < 0.00001) {
        return number_format_i18n($hours, 0) . '時間';
    }

    $formatted = rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.');

    return $formatted . '時間';
}

// UTCの保存日時をWordPress設定のタイムゾーンへ変換する
function study_progress_format_updated_at($updated_at) {
    $timestamp = strtotime($updated_at . ' UTC');

    if (!$timestamp) {
        return $updated_at;
    }

    return wp_date(
        get_option('date_format') . ' ' . get_option('time_format'),
        $timestamp,
        wp_timezone()
    );
}
?>
