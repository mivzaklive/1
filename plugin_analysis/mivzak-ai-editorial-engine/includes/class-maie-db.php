<?php

if (!defined('ABSPATH')) {
    exit;
}

final class MAIE_DB
{
    public static function profiles_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'maie_profiles';
    }

    public static function jobs_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'maie_jobs';
    }

    public static function job_events_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'maie_job_events';
    }

    public static function views_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'maie_article_views';
    }

    private static ?array $settings_cache = null;

    public static function get_settings(): array
    {
        if (self::$settings_cache !== null) {
            return self::$settings_cache;
        }

        $defaults = MAIE_Prompts::default_settings();
        $saved = get_option('maie_settings', []);
        if (!is_array($saved)) {
            $saved = [];
        }

        self::$settings_cache = array_merge($defaults, $saved);
        return self::$settings_cache;
    }

    public static function update_settings(array $settings): bool
    {
        self::$settings_cache = null; // אפס cache לאחר עדכון
        $defaults = MAIE_Prompts::default_settings();
        $clean = [];

        foreach ($defaults as $key => $default_value) {
            $value = $settings[$key] ?? $default_value;

            switch ($key) {
                case 'api_key':
                    $clean[$key] = sanitize_text_field((string) $value);
                    break;

                case 'text_model':
                    $clean[$key] = MAIE_Options::allowed_text_model(sanitize_text_field((string) $value), (string) $default_value);
                    break;

                case 'qa_model':
                    $clean[$key] = MAIE_Options::allowed_qa_model(sanitize_text_field((string) $value), (string) $default_value);
                    break;

                case 'vision_model':
                    $clean[$key] = MAIE_Options::allowed_vision_model(sanitize_text_field((string) $value), (string) $default_value);
                    break;

                case 'image_model':
                    $clean[$key] = MAIE_Options::allowed_image_model(sanitize_text_field((string) $value), (string) $default_value);
                    break;

                case 'image_output_format':
                    $allowed = ['webp', 'png', 'jpeg'];
                    $clean[$key] = in_array($value, $allowed, true) ? $value : $default_value;
                    break;

                case 'image_quality':
                    $clean[$key] = MAIE_Options::allowed_image_quality(sanitize_text_field((string) $value), (string) $default_value);
                    break;

                case 'image_size':
                    $clean[$key] = MAIE_Options::allowed_image_size(sanitize_text_field((string) $value), (string) $default_value);
                    break;

                case 'default_post_author':
                case 'image_output_compression':
                case 'max_jobs_per_cron':
                case 'max_image_regeneration_attempts':
                case 'jobs_retention_days':
                    $clean[$key] = max(0, absint($value));
                    break;

                case 'auto_cron_enabled':
                case 'web_search_enabled':
                case 'qa_enabled':
                case 'image_qa_enabled':
                case 'strict_publish_requires_image':
                case 'debug_enabled':
                    $clean[$key] = !empty($value) ? 1 : 0;
                    break;

                default:
                    $clean[$key] = is_string($value) ? sanitize_text_field($value) : $default_value;
                    break;
            }
        }

        if ($clean['default_post_author'] <= 0 || !get_user_by('id', $clean['default_post_author'])) {
            $clean['default_post_author'] = get_current_user_id() ?: 1;
        }

        if ($clean['image_output_compression'] < 1 || $clean['image_output_compression'] > 100) {
            $clean['image_output_compression'] = 90;
        }

        if ($clean['max_jobs_per_cron'] < 1) {
            $clean['max_jobs_per_cron'] = 1;
        }

        if ($clean['max_image_regeneration_attempts'] > 5) {
            $clean['max_image_regeneration_attempts'] = 5;
        }

        if (isset($clean['jobs_retention_days']) && ($clean['jobs_retention_days'] < 7 || $clean['jobs_retention_days'] > 365)) {
            $clean['jobs_retention_days'] = 30;
        }

        return update_option('maie_settings', $clean, false);
    }

    public static function get_profiles(array $args = []): array
    {
        global $wpdb;

        $table = self::profiles_table();
        $where = '1=1';
        $params = [];

        if (isset($args['enabled'])) {
            $where .= ' AND enabled = %d';
            $params[] = !empty($args['enabled']) ? 1 : 0;
        }

        if (!empty($args['content_mode'])) {
            $where .= ' AND content_mode = %s';
            $params[] = sanitize_key((string) $args['content_mode']);
        }

        if (!empty($args['generation_interval_minutes'])) {
            $where .= ' AND generation_interval_minutes = %d';
            $params[] = max(5, absint($args['generation_interval_minutes']));
        }

        if (!empty($args['post_status'])) {
            $where .= ' AND post_status = %s';
            $params[] = self::sanitize_post_status((string) $args['post_status']);
        }

        if (!empty($args['search'])) {
            $like = '%' . $wpdb->esc_like(sanitize_text_field((string) $args['search'])) . '%';
            $where .= ' AND (category_name LIKE %s OR menu_group LIKE %s OR category_slug LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $orderby = sanitize_key((string) ($args['orderby'] ?? 'default'));
        $order = strtoupper(sanitize_text_field((string) ($args['order'] ?? 'ASC')));
        $order = $order === 'DESC' ? 'DESC' : 'ASC';

        if ($orderby === 'frequency') {
            $order_by = "generation_interval_minutes {$order}, menu_group ASC, category_name ASC, id ASC";
        } else {
            $order_by = 'menu_group ASC, category_name ASC, id ASC';
        }

        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$order_by}";

        if ($params) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public static function get_profile(int $id): ?array
    {
        global $wpdb;
        $table = self::profiles_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    public static function get_profile_by_category_name(string $category_name): ?array
    {
        global $wpdb;
        $table = self::profiles_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE category_name = %s LIMIT 1", $category_name), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    public static function insert_profile(array $data): int
    {
        global $wpdb;
        $table = self::profiles_table();
        $now = current_time('mysql');

        $inserted = $wpdb->insert(
            $table,
            [
                'category_id' => absint($data['category_id'] ?? 0),
                'category_name' => sanitize_text_field((string) ($data['category_name'] ?? '')),
                'category_slug' => sanitize_title((string) ($data['category_slug'] ?? '')),
                'menu_group' => sanitize_text_field((string) ($data['menu_group'] ?? 'כללי')),
                'content_mode' => self::sanitize_content_mode((string) ($data['content_mode'] ?? 'news')),
                'enabled' => !empty($data['enabled']) ? 1 : 0,
                'post_status' => self::sanitize_post_status((string) ($data['post_status'] ?? 'draft')),
                'search_window_hours' => max(1, absint($data['search_window_hours'] ?? 12)),
                'generation_interval_minutes' => max(5, absint($data['generation_interval_minutes'] ?? 180)),
                'max_posts_per_day' => max(0, absint($data['max_posts_per_day'] ?? 0)),
                'prefer_israel' => !empty($data['prefer_israel']) ? 1 : 0,
                'preferred_domains' => sanitize_textarea_field((string) ($data['preferred_domains'] ?? '')),
                'blocked_domains' => sanitize_textarea_field((string) ($data['blocked_domains'] ?? '')),
                'topic_keywords' => sanitize_textarea_field((string) ($data['topic_keywords'] ?? '')),
                'research_brief' => wp_kses_post((string) ($data['research_brief'] ?? '')),
                'title_prompt' => wp_kses_post((string) ($data['title_prompt'] ?? '')),
                'article_prompt' => wp_kses_post((string) ($data['article_prompt'] ?? '')),
                'image_prompt' => wp_kses_post((string) ($data['image_prompt'] ?? '')),
                'web_search_mode' => MAIE_Options::allowed_web_search_mode(sanitize_key((string) ($data['web_search_mode'] ?? 'inherit'))),
                'text_model_override' => self::sanitize_optional_choice((string) ($data['text_model_override'] ?? ''), array_keys(MAIE_Options::text_models())),
                'qa_model_override' => self::sanitize_optional_choice((string) ($data['qa_model_override'] ?? ''), array_keys(MAIE_Options::qa_models())),
                'vision_model_override' => self::sanitize_optional_choice((string) ($data['vision_model_override'] ?? ''), array_keys(MAIE_Options::vision_models())),
                'image_model_override' => self::sanitize_optional_choice((string) ($data['image_model_override'] ?? ''), array_keys(MAIE_Options::image_models())),
                'image_size_override' => self::sanitize_optional_choice((string) ($data['image_size_override'] ?? ''), array_keys(MAIE_Options::image_sizes())),
                'image_quality_override' => self::sanitize_optional_choice((string) ($data['image_quality_override'] ?? ''), array_keys(MAIE_Options::image_qualities())),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d',
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            ]
        );

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    public static function update_profile(int $id, array $data): bool
    {
        global $wpdb;
        $table = self::profiles_table();

        $updated = $wpdb->update(
            $table,
            [
                'category_id' => absint($data['category_id'] ?? 0),
                'category_name' => sanitize_text_field((string) ($data['category_name'] ?? '')),
                'category_slug' => sanitize_title((string) ($data['category_slug'] ?? '')),
                'menu_group' => sanitize_text_field((string) ($data['menu_group'] ?? 'כללי')),
                'content_mode' => self::sanitize_content_mode((string) ($data['content_mode'] ?? 'news')),
                'enabled' => !empty($data['enabled']) ? 1 : 0,
                'post_status' => self::sanitize_post_status((string) ($data['post_status'] ?? 'draft')),
                'search_window_hours' => max(1, absint($data['search_window_hours'] ?? 12)),
                'generation_interval_minutes' => max(5, absint($data['generation_interval_minutes'] ?? 180)),
                'max_posts_per_day' => max(0, absint($data['max_posts_per_day'] ?? 0)),
                'prefer_israel' => !empty($data['prefer_israel']) ? 1 : 0,
                'preferred_domains' => sanitize_textarea_field((string) ($data['preferred_domains'] ?? '')),
                'blocked_domains' => sanitize_textarea_field((string) ($data['blocked_domains'] ?? '')),
                'topic_keywords' => sanitize_textarea_field((string) ($data['topic_keywords'] ?? '')),
                'research_brief' => wp_kses_post((string) ($data['research_brief'] ?? '')),
                'title_prompt' => wp_kses_post((string) ($data['title_prompt'] ?? '')),
                'article_prompt' => wp_kses_post((string) ($data['article_prompt'] ?? '')),
                'image_prompt' => wp_kses_post((string) ($data['image_prompt'] ?? '')),
                'web_search_mode' => MAIE_Options::allowed_web_search_mode(sanitize_key((string) ($data['web_search_mode'] ?? 'inherit'))),
                'text_model_override' => self::sanitize_optional_choice((string) ($data['text_model_override'] ?? ''), array_keys(MAIE_Options::text_models())),
                'qa_model_override' => self::sanitize_optional_choice((string) ($data['qa_model_override'] ?? ''), array_keys(MAIE_Options::qa_models())),
                'vision_model_override' => self::sanitize_optional_choice((string) ($data['vision_model_override'] ?? ''), array_keys(MAIE_Options::vision_models())),
                'image_model_override' => self::sanitize_optional_choice((string) ($data['image_model_override'] ?? ''), array_keys(MAIE_Options::image_models())),
                'image_size_override' => self::sanitize_optional_choice((string) ($data['image_size_override'] ?? ''), array_keys(MAIE_Options::image_sizes())),
                'image_quality_override' => self::sanitize_optional_choice((string) ($data['image_quality_override'] ?? ''), array_keys(MAIE_Options::image_qualities())),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            [
                '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d',
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            ],
            ['%d']
        );

        return $updated !== false;
    }

    public static function toggle_profile(int $id): bool
    {
        $profile = self::get_profile($id);
        if (!$profile) {
            return false;
        }

        global $wpdb;
        $table = self::profiles_table();
        $new_value = !empty($profile['enabled']) ? 0 : 1;

        return $wpdb->update(
            $table,
            ['enabled' => $new_value, 'updated_at' => current_time('mysql')],
            ['id' => $id],
            ['%d', '%s'],
            ['%d']
        ) !== false;
    }

    public static function create_job(int $profile_id, string $status = 'queued', string $step = 'queued', string $message = ''): int
    {
        global $wpdb;
        $table = self::jobs_table();
        $now = current_time('mysql');

        $insert_job = static function () use ($wpdb, $table, $profile_id, $status, $step, $message, $now): bool {
            return $wpdb->insert(
                $table,
                [
                    'profile_id' => $profile_id,
                    'status' => sanitize_key($status),
                    'step' => sanitize_key($step),
                    'topic_title' => '',
                    'post_id' => 0,
                    'message' => sanitize_textarea_field($message),
                    'payload' => '',
                    'progress_percent' => 0,
                    'created_at' => $now,
                    'started_at' => null,
                    'finished_at' => null,
                ],
                ['%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s']
            ) !== false;
        };

        $inserted = $insert_job();

        // תיקון עצמי: אם אתר עודכן מגרסה ישנה והטבלה לא קיבלה עדיין את עמודות ההתקדמות,
        // מבצעים ריפוי סכימה ומנסים פעם אחת נוספת.
        if (!$inserted && class_exists('MAIE_Install')) {
            MAIE_Install::ensure_runtime_schema(true);
            $inserted = $insert_job();
        }

        if (!$inserted) {
            $error = sanitize_text_field((string) ($wpdb->last_error ?: 'Database insert failed while creating a new job.'));
            set_transient('maie_last_queue_error', $error, 10 * MINUTE_IN_SECONDS);
            return 0;
        }

        delete_transient('maie_last_queue_error');
        $job_id = (int) $wpdb->insert_id;
        self::add_job_event($job_id, sanitize_key($status), sanitize_key($step), sanitize_textarea_field($message), 0);
        return $job_id;
    }

    public static function get_job(int $job_id): ?array
    {
        global $wpdb;
        $table = self::jobs_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $job_id), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    public static function update_job(int $job_id, array $data): bool
    {
        global $wpdb;
        $table = self::jobs_table();
        $formats = [];
        $clean = [];

        $allowed = [
            'status' => '%s',
            'step' => '%s',
            'topic_title' => '%s',
            'post_id' => '%d',
            'message' => '%s',
            'payload' => '%s',
            'progress_percent' => '%d',
            'started_at' => '%s',
            'finished_at' => '%s',
        ];

        foreach ($allowed as $key => $format) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            switch ($key) {
                case 'post_id':
                    $clean[$key] = absint($value);
                    break;
                case 'progress_percent':
                    $clean[$key] = min(100, max(0, absint($value)));
                    break;
                case 'payload':
                    $clean[$key] = is_string($value) ? wp_unslash($value) : wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;
                case 'status':
                case 'step':
                    $clean[$key] = sanitize_key((string) $value);
                    break;
                case 'started_at':
                case 'finished_at':
                    $clean[$key] = !empty($value) ? sanitize_text_field((string) $value) : null;
                    break;
                default:
                    $clean[$key] = sanitize_textarea_field((string) $value);
                    break;
            }
            $formats[] = $format;
        }

        if (!$clean) {
            return false;
        }

        $current_job = self::get_job($job_id);
        if (array_key_exists('step', $clean) && !array_key_exists('progress_percent', $clean)) {
            $clean['progress_percent'] = self::progress_for_step((string) $clean['step'], absint($current_job['progress_percent'] ?? 0));
            $formats[] = '%d';
        }

        $updated = $wpdb->update($table, $clean, ['id' => $job_id], $formats, ['%d']) !== false;
        if ($updated && array_key_exists('step', $clean)) {
            $status_for_event = array_key_exists('status', $clean)
                ? sanitize_key((string) $clean['status'])
                : sanitize_key((string) ($current_job['status'] ?? 'running'));
            $message_for_event = array_key_exists('message', $clean)
                ? sanitize_textarea_field((string) $clean['message'])
                : '';
            self::add_job_event(
                $job_id,
                $status_for_event !== '' ? $status_for_event : 'running',
                sanitize_key((string) $clean['step']),
                $message_for_event,
                absint($clean['progress_percent'] ?? ($current_job['progress_percent'] ?? 0))
            );
        }

        return $updated;
    }

    public static function get_jobs(int $limit = 50): array
    {
        global $wpdb;
        $table = self::jobs_table();
        $limit = max(1, min(500, $limit));
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public static function count_profiles(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . self::profiles_table());
    }

    public static function count_enabled_profiles(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . self::profiles_table() . ' WHERE enabled = 1');
    }

    public static function count_jobs_by_status(string $status): int
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . self::jobs_table() . ' WHERE status = %s', sanitize_key($status)));
    }

    public static function recent_job_topics(int $profile_id, int $days = 7, int $limit = 30): array
    {
        global $wpdb;
        $table = self::jobs_table();
        $days = max(1, min(30, $days));
        $limit = max(1, min(100, $limit));
        $after = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * $days);

        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT topic_title FROM {$table} WHERE profile_id = %d AND topic_title <> '' AND created_at >= %s ORDER BY id DESC LIMIT %d",
            $profile_id,
            get_date_from_gmt($after),
            $limit
        ));

        return is_array($rows) ? array_filter(array_map('strval', $rows)) : [];
    }

    // רק נושאים שהשתלמו בפועל ונשמרו כפוסטים – לבדיקת כפילויות בלבד
    public static function recent_published_job_topics(int $profile_id, int $days = 14, int $limit = 40): array
    {
        global $wpdb;
        $table = self::jobs_table();
        $days = max(1, min(60, $days));
        $limit = max(1, min(100, $limit));
        $after = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * $days);

        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT topic_title FROM {$table}
             WHERE profile_id = %d AND topic_title <> '' AND status = 'completed' AND created_at >= %s
             ORDER BY id DESC LIMIT %d",
            $profile_id,
            get_date_from_gmt($after),
            $limit
        ));

        return is_array($rows) ? array_filter(array_map('strval', $rows)) : [];
    }

    public static function daily_completed_count(int $profile_id): int
    {
        global $wpdb;
        $table = self::jobs_table();
        $today = current_time('Y-m-d');

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE profile_id = %d AND status = 'completed' AND DATE(created_at) = %s",
            $profile_id,
            $today
        ));
    }

    public static function last_job_created_at(int $profile_id): ?string
    {
        global $wpdb;
        $table = self::jobs_table();
        $profile_id = absint($profile_id);
        if ($profile_id <= 0) {
            return null;
        }

        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT created_at FROM {$table} WHERE profile_id = %d AND status IN ('completed','failed','skipped','running','queued') ORDER BY id DESC LIMIT 1",
            $profile_id
        ));

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function profile_is_due(array $profile): bool
    {
        global $wpdb;
        $table = self::jobs_table();
        $profile_id = absint($profile['id'] ?? 0);
        if ($profile_id <= 0) {
            return false;
        }

        $interval = max(5, absint($profile['generation_interval_minutes'] ?? 180));
        $last_job_time = self::last_job_created_at($profile_id);

        if (!$last_job_time) {
            return true;
        }

        $last_timestamp = strtotime((string) $last_job_time);
        if (!$last_timestamp) {
            return true;
        }

        return (time() - $last_timestamp) >= ($interval * MINUTE_IN_SECONDS);
    }


    public static function add_job_event(int $job_id, string $status, string $step, string $message, int $progress_percent): bool
    {
        global $wpdb;
        $table = self::job_events_table();
        $job_id = absint($job_id);
        if ($job_id <= 0) {
            return false;
        }

        return $wpdb->insert(
            $table,
            [
                'job_id' => $job_id,
                'status' => sanitize_key($status),
                'step' => sanitize_key($step),
                'message' => sanitize_textarea_field($message),
                'progress_percent' => min(100, max(0, absint($progress_percent))),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%d', '%s']
        ) !== false;
    }

    public static function get_job_events(int $job_id, int $limit = 60): array
    {
        global $wpdb;
        $table = self::job_events_table();
        $job_id = absint($job_id);
        $limit = max(1, min(200, $limit));
        if ($job_id <= 0) {
            return [];
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE job_id = %d ORDER BY id ASC LIMIT %d",
            $job_id,
            $limit
        ), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public static function delete_old_jobs(int $days = 30): int
    {
        global $wpdb;
        $days = max(7, min(365, $days));
        $cutoff = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * $days);

        $jobs_table = self::jobs_table();
        $events_table = self::job_events_table();

        // מחק תחילה את האירועים המשויכים למשימות ישנות
        $wpdb->query($wpdb->prepare(
            "DELETE e FROM {$events_table} e
             INNER JOIN {$jobs_table} j ON j.id = e.job_id
             WHERE j.status IN ('completed','failed','skipped')
               AND j.created_at < %s",
            $cutoff
        ));

        // מחק את המשימות הישנות עצמן
        $deleted = (int) $wpdb->query($wpdb->prepare(
            "DELETE FROM {$jobs_table}
             WHERE status IN ('completed','failed','skipped')
               AND created_at < %s",
            $cutoff
        ));

        return $deleted;
    }

    public static function progress_for_step(string $step, int $fallback = 0): int
    {
        $map = [
            'queued' => 0,
            'manual_queue' => 0,
            'research' => 10,
            'topic_selected' => 22,
            'title' => 35,
            'article' => 52,
            'editorial_review' => 68,
            'image_metadata' => 78,
            'image_generation' => 86,
            'image_review' => 92,
            'post_insert' => 97,
            'completed' => 100,
        ];
        $step = sanitize_key($step);
        return array_key_exists($step, $map) ? (int) $map[$step] : min(100, max(0, absint($fallback)));
    }


    public static function record_view(int $post_id, string $visitor_hash): bool
    {
        global $wpdb;
        $table = self::views_table();
        $post_id = absint($post_id);
        $visitor_hash = sanitize_text_field($visitor_hash);
        if ($post_id <= 0 || $visitor_hash === '') {
            return false;
        }

        $view_date = current_time('Y-m-d');
        $now = current_time('mysql');
        $existing_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE post_id = %d AND view_date = %s AND visitor_hash = %s LIMIT 1",
            $post_id,
            $view_date,
            $visitor_hash
        ));

        if ($existing_id > 0) {
            return $wpdb->query($wpdb->prepare(
                "UPDATE {$table} SET views = views + 1, last_seen_at = %s WHERE id = %d",
                $now,
                $existing_id
            )) !== false;
        }

        return $wpdb->insert(
            $table,
            [
                'post_id' => $post_id,
                'view_date' => $view_date,
                'visitor_hash' => $visitor_hash,
                'views' => 1,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ],
            ['%d', '%s', '%s', '%d', '%s', '%s']
        ) !== false;
    }

    public static function get_post_view_totals(int $post_id): array
    {
        global $wpdb;
        $table = self::views_table();
        $post_id = absint($post_id);
        if ($post_id <= 0) {
            return ['views' => 0, 'unique_visitors' => 0];
        }

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT COALESCE(SUM(views), 0) AS views, COUNT(DISTINCT visitor_hash) AS unique_visitors FROM {$table} WHERE post_id = %d",
            $post_id
        ), ARRAY_A);

        return [
            'views' => absint($row['views'] ?? 0),
            'unique_visitors' => absint($row['unique_visitors'] ?? 0),
        ];
    }

    public static function get_stats_filters(array $raw): array
    {
        $today = current_time('Y-m-d');
        $default_start = gmdate('Y-m-d', current_time('timestamp') - (30 * DAY_IN_SECONDS));
        $start_date = sanitize_text_field((string) ($raw['start_date'] ?? $default_start));
        $end_date = sanitize_text_field((string) ($raw['end_date'] ?? $today));
        $profile_id = absint($raw['profile_id'] ?? 0);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
            $start_date = $default_start;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            $end_date = $today;
        }
        if ($start_date > $end_date) {
            [$start_date, $end_date] = [$end_date, $start_date];
        }

        return [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'profile_id' => $profile_id,
        ];
    }

    public static function get_stats_totals(array $filters): array
    {
        global $wpdb;
        $views = self::views_table();
        $posts = $wpdb->posts;
        $postmeta = $wpdb->postmeta;
        $start = sanitize_text_field((string) ($filters['start_date'] ?? current_time('Y-m-d')));
        $end = sanitize_text_field((string) ($filters['end_date'] ?? current_time('Y-m-d')));
        $profile_id = absint($filters['profile_id'] ?? 0);

        $profile_join = '';
        $profile_where = '';
        $params = [$start, $end];
        if ($profile_id > 0) {
            $profile_join = " INNER JOIN {$postmeta} pm_profile ON pm_profile.post_id = p.ID AND pm_profile.meta_key = '_maie_profile_id' ";
            $profile_where = ' AND pm_profile.meta_value = %d ';
            $params[] = $profile_id;
        }

        $sql = "SELECT COUNT(DISTINCT p.ID) AS articles,
                       COALESCE(SUM(v.views), 0) AS views,
                       COUNT(DISTINCT v.visitor_hash) AS unique_visitors
                FROM {$posts} p
                INNER JOIN {$postmeta} pm_generated ON pm_generated.post_id = p.ID AND pm_generated.meta_key = '_maie_generated' AND pm_generated.meta_value = '1'
                {$profile_join}
                LEFT JOIN {$views} v ON v.post_id = p.ID
                WHERE p.post_type = 'post'
                  AND DATE(p.post_date) BETWEEN %s AND %s
                  {$profile_where}";

        $prepared = $wpdb->prepare($sql, $params);
        $row = $wpdb->get_row($prepared, ARRAY_A);

        return [
            'articles' => absint($row['articles'] ?? 0),
            'views' => absint($row['views'] ?? 0),
            'unique_visitors' => absint($row['unique_visitors'] ?? 0),
        ];
    }

    public static function get_article_stats(array $filters, int $limit = 200): array
    {
        global $wpdb;
        $views = self::views_table();
        $posts = $wpdb->posts;
        $postmeta = $wpdb->postmeta;
        $start = sanitize_text_field((string) ($filters['start_date'] ?? current_time('Y-m-d')));
        $end = sanitize_text_field((string) ($filters['end_date'] ?? current_time('Y-m-d')));
        $profile_id = absint($filters['profile_id'] ?? 0);
        $limit = max(1, min(500, $limit));

        $profile_join = " LEFT JOIN {$postmeta} pm_profile ON pm_profile.post_id = p.ID AND pm_profile.meta_key = '_maie_profile_id' ";
        $profile_where = '';
        $params = [$start, $end];
        if ($profile_id > 0) {
            $profile_where = ' AND pm_profile.meta_value = %d ';
            $params[] = $profile_id;
        }
        $params[] = $limit;

        $sql = "SELECT p.ID AS post_id, p.post_title, p.post_status, p.post_date,
                       COALESCE(pm_profile.meta_value, '0') AS profile_id,
                       COALESCE(SUM(v.views), 0) AS views,
                       COUNT(DISTINCT v.visitor_hash) AS unique_visitors
                FROM {$posts} p
                INNER JOIN {$postmeta} pm_generated ON pm_generated.post_id = p.ID AND pm_generated.meta_key = '_maie_generated' AND pm_generated.meta_value = '1'
                {$profile_join}
                LEFT JOIN {$views} v ON v.post_id = p.ID
                WHERE p.post_type = 'post'
                  AND DATE(p.post_date) BETWEEN %s AND %s
                  {$profile_where}
                GROUP BY p.ID, p.post_title, p.post_status, p.post_date, pm_profile.meta_value
                ORDER BY views DESC, p.post_date DESC
                LIMIT %d";

        $prepared = $wpdb->prepare($sql, $params);
        $rows = $wpdb->get_results($prepared, ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    private static function sanitize_optional_choice(string $value, array $allowed): string
    {
        $value = sanitize_text_field($value);
        return in_array($value, $allowed, true) ? $value : '';
    }

    private static function sanitize_post_status(string $status): string
    {
        $allowed = ['draft', 'publish', 'pending'];
        return in_array($status, $allowed, true) ? $status : 'draft';
    }

    private static function sanitize_content_mode(string $mode): string
    {
        $allowed = ['news', 'evergreen', 'press_release'];
        return in_array($mode, $allowed, true) ? $mode : 'news';
    }
}
