<?php

if (!defined('ABSPATH')) {
    exit;
}

final class MAIE_Install
{
    public static function activate(): void
    {
        // רישום לוח הזמנים המותאם אישית לפני שמנסים לתזמן את ה-Cron
        // (ה-filter רגיל נרשם ב-plugins_loaded שכבר עבר בעת ה-activation hook)
        add_filter('cron_schedules', ['MAIE_Cron', 'add_custom_schedule']);

        self::create_tables();
        self::ensure_settings();
        self::seed_profiles();
        self::schedule_cron();
        update_option('maie_db_version', MAIE_DB_VERSION, false);
    }

    public static function deactivate(): void
    {
        $timestamp = wp_next_scheduled('maie_cron_event');
        while ($timestamp) {
            wp_unschedule_event($timestamp, 'maie_cron_event');
            $timestamp = wp_next_scheduled('maie_cron_event');
        }

        $manual_timestamp = wp_next_scheduled('maie_manual_cron_run_event');
        while ($manual_timestamp) {
            wp_unschedule_event($manual_timestamp, 'maie_manual_cron_run_event');
            $manual_timestamp = wp_next_scheduled('maie_manual_cron_run_event');
        }
    }

    public static function maybe_upgrade(): void
    {
        $installed = (string) get_option('maie_db_version', '');
        if ($installed !== MAIE_DB_VERSION) {
            self::create_tables();
            self::ensure_settings();
            self::seed_profiles();
            self::sync_blueprint_fields();
            self::reschedule_cron();
            update_option('maie_db_version', MAIE_DB_VERSION, false);
        }

        // גרסאות קודמות יכלו לעדכן את מספר גרסת הסכימה לפני שהשינוי במסד הושלם בפועל.
        // לכן אנו מבצעים בדיקת בריאות קצרה ומתקנים חסרים באופן עצמאי.
        self::ensure_runtime_schema();

        // רשת ביטחון: אם אירוע ה-Cron החוזר נעלם או לא נרשם באתר לאחר עדכון,
        // משחזרים אותו בכל טעינת התוסף. זה אינו מפעיל יצירה כשהאוטומציה כבויה,
        // אלא רק דואג שהשעון יהיה מתוזמן ומוכן.
        self::ensure_cron_scheduled();
    }

    public static function ensure_runtime_schema(bool $force = false): void
    {
        static $checked = false;

        if ($checked && !$force) {
            return;
        }
        $checked = true;

        global $wpdb;

        $jobs = MAIE_DB::jobs_table();
        $events = MAIE_DB::job_events_table();
        $profiles = MAIE_DB::profiles_table();
        $views = MAIE_DB::views_table();

        $needs_repair = !self::table_exists($profiles)
            || !self::table_exists($jobs)
            || !self::table_exists($views)
            || !self::table_exists($events)
            || !self::column_exists($jobs, 'progress_percent');

        if (!$needs_repair && !$force) {
            return;
        }

        self::create_tables();

        // dbDelta אמור להוסיף את העמודה, אך אנו מוסיפים רשת ביטחון למקרה שהשדרוג לא הושלם.
        if (self::table_exists($jobs) && !self::column_exists($jobs, 'progress_percent')) {
            $wpdb->query("ALTER TABLE `{$jobs}` ADD `progress_percent` int(11) NOT NULL DEFAULT 0 AFTER `payload`");
        }

        update_option('maie_last_schema_repair_at', current_time('mysql'), false);
    }

    private static function table_exists(string $table): bool
    {
        global $wpdb;
        $like = $wpdb->esc_like($table);
        return (string) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like)) === $table;
    }

    private static function column_exists(string $table, string $column): bool
    {
        global $wpdb;
        if (!self::table_exists($table)) {
            return false;
        }
        return !empty($wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM `{$table}` LIKE %s", $column)));
    }

    private static function create_tables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $profiles = MAIE_DB::profiles_table();
        $jobs = MAIE_DB::jobs_table();
        $views = MAIE_DB::views_table();
        $events = MAIE_DB::job_events_table();

        $sql_profiles = "CREATE TABLE {$profiles} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            category_id bigint(20) unsigned NOT NULL DEFAULT 0,
            category_name varchar(191) NOT NULL,
            category_slug varchar(191) NOT NULL,
            menu_group varchar(191) NOT NULL DEFAULT 'כללי',
            content_mode varchar(30) NOT NULL DEFAULT 'news',
            enabled tinyint(1) NOT NULL DEFAULT 1,
            post_status varchar(20) NOT NULL DEFAULT 'draft',
            search_window_hours int(11) NOT NULL DEFAULT 12,
            generation_interval_minutes int(11) NOT NULL DEFAULT 180,
            max_posts_per_day int(11) NOT NULL DEFAULT 1,
            prefer_israel tinyint(1) NOT NULL DEFAULT 0,
            preferred_domains longtext NULL,
            blocked_domains longtext NULL,
            topic_keywords longtext NULL,
            research_brief longtext NULL,
            title_prompt longtext NULL,
            article_prompt longtext NULL,
            image_prompt longtext NULL,
            web_search_mode varchar(20) NOT NULL DEFAULT 'inherit',
            text_model_override varchar(100) NOT NULL DEFAULT '',
            qa_model_override varchar(100) NOT NULL DEFAULT '',
            vision_model_override varchar(100) NOT NULL DEFAULT '',
            image_model_override varchar(100) NOT NULL DEFAULT '',
            image_size_override varchar(30) NOT NULL DEFAULT '',
            image_quality_override varchar(20) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY category_id (category_id),
            KEY enabled (enabled),
            KEY content_mode (content_mode)
        ) {$charset_collate};";

        $sql_jobs = "CREATE TABLE {$jobs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            profile_id bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(40) NOT NULL DEFAULT 'queued',
            step varchar(100) NOT NULL DEFAULT 'queued',
            topic_title text NULL,
            post_id bigint(20) unsigned NOT NULL DEFAULT 0,
            message longtext NULL,
            payload longtext NULL,
            progress_percent int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            started_at datetime NULL,
            finished_at datetime NULL,
            PRIMARY KEY  (id),
            KEY profile_id (profile_id),
            KEY status (status),
            KEY post_id (post_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        $sql_views = "CREATE TABLE {$views} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            view_date date NOT NULL,
            visitor_hash char(64) NOT NULL,
            views int(11) NOT NULL DEFAULT 1,
            first_seen_at datetime NOT NULL,
            last_seen_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY post_day_visitor (post_id, view_date, visitor_hash),
            KEY post_id (post_id),
            KEY view_date (view_date)
        ) {$charset_collate};";

        $sql_events = "CREATE TABLE {$events} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(40) NOT NULL DEFAULT 'running',
            step varchar(100) NOT NULL DEFAULT 'queued',
            message longtext NULL,
            progress_percent int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY job_id (job_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta($sql_profiles);
        dbDelta($sql_jobs);
        dbDelta($sql_views);
        dbDelta($sql_events);
    }

    private static function ensure_settings(): void
    {
        $settings = get_option('maie_settings', null);
        if (!is_array($settings)) {
            add_option('maie_settings', MAIE_Prompts::default_settings(), '', false);
            return;
        }

        $merged = array_merge(MAIE_Prompts::default_settings(), $settings);
        update_option('maie_settings', $merged, false);
    }

    private static function seed_profiles(): void
    {
        foreach (MAIE_Prompts::default_profile_blueprints() as $blueprint) {
            $name = (string) ($blueprint['category_name'] ?? '');
            if ($name === '' || MAIE_DB::get_profile_by_category_name($name)) {
                continue;
            }

            $profile = MAIE_Prompts::build_profile_defaults($blueprint);
            $term = self::find_category_term($name);
            if ($term instanceof WP_Term) {
                $profile['category_id'] = (int) $term->term_id;
                $profile['category_slug'] = (string) $term->slug;
            }

            MAIE_DB::insert_profile($profile);
        }
    }

    // מסנכרן שדות blueprint לפרופילים קיימים: research_brief, preferred_domains, topic_keywords.
    // רץ בכל שדרוג גרסה כדי שתיקוני prompt ומקורות יחולו גם על פרופילים ישנים.
    public static function sync_blueprint_fields(): void
    {
        foreach (MAIE_Prompts::default_profile_blueprints() as $blueprint) {
            $name = (string) ($blueprint['category_name'] ?? '');
            if ($name === '') {
                continue;
            }
            $existing = MAIE_DB::get_profile_by_category_name($name);
            if (!$existing) {
                continue;
            }
            $mode = sanitize_key((string) ($blueprint['content_mode'] ?? 'news'));
            $focus = (string) ($blueprint['research_focus'] ?? '');
            MAIE_DB::update_profile((int) $existing['id'], [
                'research_brief'   => MAIE_Prompts::research_brief($name, $mode, $focus),
                'preferred_domains' => (string) ($blueprint['preferred_domains'] ?? ''),
                'topic_keywords'   => (string) ($blueprint['topic_keywords'] ?? ''),
                'title_prompt'     => MAIE_Prompts::title_prompt($name, $mode),
                'article_prompt'   => MAIE_Prompts::article_prompt($name, $mode),
            ]);
        }
    }

    private static function find_category_term(string $name): ?WP_Term
    {
        $term = get_term_by('name', $name, 'category');
        if ($term instanceof WP_Term) {
            return $term;
        }

        $terms = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false,
            'number' => 0,
        ]);

        if (!is_array($terms)) {
            return null;
        }

        $normalized_target = self::normalize_label($name);
        foreach ($terms as $candidate) {
            if (!$candidate instanceof WP_Term) {
                continue;
            }
            if (self::normalize_label($candidate->name) === $normalized_target) {
                return $candidate;
            }
        }

        return null;
    }

    private static function normalize_label(string $label): string
    {
        $label = html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $label = wp_strip_all_tags($label);
        $label = preg_replace('/[\s\-–—־]+/u', ' ', $label) ?: $label;
        $label = trim(mb_strtolower($label, 'UTF-8'));
        return $label;
    }

    public static function ensure_cron_scheduled(): void
    {
        if (!wp_next_scheduled('maie_cron_event')) {
            self::schedule_cron();
        }
    }

    private static function reschedule_cron(): void
    {
        $timestamp = wp_next_scheduled('maie_cron_event');
        while ($timestamp) {
            wp_unschedule_event($timestamp, 'maie_cron_event');
            $timestamp = wp_next_scheduled('maie_cron_event');
        }
        self::schedule_cron();
    }

    private static function schedule_cron(): void
    {
        if (!wp_next_scheduled('maie_cron_event')) {
            wp_schedule_event(time() + 5 * MINUTE_IN_SECONDS, 'maie_every_five_minutes', 'maie_cron_event');
        }
    }
}
