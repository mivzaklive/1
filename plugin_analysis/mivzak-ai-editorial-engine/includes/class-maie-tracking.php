<?php

if (!defined('ABSPATH')) {
    exit;
}

final class MAIE_Tracking
{
    public static function init(): void
    {
        add_action('wp_footer', [__CLASS__, 'render_tracking_script'], 99);
        add_action('wp_ajax_maie_track_view', [__CLASS__, 'handle_track_view']);
        add_action('wp_ajax_nopriv_maie_track_view', [__CLASS__, 'handle_track_view']);

        add_filter('manage_post_posts_columns', [__CLASS__, 'add_posts_column']);
        add_action('manage_post_posts_custom_column', [__CLASS__, 'render_posts_column'], 10, 2);
        add_action('add_meta_boxes_post', [__CLASS__, 'register_meta_box']);
    }

    public static function render_tracking_script(): void
    {
        if (!is_singular('post')) {
            return;
        }

        $post_id = get_queried_object_id();
        if ($post_id <= 0 || !self::is_generated_post($post_id)) {
            return;
        }

        $payload = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'postId' => $post_id,
            'nonce' => wp_create_nonce('maie_track_view_' . $post_id),
        ];

        echo '<script id="maie-view-tracker">';
        echo '(function(){';
        echo 'var cfg=' . wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
        echo 'if(!cfg||!cfg.ajaxUrl||!cfg.postId){return;}';
        echo 'var send=function(){';
        echo 'var data=new URLSearchParams();';
        echo 'data.append("action","maie_track_view");';
        echo 'data.append("post_id",String(cfg.postId));';
        echo 'data.append("nonce",String(cfg.nonce));';
        echo 'if(navigator.sendBeacon){';
        echo 'try{var blob=new Blob([data.toString()],{type:"application/x-www-form-urlencoded; charset=UTF-8"}); if(navigator.sendBeacon(cfg.ajaxUrl,blob)){return;}}catch(e){}';
        echo '}';
        echo 'fetch(cfg.ajaxUrl,{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/x-www-form-urlencoded; charset=UTF-8"},body:data.toString(),keepalive:true}).catch(function(){});';
        echo '};';
        echo 'if(document.visibilityState==="prerender"){document.addEventListener("visibilitychange",function once(){if(document.visibilityState!=="prerender"){document.removeEventListener("visibilitychange",once);send();}});}else{setTimeout(send,1200);}';
        echo '})();';
        echo '</script>';
    }

    public static function handle_track_view(): void
    {
        $post_id = absint($_POST['post_id'] ?? 0);
        if ($post_id <= 0 || !self::is_generated_post($post_id)) {
            wp_send_json_error(['message' => 'post_not_tracked'], 400);
        }

        $nonce = sanitize_text_field((string) ($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'maie_track_view_' . $post_id)) {
            wp_send_json_error(['message' => 'invalid_nonce'], 403);
        }

        $visitor_id = isset($_COOKIE['maie_vid']) ? sanitize_text_field((string) $_COOKIE['maie_vid']) : '';
        if ($visitor_id === '' || strlen($visitor_id) < 12) {
            $visitor_id = wp_generate_uuid4();
            if (!headers_sent()) {
                setcookie('maie_vid', $visitor_id, [
                    'expires' => time() + YEAR_IN_SECONDS,
                    'path' => COOKIEPATH ?: '/',
                    'domain' => COOKIE_DOMAIN ?: '',
                    'secure' => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
        }

        $visitor_hash = hash_hmac('sha256', $visitor_id, wp_salt('auth'));
        $recorded = MAIE_DB::record_view($post_id, $visitor_hash);

        wp_send_json_success([
            'recorded' => $recorded,
        ]);
    }

    public static function add_posts_column(array $columns): array
    {
        $result = [];
        foreach ($columns as $key => $label) {
            $result[$key] = $label;
            if ($key === 'title') {
                $result['maie_generated'] = 'מנוע AI';
            }
        }
        return $result;
    }

    public static function render_posts_column(string $column, int $post_id): void
    {
        if ($column !== 'maie_generated') {
            return;
        }

        if (!self::is_generated_post($post_id)) {
            echo '—';
            return;
        }

        $job_id = absint(get_post_meta($post_id, '_maie_job_id', true));
        echo '<span class="maie-admin-ai-badge">נוצר ע״י מנוע AI</span>';
        if ($job_id > 0) {
            echo '<br><small>משימה #' . esc_html((string) $job_id) . '</small>';
        }
    }

    public static function register_meta_box(): void
    {
        add_meta_box(
            'maie_generated_meta_box',
            'מנוע כתבות AI – מבזק לייב',
            [__CLASS__, 'render_meta_box'],
            'post',
            'side',
            'high'
        );
    }

    public static function render_meta_box(WP_Post $post): void
    {
        if (!self::is_generated_post((int) $post->ID)) {
            echo '<p>הכתבה לא נוצרה דרך התוסף.</p>';
            return;
        }

        $profile_id = absint(get_post_meta($post->ID, '_maie_profile_id', true));
        $job_id = absint(get_post_meta($post->ID, '_maie_job_id', true));
        $generated_at = sanitize_text_field((string) get_post_meta($post->ID, '_maie_generated_at', true));
        $stats = MAIE_DB::get_post_view_totals((int) $post->ID);
        $profile = $profile_id > 0 ? MAIE_DB::get_profile($profile_id) : null;

        echo '<p><strong class="maie-admin-ai-badge">נוצר ע״י מנוע AI</strong></p>';
        echo '<p><strong>פרופיל:</strong> ' . esc_html((string) ($profile['category_name'] ?? 'לא ידוע')) . '</p>';
        echo '<p><strong>משימה:</strong> #' . esc_html((string) $job_id) . '</p>';
        echo '<p><strong>נוצר בתאריך:</strong> ' . esc_html($generated_at !== '' ? $generated_at : 'לא נשמר') . '</p>';
        echo '<hr>';
        echo '<p><strong>צפיות עצמאיות:</strong> ' . esc_html((string) absint($stats['views'] ?? 0)) . '</p>';
        echo '<p><strong>מבקרים ייחודיים:</strong> ' . esc_html((string) absint($stats['unique_visitors'] ?? 0)) . '</p>';
    }

    public static function is_generated_post(int $post_id): bool
    {
        return (string) get_post_meta($post_id, '_maie_generated', true) === '1';
    }
}
