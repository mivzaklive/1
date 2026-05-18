<?php

if (!defined('ABSPATH')) {
    exit;
}

final class MAIE_Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [__CLASS__, 'register_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);

        add_action('admin_post_maie_save_settings', [__CLASS__, 'handle_save_settings']);
        add_action('admin_post_maie_save_profile', [__CLASS__, 'handle_save_profile']);
        add_action('admin_post_maie_toggle_profile', [__CLASS__, 'handle_toggle_profile']);
        add_action('admin_post_maie_generate_now', [__CLASS__, 'handle_generate_now']);
        add_action('admin_post_maie_run_cron_now', [__CLASS__, 'handle_run_cron_now']);
        add_action('admin_post_maie_cleanup_logs', [__CLASS__, 'handle_cleanup_logs']);
        add_action('wp_ajax_maie_job_status', [__CLASS__, 'ajax_job_status']);
        add_action('wp_ajax_maie_kick_job', [__CLASS__, 'ajax_kick_job']);
    }

    public static function register_menu(): void
    {
        add_menu_page(
            'מנוע כתבות AI – מבזק לייב',
            'מנוע כתבות AI',
            'manage_options',
            'maie-dashboard',
            [__CLASS__, 'render_dashboard'],
            'dashicons-welcome-write-blog',
            58
        );

        add_submenu_page(
            'maie-dashboard',
            'לוח בקרה',
            'לוח בקרה',
            'manage_options',
            'maie-dashboard',
            [__CLASS__, 'render_dashboard']
        );

        add_submenu_page(
            'maie-dashboard',
            'קטגוריות ופרופילים',
            'קטגוריות ופרופילים',
            'manage_options',
            'maie-profiles',
            [__CLASS__, 'render_profiles']
        );

        add_submenu_page(
            'maie-dashboard',
            'הגדרות',
            'הגדרות',
            'manage_options',
            'maie-settings',
            [__CLASS__, 'render_settings']
        );

        add_submenu_page(
            'maie-dashboard',
            'לוגים ומשימות',
            'לוגים ומשימות',
            'manage_options',
            'maie-logs',
            [__CLASS__, 'render_logs']
        );


        add_submenu_page(
            'maie-dashboard',
            'סטטיסטיקות צפייה',
            'סטטיסטיקות צפייה',
            'manage_options',
            'maie-stats',
            [__CLASS__, 'render_stats']
        );
    }

    public static function enqueue_assets(string $hook): void
    {
        if (!str_contains($hook, 'maie')) {
            return;
        }

        wp_enqueue_style(
            'maie-admin',
            MAIE_PLUGIN_URL . 'assets/admin.css',
            [],
            MAIE_VERSION
        );

        wp_enqueue_script(
            'maie-admin',
            MAIE_PLUGIN_URL . 'assets/admin.js',
            [],
            MAIE_VERSION,
            true
        );

        wp_localize_script('maie-admin', 'maieAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'jobStatusNonce' => wp_create_nonce('maie_job_status'),
            'pollMs' => 1500,
        ]);
    }

    public static function render_dashboard(): void
    {
        self::guard();
        $settings = MAIE_DB::get_settings();
        $profiles_count = MAIE_DB::count_profiles();
        $enabled_profiles = MAIE_DB::count_enabled_profiles();
        $completed_jobs = MAIE_DB::count_jobs_by_status('completed');
        $failed_jobs = MAIE_DB::count_jobs_by_status('failed');
        $cron_time = wp_next_scheduled('maie_cron_event');
        $recent_jobs = MAIE_DB::get_jobs(8);

        echo '<div class="wrap maie-wrap" dir="rtl">';
        echo '<h1>מנוע כתבות AI – מבזק לייב</h1>';
        self::render_notice();
        echo '<p class="maie-lead">מערכת ליצירת כתבות אוטומטית לפי קטגוריות, עם מחקר רשת, כתיבה עיתונאית, תמונה ראשית ובקרת איכות.</p>';
        if (empty($settings['api_key'])) {
            echo '<div class="notice notice-warning"><p><strong>נדרש: הגדרת מפתח API</strong> – לפני השימוש במנוע, הזן את מפתח ה-OpenAI API שלך ב<a href="' . esc_url(admin_url('admin.php?page=maie-settings')) . '">הגדרות</a>.</p></div>';
        }
        if (empty($settings['auto_cron_enabled'])) {
            echo '<div class="notice notice-info"><p><strong>האוטומציה כבויה</strong> – ניתן ליצור כתבות ידנית מעמוד <a href="' . esc_url(admin_url('admin.php?page=maie-profiles')) . '">קטגוריות ופרופילים</a>. להפעלת יצירה אוטומטית, אפשר את "הפעל יצירה אוטומטית" ב<a href="' . esc_url(admin_url('admin.php?page=maie-settings')) . '">הגדרות</a>.</p></div>';
        }

        echo '<div class="maie-cards">';
        self::stat_card('פרופילים', (string) $profiles_count, 'קטגוריות מוכנות להפעלה');
        self::stat_card('פעילים', (string) $enabled_profiles, 'פרופילים המסומנים כפעילים');
        self::stat_card('הושלמו', (string) $completed_jobs, 'משימות שסיימו יצירת כתבה');
        self::stat_card('נכשלו', (string) $failed_jobs, 'משימות שנעצרו בבדיקה או בשגיאה');
        echo '</div>';

        echo '<div class="maie-grid">';
        echo '<section class="maie-panel">';
        echo '<h2>מצב מערכת</h2>';
        echo '<table class="widefat striped maie-mini-table"><tbody>';
        echo '<tr><th>מפתח OpenAI API</th><td>' . (!empty($settings['api_key']) ? '<span class="maie-ok">הוגדר</span>' : '<span class="maie-bad">לא הוגדר</span>') . '</td></tr>';
        echo '<tr><th>מודל טקסט</th><td>' . esc_html((string) ($settings['text_model'] ?? '')) . '</td></tr>';
        echo '<tr><th>מודל תמונה</th><td>' . esc_html((string) ($settings['image_model'] ?? '')) . '</td></tr>';
        echo '<tr><th>בדיקת איכות מערכתית</th><td>' . (!empty($settings['qa_enabled']) ? 'פעילה' : 'כבויה') . '</td></tr>';
        echo '<tr><th>בדיקת תמונה</th><td>' . (!empty($settings['image_qa_enabled']) ? 'פעילה' : 'כבויה') . '</td></tr>';
        echo '<tr><th>אוטומציה</th><td>' . (!empty($settings['auto_cron_enabled']) ? '<span class="maie-ok">פעילה</span>' : '<span class="maie-muted">כבויה</span>') . '</td></tr>';
        echo '<tr><th>הרצת Cron הבאה</th><td>' . ($cron_time ? esc_html(wp_date('d/m/Y H:i', $cron_time)) : 'לא מתוזמן') . '</td></tr>';
        echo '</tbody></table>';
        echo '<p class="maie-actions"><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=maie-settings')) . '">הגדרות</a> <a class="button" href="' . esc_url(admin_url('admin.php?page=maie-profiles')) . '">ניהול קטגוריות</a></p>';
        echo '</section>';

        echo '<section class="maie-panel">';
        echo '<h2>משימות אחרונות</h2>';
        self::render_jobs_table($recent_jobs, false);
        echo '<p class="maie-actions"><a class="button" href="' . esc_url(admin_url('admin.php?page=maie-logs')) . '">לכל הלוגים</a></p>';
        echo '</section>';
        echo '</div>';
        echo '</div>';
    }

    public static function render_settings(): void
    {
        self::guard();
        $settings = MAIE_DB::get_settings();
        $users = get_users(['fields' => ['ID', 'display_name'], 'orderby' => 'display_name', 'order' => 'ASC']);

        echo '<div class="wrap maie-wrap" dir="rtl">';
        echo '<h1>הגדרות מנוע כתבות AI</h1>';
        self::render_notice();
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="maie-form">';
        wp_nonce_field('maie_save_settings');
        echo '<input type="hidden" name="action" value="maie_save_settings">';

        echo '<section class="maie-panel">';
        echo '<h2>חיבור ל-OpenAI</h2>';
        self::text_field('api_key', 'OpenAI API Key', (string) ($settings['api_key'] ?? ''), 'password', 'המפתח נשמר בבסיס הנתונים של וורדפרס. אין לחשוף אותו בצילום מסך.');
        echo '</section>';

        echo '<section class="maie-panel">';
        echo '<h2>מודלי AI ועלויות</h2>';
        echo '<p class="description">האפשרויות המסומנות ברקע מודגש הן ברירות המחדל המומלצות. ניתן לחרוג מהן בפרופיל מסוים לצורך חיסכון.</p>';
        self::choice_cards_field('text_model', 'מודל טקסט ראשי', (string) ($settings['text_model'] ?? 'gpt-4o'), MAIE_Options::text_models(), 'משמש למחקר, כותרת, כתבה והפקת מטא-נתונים.');
        self::choice_cards_field('qa_model', 'מודל בקרת איכות', (string) ($settings['qa_model'] ?? 'gpt-4o'), MAIE_Options::qa_models(), 'בודק דיוק, ניסוח, הזיות, כפילויות והתאמה לפרומפט.');
        self::choice_cards_field('vision_model', 'מודל בדיקת תמונה', (string) ($settings['vision_model'] ?? 'gpt-4o'), MAIE_Options::vision_models(), 'בודק אם התמונה קשורה לכתבה ואם הופיעו בה טקסט, לוגו או בעיות איכות.');
        self::choice_cards_field('image_model', 'מודל יצירת תמונה', (string) ($settings['image_model'] ?? 'gpt-image-1'), MAIE_Options::image_models(), 'יוצר את התמונה הראשית של הכתבה.');
        echo '</section>';

        echo '<section class="maie-panel">';
        echo '<h2>פרסום ואוטומציה</h2>';
        echo '<div class="maie-field"><label for="default_post_author">מחבר ברירת מחדל</label><select id="default_post_author" name="default_post_author">';
        foreach ($users as $user) {
            $selected = selected(absint($settings['default_post_author'] ?? 1), absint($user->ID), false);
            echo '<option value="' . esc_attr((string) $user->ID) . '" ' . $selected . '>' . esc_html($user->display_name) . '</option>';
        }
        echo '</select></div>';
        self::checkbox_field('auto_cron_enabled', 'הפעל יצירה אוטומטית ב-Cron', !empty($settings['auto_cron_enabled']), 'המערכת תסרוק פרופילים פעילים ותיצור כתבות לפי התדירות שהוגדרה.');
        self::checkbox_field('web_search_enabled', 'אפשר חיפוש רשת מובנה', !empty($settings['web_search_enabled']), 'נדרש לאיתור נושאים עדכניים. בפרופיל בודד ניתן לכבות כדי לחסוך בעלויות בתוכן שאינו תלוי בזמן אמת.');
        self::checkbox_field('qa_enabled', 'הפעל בדיקת איכות מערכתית לפני פרסום', !empty($settings['qa_enabled']), 'מונע פרסום כתבות חלשות, מנופחות או לא מדויקות.');
        self::checkbox_field('image_qa_enabled', 'הפעל בדיקת תמונה לפני שמירה', !empty($settings['image_qa_enabled']), 'פוסל תמונות לא רלוונטיות או תמונות עם טקסט.');
        self::checkbox_field('strict_publish_requires_image', 'אל תשמור כתבה אם לא נוצרה תמונה ראשית תקינה', !empty($settings['strict_publish_requires_image']), 'מומלץ להשאיר פעיל.');
        self::checkbox_field('debug_enabled', 'מצב Debug מורחב בלוגים', !empty($settings['debug_enabled']), 'שומר יותר מידע פנימי במשימות.');
        self::select_field(
            'max_jobs_per_cron',
            'כמה פרופילים לעבד בכל סבב Cron',
            absint($settings['max_jobs_per_cron'] ?? 1),
            self::cron_jobs_per_run_options(),
            'כאשר יש הרבה פרופילים שהגיעו למועד יצירה, זו הכמות המרבית שהמערכת תנסה לעבד בכל סבב. מומלץ 5 באתר עם הרבה קטגוריות פעילות.'
        );
        self::number_field('max_image_regeneration_attempts', 'ניסיונות יצירה מחדש של תמונה לאחר פסילה', absint($settings['max_image_regeneration_attempts'] ?? 2), 0, 5);
        self::select_field(
            'jobs_retention_days',
            'שמירת לוגים',
            absint($settings['jobs_retention_days'] ?? 30),
            [7 => '7 ימים', 14 => '14 ימים', 30 => '30 ימים', 60 => '60 ימים', 90 => '90 ימים'],
            'כמה ימים לשמור היסטוריית משימות ולוגים. ניקוי אוטומטי מתבצע אחת ליום.'
        );
        echo '</section>';

        echo '<section class="maie-panel">';
        echo '<h2>הגדרות תמונה</h2>';
        self::choice_cards_field('image_size', 'גודל תמונה', (string) ($settings['image_size'] ?? '1536x1024'), MAIE_Options::image_sizes(), 'ברירת המחדל מיועדת לתמונה ראשית אופקית ולתצוגה מיטבית ב-Google Discover וב-Google News.');
        self::choice_cards_field('image_quality', 'איכות תמונה', (string) ($settings['image_quality'] ?? 'medium'), MAIE_Options::image_qualities(), 'איכות גבוהה יותר מגדילה עלות וזמן יצירה.');
        echo '<div class="maie-field"><label for="image_output_format">פורמט תמונה</label><select id="image_output_format" name="image_output_format">';
        foreach (['webp' => 'WebP – מומלץ', 'jpeg' => 'JPEG', 'png' => 'PNG'] as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected((string) ($settings['image_output_format'] ?? 'webp'), $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></div>';
        self::number_field('image_output_compression', 'דחיסת תמונה', absint($settings['image_output_compression'] ?? 90), 1, 100, '%');
        echo '</section>';

        submit_button('שמור הגדרות');
        echo '</form>';
        echo '</div>';
    }

    public static function render_profiles(): void
    {
        self::guard();
        $action = sanitize_key((string) ($_GET['maie_action'] ?? ''));
        $profile_id = absint($_GET['profile_id'] ?? 0);

        if ($action === 'edit' && $profile_id > 0) {
            self::render_profile_edit($profile_id);
            return;
        }

        $profile_filters = self::get_profile_filters(wp_unslash($_GET));
        $profiles = MAIE_DB::get_profiles($profile_filters);
        echo '<div class="wrap maie-wrap" dir="rtl">';
        echo '<h1>קטגוריות ופרופילי יצירה</h1>';
        self::render_notice();
        echo '<p class="maie-lead">הפרופילים נטענו מראש לפי הקטגוריות הפעילות בתפריט האתר. כל פרופיל כולל נושאים, מקורות ופרומפטים מותאמים.</p>';
        self::render_profile_filters($profile_filters);

        echo '<div class="maie-table-wrap"><table class="widefat striped maie-table">';
        $frequency_sort_url = self::profile_frequency_sort_url($profile_filters);
        $frequency_sort_label = self::profile_frequency_sort_label($profile_filters);
        echo '<thead><tr><th>קטגוריה</th><th>קבוצה</th><th>סוג</th><th>מיפוי וורדפרס</th><th>חלון</th><th><a class="maie-sortable-header" href="' . esc_url($frequency_sort_url) . '">תדירות / תקרה ' . esc_html($frequency_sort_label) . '</a></th><th>פעיל</th><th>פעולות</th></tr></thead><tbody>';
        foreach ($profiles as $profile) {
            $id = absint($profile['id'] ?? 0);
            $mapped = absint($profile['category_id'] ?? 0) > 0 ? 'ממופה' : 'לא ממופה';
            $mapped_class = absint($profile['category_id'] ?? 0) > 0 ? 'maie-ok' : 'maie-bad';
            $enabled = !empty($profile['enabled']);
            $toggle_url = wp_nonce_url(admin_url('admin-post.php?action=maie_toggle_profile&profile_id=' . $id), 'maie_toggle_profile_' . $id);
            $generate_draft_url = wp_nonce_url(
                admin_url('admin-post.php?action=maie_generate_now&profile_id=' . $id . '&post_status=draft'),
                'maie_generate_now_' . $id . '_draft'
            );
            $generate_publish_url = wp_nonce_url(
                admin_url('admin-post.php?action=maie_generate_now&profile_id=' . $id . '&post_status=publish'),
                'maie_generate_now_' . $id . '_publish'
            );
            $edit_url = admin_url('admin.php?page=maie-profiles&maie_action=edit&profile_id=' . $id);

            echo '<tr>';
            echo '<td><strong>' . esc_html((string) ($profile['category_name'] ?? '')) . '</strong></td>';
            echo '<td>' . esc_html((string) ($profile['menu_group'] ?? '')) . '</td>';
            echo '<td>' . esc_html(self::content_mode_label((string) ($profile['content_mode'] ?? 'news'))) . '</td>';
            echo '<td><span class="' . esc_attr($mapped_class) . '">' . esc_html($mapped) . '</span></td>';
            echo '<td>' . esc_html(self::format_search_window(absint($profile['search_window_hours'] ?? 0))) . '</td>';
            echo '<td><strong>' . esc_html(self::format_generation_interval(absint($profile['generation_interval_minutes'] ?? 0))) . '</strong><br><span class="maie-small-muted">' . esc_html(self::format_daily_cap(absint($profile['max_posts_per_day'] ?? 0))) . '</span></td>';
            echo '<td>' . self::profile_toggle_switch($toggle_url, $enabled, (string) ($profile['category_name'] ?? '')) . '</td>';
            echo '<td class="maie-actions-cell">';
            echo '<a class="button button-small" href="' . esc_url($edit_url) . '">עריכה</a> ';
            echo '<a class="button button-primary button-small" href="' . esc_url($generate_draft_url) . '">צור לטיוטה</a> ';
            echo '<a class="button button-small maie-publish-now" href="' . esc_url($generate_publish_url) . '">צור ופרסם</a>';
            echo '</td>';
            echo '</tr>';
        }
        if (!$profiles) {
            echo '<tr><td colspan="8">לא נמצאו פרופילים.</td></tr>';
        }
        echo '</tbody></table></div>';
        echo '</div>';
    }

    private static function render_profile_edit(int $profile_id): void
    {
        $profile = MAIE_DB::get_profile($profile_id);
        if (!$profile) {
            wp_die('הפרופיל לא נמצא.');
        }

        $settings = MAIE_DB::get_settings();
        $categories = get_categories(['hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
        $content_mode = sanitize_key((string) ($profile['content_mode'] ?? 'news'));

        echo '<div class="wrap maie-wrap" dir="rtl">';
        echo '<h1>עריכת פרופיל: ' . esc_html((string) ($profile['category_name'] ?? '')) . '</h1>';
        self::render_notice();
        echo '<p><a class="button" href="' . esc_url(admin_url('admin.php?page=maie-profiles')) . '">חזרה לכל הפרופילים</a></p>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="maie-form">';
        wp_nonce_field('maie_save_profile_' . $profile_id);
        echo '<input type="hidden" name="action" value="maie_save_profile">';
        echo '<input type="hidden" name="profile_id" value="' . esc_attr((string) $profile_id) . '">';

        echo '<section class="maie-panel">';
        echo '<h2>מיפוי והפעלה</h2>';
        self::text_field('category_name', 'שם תצוגה', (string) ($profile['category_name'] ?? ''), 'text', 'שם פנימי של הפרופיל.');
        self::text_field('category_slug', 'Slug פנימי', (string) ($profile['category_slug'] ?? ''), 'text', 'אינו משנה את ה-Slug בוורדפרס, משמש לארגון פנימי.');
        self::text_field('menu_group', 'קבוצת תפריט', (string) ($profile['menu_group'] ?? 'כללי'), 'text');

        echo '<div class="maie-field"><label for="category_id">קטגוריית וורדפרס</label><select id="category_id" name="category_id">';
        echo '<option value="0">ללא מיפוי</option>';
        foreach ($categories as $category) {
            if (!$category instanceof WP_Term) {
                continue;
            }
            echo '<option value="' . esc_attr((string) $category->term_id) . '" ' . selected(absint($profile['category_id'] ?? 0), absint($category->term_id), false) . '>' . esc_html($category->name) . '</option>';
        }
        echo '</select></div>';

        echo '<div class="maie-field"><label for="content_mode">סוג תוכן</label><select id="content_mode" name="content_mode">';
        foreach (['news' => 'חדשות', 'evergreen' => 'מגזין / מדריך', 'press_release' => 'הודעת דוברות מעובדת'] as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected((string) ($profile['content_mode'] ?? 'news'), $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></div>';

        self::checkbox_field('enabled', 'פרופיל פעיל', !empty($profile['enabled']), 'הפרופיל יהיה זמין ליצירה ידנית ולאוטומציה.');
        self::checkbox_field('prefer_israel', 'העדף נושאים עם זיקה לישראל', !empty($profile['prefer_israel']));
        echo '<div class="maie-field"><label for="post_status">סטטוס פוסט חדש</label><select id="post_status" name="post_status">';
        foreach (['draft' => 'טיוטה – מומלץ בשלבי בדיקה', 'pending' => 'ממתין לאישור', 'publish' => 'פרסום מיידי'] as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected((string) ($profile['post_status'] ?? 'draft'), $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></div>';
        echo '</section>';

        echo '<section class="maie-panel">';
        echo '<h2>תדירות וסינון</h2>';
        self::select_field(
            'search_window_hours',
            'חלון זמן לחיפוש',
            absint($profile['search_window_hours'] ?? 12),
            self::search_window_options(),
            'טווח העדכניות של הנושא: המערכת תחפש התפתחות שפורסמה או עודכנה בתוך פרק הזמן שבחרת.'
        );
        self::select_field(
            'generation_interval_minutes',
            'מרווח בין יצירות',
            absint($profile['generation_interval_minutes'] ?? 180),
            self::generation_interval_options(),
            'כל כמה זמן הפרופיל רשאי לנסות ליצור כתבה חדשה באוטומציה. יצירה ידנית אינה מוגבלת לפי שדה זה.'
        );
        self::select_field(
            'max_posts_per_day',
            'תקרת בטיחות יומית',
            absint($profile['max_posts_per_day'] ?? 0),
            self::daily_cap_options(),
            'זהו גבול עליון נוסף בלבד. אם בחרת "ללא תקרה נוספת", מספר הכתבות האפשרי נקבע רק לפי מרווח היצירה. לדוגמה: כל שעה = עד 24 ניסיונות יצירה ביממה.'
        );
        self::textarea_field('preferred_domains', 'דומיינים מועדפים', (string) ($profile['preferred_domains'] ?? ''), 4, 'מופרדים בפסיקים.');
        self::textarea_field('blocked_domains', 'דומיינים חסומים', (string) ($profile['blocked_domains'] ?? ''), 3, 'מופרדים בפסיקים.');
        self::textarea_field('topic_keywords', 'מילות מפתח / תחומי עניין', (string) ($profile['topic_keywords'] ?? ''), 6, 'שורה לכל נושא או רשימה חופשית.');
        echo '</section>';

        echo '<section class="maie-panel">';
        echo '<h2>מודלים, חיפוש ועלויות בפרופיל הזה</h2>';
        echo '<p class="description">השארת האפשרות המומלצת תירש את הגדרת המערכת הכללית. בקטגוריות פחות קריטיות ניתן לבחור מודל זול יותר או לכבות חיפוש רשת.</p>';
        self::choice_cards_field('web_search_mode', 'מצב חיפוש רשת', (string) ($profile['web_search_mode'] ?? 'inherit'), MAIE_Options::web_search_modes($content_mode), 'לחדשות עדכניות מומלץ חיפוש רשת. למדריכים כלליים אפשר לכבות כדי לחסוך.');
        self::choice_cards_field('text_model_override', 'מודל טקסט לפרופיל', (string) ($profile['text_model_override'] ?? ''), MAIE_Options::inherit_options(MAIE_Options::text_models(), (string) ($settings['text_model'] ?? 'gpt-4o')), 'משפיע על איכות המחקר, הכותרת והכתבה בפרופיל זה.');
        self::choice_cards_field('qa_model_override', 'מודל בקרת איכות לפרופיל', (string) ($profile['qa_model_override'] ?? ''), MAIE_Options::inherit_options(MAIE_Options::qa_models(), (string) ($settings['qa_model'] ?? 'gpt-4o')), 'משפיע על רמת הבקרה לפני שמירה או פרסום.');
        self::choice_cards_field('vision_model_override', 'מודל בדיקת תמונה לפרופיל', (string) ($profile['vision_model_override'] ?? ''), MAIE_Options::inherit_options(MAIE_Options::vision_models(), (string) ($settings['vision_model'] ?? 'gpt-4o')), 'משפיע על פסילת תמונות עם טקסט, לוגו או חוסר התאמה.');
        self::choice_cards_field('image_model_override', 'מודל יצירת תמונה לפרופיל', (string) ($profile['image_model_override'] ?? ''), MAIE_Options::inherit_options(MAIE_Options::image_models(), (string) ($settings['image_model'] ?? 'gpt-image-1')), 'ניתן לחסוך בקטגוריות שבהן התמונה פחות מרכזית.');
        self::choice_cards_field('image_size_override', 'גודל תמונה לפרופיל', (string) ($profile['image_size_override'] ?? ''), MAIE_Options::inherit_options(MAIE_Options::image_sizes(), (string) ($settings['image_size'] ?? '1536x1024')), 'ברירת המחדל המומלצת היא 1536×1024.');
        self::choice_cards_field('image_quality_override', 'איכות תמונה לפרופיל', (string) ($profile['image_quality_override'] ?? ''), MAIE_Options::inherit_options(MAIE_Options::image_qualities(), (string) ($settings['image_quality'] ?? 'medium')), 'איכות גבוהה מייקרת יצירת תמונות.');
        echo '</section>';

        echo '<section class="maie-panel">';
        echo '<h2>פרומפטים</h2>';
        self::textarea_field('research_brief', 'הנחיות מחקר ובחירת נושא', (string) ($profile['research_brief'] ?? ''), 11);
        self::textarea_field('title_prompt', 'פרומפט כותרת', (string) ($profile['title_prompt'] ?? ''), 14);
        self::textarea_field('article_prompt', 'פרומפט כתבה', (string) ($profile['article_prompt'] ?? ''), 20);
        self::textarea_field('image_prompt', 'פרומפט תמונה', (string) ($profile['image_prompt'] ?? ''), 18);
        echo '</section>';

        submit_button('שמור פרופיל');
        echo '</form>';
        echo '</div>';
    }

    public static function render_logs(): void
    {
        self::guard();
        $jobs = MAIE_DB::get_jobs(120);
        $focused_job_id = absint($_GET['job_id'] ?? 0);
        $cleanup_url = wp_nonce_url(admin_url('admin-post.php?action=maie_cleanup_logs'), 'maie_cleanup_logs');

        echo '<div class="wrap maie-wrap" dir="rtl">';
        echo '<h1>לוגים ומשימות <a class="page-title-action" href="' . esc_url($cleanup_url) . '" onclick="return confirm(\'למחוק משימות ישנות?\')">נקה לוגים ישנים</a></h1>';
        self::render_notice();
        self::render_cron_health_panel();
        if ($focused_job_id > 0) {
            self::render_job_progress_panel($focused_job_id);
        }
        self::render_jobs_table($jobs, true);
        echo '</div>';
    }


    public static function render_stats(): void
    {
        self::guard();
        $filters = MAIE_DB::get_stats_filters(wp_unslash($_GET));
        $totals = MAIE_DB::get_stats_totals($filters);
        $rows = MAIE_DB::get_article_stats($filters, 300);
        $profiles = MAIE_DB::get_profiles();

        echo '<div class="wrap maie-wrap" dir="rtl">';
        echo '<h1>סטטיסטיקות צפייה – כתבות שנוצרו בתוסף</h1>';
        echo '<p class="maie-lead">המדידה נשמרת בטבלה נפרדת של התוסף ואינה נשענת על מונה צפיות של וורדפרס. סינון התאריכים מתייחס לתאריך פרסום הכתבה.</p>';

        echo '<form method="get" class="maie-filter-form">';
        echo '<input type="hidden" name="page" value="maie-stats">';
        echo '<div class="maie-filter-grid">';
        echo '<div class="maie-field"><label for="start_date">מתאריך</label><input type="date" id="start_date" name="start_date" value="' . esc_attr((string) $filters['start_date']) . '"></div>';
        echo '<div class="maie-field"><label for="end_date">עד תאריך</label><input type="date" id="end_date" name="end_date" value="' . esc_attr((string) $filters['end_date']) . '"></div>';
        echo '<div class="maie-field"><label for="profile_id">קטגוריה / פרופיל</label><select id="profile_id" name="profile_id">';
        echo '<option value="0">כל הפרופילים</option>';
        foreach ($profiles as $profile) {
            $id = absint($profile['id'] ?? 0);
            echo '<option value="' . esc_attr((string) $id) . '" ' . selected(absint($filters['profile_id'] ?? 0), $id, false) . '>' . esc_html((string) ($profile['category_name'] ?? '')) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="maie-field maie-filter-submit"><label>&nbsp;</label><button class="button button-primary">סנן</button></div>';
        echo '</div>';
        echo '</form>';

        echo '<div class="maie-cards">';
        self::stat_card('כתבות במעקב', (string) absint($totals['articles'] ?? 0), 'כתבות AI שפורסמו בטווח הנבחר');
        self::stat_card('צפיות', (string) absint($totals['views'] ?? 0), 'מדידה עצמאית של התוסף');
        self::stat_card('מבקרים ייחודיים', (string) absint($totals['unique_visitors'] ?? 0), 'מבקרים ייחודיים לפי מזהה דפדפן');
        $articles = max(1, absint($totals['articles'] ?? 0));
        $avg = absint($totals['views'] ?? 0) / $articles;
        self::stat_card('ממוצע צפיות לכתבה', number_format_i18n($avg, 1), 'בטווח המסונן');
        echo '</div>';

        echo '<section class="maie-panel">';
        echo '<h2>פירוט כתבות</h2>';
        echo '<div class="maie-table-wrap"><table class="widefat striped maie-table">';
        echo '<thead><tr><th>כתבה</th><th>קטגוריה</th><th>תאריך</th><th>סטטוס</th><th>צפיות</th><th>ייחודיים</th><th>פעולות</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $post_id = absint($row['post_id'] ?? 0);
            $profile = MAIE_DB::get_profile(absint($row['profile_id'] ?? 0));
            $edit_link = get_edit_post_link($post_id) ?: '#';
            $view_link = get_permalink($post_id) ?: '#';
            echo '<tr>';
            echo '<td><strong>' . esc_html((string) ($row['post_title'] ?? '')) . '</strong></td>';
            echo '<td>' . esc_html((string) ($profile['category_name'] ?? 'לא ידוע')) . '</td>';
            echo '<td>' . esc_html((string) ($row['post_date'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($row['post_status'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) absint($row['views'] ?? 0)) . '</td>';
            echo '<td>' . esc_html((string) absint($row['unique_visitors'] ?? 0)) . '</td>';
            echo '<td><a class="button button-small" href="' . esc_url($edit_link) . '">עריכה</a> <a class="button button-small" href="' . esc_url($view_link) . '" target="_blank" rel="noopener">צפייה</a></td>';
            echo '</tr>';
        }
        if (!$rows) {
            echo '<tr><td colspan="7">אין נתונים בטווח שנבחר.</td></tr>';
        }
        echo '</tbody></table></div>';
        echo '</section>';
        echo '</div>';
    }

    public static function handle_save_settings(): void
    {
        self::guard();
        check_admin_referer('maie_save_settings');

        $settings = wp_unslash($_POST);
        MAIE_DB::update_settings(is_array($settings) ? $settings : []);
        if (class_exists('MAIE_Install')) {
            MAIE_Install::ensure_cron_scheduled();
        }
        wp_safe_redirect(add_query_arg([
            'page' => 'maie-settings',
            'maie_notice' => 'settings_saved',
        ], admin_url('admin.php')));
        exit;
    }

    public static function handle_save_profile(): void
    {
        self::guard();
        $profile_id = absint($_POST['profile_id'] ?? 0);
        if ($profile_id <= 0) {
            wp_die('מזהה פרופיל חסר.');
        }
        check_admin_referer('maie_save_profile_' . $profile_id);

        $data = wp_unslash($_POST);
        MAIE_DB::update_profile($profile_id, is_array($data) ? $data : []);
        wp_safe_redirect(add_query_arg([
            'page' => 'maie-profiles',
            'maie_action' => 'edit',
            'profile_id' => $profile_id,
            'maie_notice' => 'profile_saved',
        ], admin_url('admin.php')));
        exit;
    }

    public static function handle_toggle_profile(): void
    {
        self::guard();
        $profile_id = absint($_GET['profile_id'] ?? 0);
        check_admin_referer('maie_toggle_profile_' . $profile_id);
        MAIE_DB::toggle_profile($profile_id);
        wp_safe_redirect(add_query_arg([
            'page' => 'maie-profiles',
            'maie_notice' => 'profile_toggled',
        ], admin_url('admin.php')));
        exit;
    }

    public static function handle_generate_now(): void
    {
        self::guard();
        $profile_id = absint($_GET['profile_id'] ?? 0);
        $requested_post_status = sanitize_key((string) ($_GET['post_status'] ?? 'draft'));
        if (!in_array($requested_post_status, ['draft', 'publish'], true)) {
            $requested_post_status = 'draft';
        }

        check_admin_referer('maie_generate_now_' . $profile_id . '_' . $requested_post_status);

        $profile = MAIE_DB::get_profile($profile_id);
        if (!$profile) {
            wp_die('הפרופיל לא נמצא.');
        }

        $job_message = $requested_post_status === 'publish'
            ? 'נוצרה משימה ידנית ליצירת כתבה ופרסום מיידי לאחר כל בדיקות האיכות.'
            : 'נוצרה משימה ידנית ליצירת כתבה ושמירתה כטיוטה.';

        $job_id = MAIE_DB::create_job($profile_id, 'queued', 'manual_queue', $job_message);
        if ($job_id > 0) {
            MAIE_DB::update_job($job_id, [
                'payload' => [
                    'manual_requested_post_status' => $requested_post_status,
                    'manual_requested_by' => get_current_user_id(),
                    'manual_requested_at' => current_time('mysql'),
                ],
            ]);

            // שלח ריידיירקט מיידי לדפדפן לפני כל קריאת רשת או הרצת משימה.
            // send_response_and_continue() סוגר את החיבור לדפדפן (fastcgi_finish_request
            // ב-PHP-FPM, או flush ב-mod_php); ה-PHP ממשיך לרוץ ברקע לאחר מכן.
            wp_safe_redirect(add_query_arg([
                'page' => 'maie-logs',
                'job_id' => $job_id,
                'maie_notice' => 'job_queued',
            ], admin_url('admin.php')));
            self::send_response_and_continue();

            if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
                MAIE_Generator::run_job($job_id);
            } else {
                wp_schedule_single_event(time(), 'maie_run_job_event', [$job_id]);
                if (function_exists('spawn_cron')) {
                    spawn_cron(time());
                }
            }
            exit;
        }

        wp_safe_redirect(add_query_arg([
            'page' => 'maie-logs',
            'job_id' => 0,
            'maie_notice' => 'job_queue_failed',
        ], admin_url('admin.php')));
        exit;
    }

    private static function send_response_and_continue(): void
    {
        ignore_user_abort(true);
        if (function_exists('fastcgi_finish_request')) {
            // PHP-FPM: שולח את כל ה-headers (כולל Location) לדפדפן וסוגר את החיבור.
            // ה-PHP ממשיך לרוץ בשקט ברקע.
            fastcgi_finish_request();
            return;
        }
        // mod_php / CGI fallback: ניסיון לסגור את החיבור לפני המשך עיבוד
        header('Content-Encoding: none');
        header('Content-Length: 0');
        header('Connection: close');
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }


    public static function handle_cleanup_logs(): void
    {
        self::guard();
        check_admin_referer('maie_cleanup_logs');

        $settings = MAIE_DB::get_settings();
        $retention_days = max(7, absint($settings['jobs_retention_days'] ?? 30));
        $deleted = MAIE_DB::delete_old_jobs($retention_days);
        delete_transient('maie_last_cleanup');

        wp_safe_redirect(add_query_arg([
            'page' => 'maie-logs',
            'maie_notice' => 'logs_cleaned',
            'deleted' => $deleted,
        ], admin_url('admin.php')));
        exit;
    }

    public static function handle_run_cron_now(): void
    {
        self::guard();
        check_admin_referer('maie_run_cron_now');

        if (class_exists('MAIE_Install')) {
            MAIE_Install::ensure_cron_scheduled();
        }

        // לא מריצים את כל מנוע היצירה בתוך טעינת עמוד הניהול.
        // יצירת תוכן יכולה לכלול חיפוש רשת וקריאות API ארוכות, ולכן מכניסים את ההרצה לתור רקע.
        if (!wp_next_scheduled('maie_manual_cron_run_event')) {
            wp_schedule_single_event(time() + 2, 'maie_manual_cron_run_event');
        }

        if (function_exists('spawn_cron')) {
            spawn_cron(time());
        }

        wp_safe_redirect(add_query_arg([
            'page' => 'maie-logs',
            'maie_notice' => 'cron_run_now',
        ], admin_url('admin.php')));
        exit;
    }

    public static function ajax_job_status(): void
    {
        self::guard();
        check_ajax_referer('maie_job_status', 'nonce');

        $job_id = absint($_GET['job_id'] ?? 0);
        $job = $job_id > 0 ? MAIE_DB::get_job($job_id) : null;
        if (!$job) {
            wp_send_json_error(['message' => 'המשימה לא נמצאה.'], 404);
        }

        $events = MAIE_DB::get_job_events($job_id, 80);
        $post_id = absint($job['post_id'] ?? 0);

        wp_send_json_success([
            'job_id' => $job_id,
            'status' => sanitize_key((string) ($job['status'] ?? '')),
            'status_label' => self::status_label(sanitize_key((string) ($job['status'] ?? ''))),
            'step' => sanitize_key((string) ($job['step'] ?? '')),
            'step_label' => self::step_label(sanitize_key((string) ($job['step'] ?? ''))),
            'message' => (string) ($job['message'] ?? ''),
            'progress_percent' => absint($job['progress_percent'] ?? 0),
            'topic_title' => (string) ($job['topic_title'] ?? ''),
            'post_id' => $post_id,
            'post_edit_url' => $post_id > 0 ? (get_edit_post_link($post_id) ?: '') : '',
            'events' => array_map(static function (array $event): array {
                return [
                    'status' => sanitize_key((string) ($event['status'] ?? '')),
                    'step' => sanitize_key((string) ($event['step'] ?? '')),
                    'step_label' => self::step_label(sanitize_key((string) ($event['step'] ?? ''))),
                    'message' => (string) ($event['message'] ?? ''),
                    'progress_percent' => absint($event['progress_percent'] ?? 0),
                    'created_at' => (string) ($event['created_at'] ?? ''),
                ];
            }, $events),
        ]);
    }

    public static function ajax_kick_job(): void
    {
        self::guard();
        check_ajax_referer('maie_job_status', 'nonce');

        $job_id = absint($_POST['job_id'] ?? 0);
        $job = $job_id > 0 ? MAIE_DB::get_job($job_id) : null;

        if (!$job || $job['status'] !== 'queued') {
            wp_send_json_success(['kicked' => false]);
            return;
        }

        if (!wp_next_scheduled('maie_run_job_event', [$job_id])) {
            wp_schedule_single_event(time(), 'maie_run_job_event', [$job_id]);
        }

        // Non-blocking ping to wp-cron.php to fire the scheduled event
        wp_remote_post(site_url('wp-cron.php'), [
            'blocking' => false,
            'timeout' => 0.01,
            'sslverify' => apply_filters('https_local_ssl_verify', false),
            'body' => ['doing_wp_cron' => sprintf('%.22F', microtime(true))],
        ]);

        wp_send_json_success(['kicked' => true]);
    }

    private static function render_job_progress_panel(int $job_id): void
    {
        $job = MAIE_DB::get_job($job_id);
        if (!$job) {
            echo '<section class="maie-panel maie-job-progress-panel"><h2>מעקב יצירה</h2><p>המשימה המבוקשת לא נמצאה.</p></section>';
            return;
        }

        $events = MAIE_DB::get_job_events($job_id, 80);
        $status = sanitize_key((string) ($job['status'] ?? 'queued'));
        $progress = absint($job['progress_percent'] ?? 0);
        $step = sanitize_key((string) ($job['step'] ?? 'queued'));
        $message = (string) ($job['message'] ?? '');

        echo '<section class="maie-panel maie-job-progress-panel" id="maie-job-progress" data-job-id="' . esc_attr((string) $job_id) . '">';
        echo '<div class="maie-progress-head">';
        echo '<div>';
        echo '<h2>יצירת כתבה בזמן אמת</h2>';
        echo '<p class="maie-progress-subtitle">משימה #' . esc_html((string) $job_id) . ' · <span id="maie-progress-status">' . esc_html(self::status_label($status)) . '</span> · <span id="maie-progress-step">' . esc_html(self::step_label($step)) . '</span></p>';
        echo '</div>';
        echo '<div class="maie-progress-percent" id="maie-progress-percent">' . esc_html((string) $progress) . '%</div>';
        echo '</div>';
        echo '<div class="maie-progress-track" role="progressbar" aria-valuenow="' . esc_attr((string) $progress) . '" aria-valuemin="0" aria-valuemax="100"><span id="maie-progress-bar" style="width:' . esc_attr((string) $progress) . '%"></span></div>';
        echo '<p class="maie-progress-message" id="maie-progress-message">' . esc_html($message !== '' ? $message : 'המשימה ממתינה לתחילת עבודה.') . '</p>';
        echo '<div class="maie-job-timeline-wrap"><h3>שלבי המשימה</h3><ol class="maie-job-timeline" id="maie-job-timeline">';
        self::render_timeline_items($events);
        echo '</ol></div>';
        echo '</section>';
    }

    private static function render_timeline_items(array $events): void
    {
        if (!$events) {
            echo '<li class="maie-timeline-item is-waiting"><span class="maie-timeline-title">בתור</span><small>המשימה נוספה לתור וממתינה להפעלה.</small></li>';
            return;
        }

        foreach ($events as $event) {
            $step = sanitize_key((string) ($event['step'] ?? 'queued'));
            $progress = absint($event['progress_percent'] ?? 0);
            $message = (string) ($event['message'] ?? '');
            $created_at = (string) ($event['created_at'] ?? '');
            echo '<li class="maie-timeline-item">';
            echo '<span class="maie-timeline-bullet"></span>';
            echo '<span class="maie-timeline-title">' . esc_html(self::step_label($step)) . '</span>';
            echo '<small>' . esc_html(($message !== '' ? $message : 'השלב עודכן.') . ' · ' . $progress . '%' . ($created_at !== '' ? ' · ' . $created_at : '')) . '</small>';
            echo '</li>';
        }
    }

    private static function render_jobs_table(array $jobs, bool $show_payload_link): void
    {
        echo '<div class="maie-table-wrap"><table class="widefat striped maie-table">';
        echo '<thead><tr><th>ID</th><th>זמן</th><th>פרופיל</th><th>סטטוס</th><th>שלב</th><th>נושא</th><th>פוסט</th><th>הודעה</th></tr></thead><tbody>';
        foreach ($jobs as $job) {
            $profile = MAIE_DB::get_profile(absint($job['profile_id'] ?? 0));
            $profile_name = $profile ? (string) ($profile['category_name'] ?? '') : 'לא נמצא';
            $post_id = absint($job['post_id'] ?? 0);
            $post_link = $post_id > 0 ? '<a href="' . esc_url(get_edit_post_link($post_id) ?: '#') . '">עריכת פוסט #' . esc_html((string) $post_id) . '</a>' : '—';
            $status = sanitize_key((string) ($job['status'] ?? ''));
            $status_class = 'maie-status-' . $status;
            echo '<tr>';
            echo '<td>' . esc_html((string) absint($job['id'] ?? 0)) . '</td>';
            echo '<td>' . esc_html((string) ($job['created_at'] ?? '')) . '</td>';
            echo '<td>' . esc_html($profile_name) . '</td>';
            echo '<td><span class="maie-status ' . esc_attr($status_class) . '">' . esc_html(self::status_label($status)) . '</span></td>';
            echo '<td>' . esc_html((string) ($job['step'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($job['topic_title'] ?? '')) . '</td>';
            echo '<td>' . $post_link . '</td>';
            echo '<td>' . esc_html((string) ($job['message'] ?? '')) . '</td>';
            echo '</tr>';
        }
        if (!$jobs) {
            echo '<tr><td colspan="8">עדיין אין משימות.</td></tr>';
        }
        echo '</tbody></table></div>';
        if ($show_payload_link) {
            echo '<p class="description">גרסה 1.0 שומרת את מטען המשימה בבסיס הנתונים לצורכי בדיקה ותחקור פנימי.</p>';
        }
    }

    private static function render_notice(): void
    {
        $notice = sanitize_key((string) ($_GET['maie_notice'] ?? ''));
        $deleted = absint($_GET['deleted'] ?? 0);
        $messages = [
            'settings_saved' => ['success', 'ההגדרות נשמרו בהצלחה.'],
            'profile_saved' => ['success', 'הפרופיל נשמר בהצלחה.'],
            'profile_toggled' => ['success', 'סטטוס הפרופיל עודכן.'],
            'job_queued' => ['success', 'המשימה נוספה לתור ותופעל ברקע. ראה את מצב ההתקדמות בלוגים.'],
            'job_queue_failed' => ['error', 'לא ניתן היה ליצור משימה חדשה.'],
            'cron_run_now' => ['success', 'הרצת Cron יזומה הוכנסה לתור רקע. סיכום הסטטוס והמשימות האחרונות יתעדכנו לאחר העיבוד.'],
            'logs_cleaned' => ['success', 'הלוגים נוקו בהצלחה. נמחקו ' . $deleted . ' משימות ישנות.'],
        ];

        if (!isset($messages[$notice])) {
            return;
        }

        [$class, $message] = $messages[$notice];
        if ($notice === 'job_queue_failed') {
            $last_error = sanitize_text_field((string) get_transient('maie_last_queue_error'));
            if ($last_error !== '') {
                $message .= ' פרט טכני: ' . $last_error;
            } else {
                $message .= ' בגרסה זו בוצעה בדיקת תיקון סכימה אוטומטית. לאחר העדכון נסה שוב.';
            }
        }
        echo '<div class="notice notice-' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }

    private static function stat_card(string $title, string $value, string $description): void
    {
        echo '<div class="maie-card">';
        echo '<div class="maie-card-title">' . esc_html($title) . '</div>';
        echo '<div class="maie-card-value">' . esc_html($value) . '</div>';
        echo '<div class="maie-card-desc">' . esc_html($description) . '</div>';
        echo '</div>';
    }

    private static function choice_cards_field(string $name, string $label, string $value, array $options, string $description = ''): void
    {
        echo '<div class="maie-field maie-choice-field">';
        echo '<span class="maie-field-label">' . esc_html($label) . '</span>';
        if ($description !== '') {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
        echo '<div class="maie-choice-grid">';
        foreach ($options as $option_value => $option) {
            $option_value = (string) $option_value;
            $selected = $value === $option_value;
            $recommended = !empty($option['recommended']);
            $classes = 'maie-choice-card';
            if ($recommended) {
                $classes .= ' is-recommended';
            }
            if ($selected) {
                $classes .= ' is-selected';
            }
            echo '<label class="' . esc_attr($classes) . '">';
            echo '<input type="radio" name="' . esc_attr($name) . '" value="' . esc_attr($option_value) . '" ' . checked($selected, true, false) . '>';
            echo '<span class="maie-choice-title">' . esc_html((string) ($option['label'] ?? $option_value)) . '</span>';
            if (!empty($option['badge'])) {
                echo '<span class="maie-choice-badge">' . esc_html((string) $option['badge']) . '</span>';
            }
            if (!empty($option['description'])) {
                echo '<span class="maie-choice-description">' . esc_html((string) $option['description']) . '</span>';
            }
            if (!empty($option['cost'])) {
                echo '<span class="maie-choice-cost">' . esc_html((string) $option['cost']) . '</span>';
            }
            echo '</label>';
        }
        echo '</div>';
        echo '</div>';
    }

    private static function text_field(string $name, string $label, string $value, string $type = 'text', string $description = ''): void
    {
        echo '<div class="maie-field">';
        echo '<label for="' . esc_attr($name) . '">' . esc_html($label) . '</label>';
        echo '<input class="regular-text" type="' . esc_attr($type) . '" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
        if ($description !== '') {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
        echo '</div>';
    }

    private static function textarea_field(string $name, string $label, string $value, int $rows = 6, string $description = ''): void
    {
        echo '<div class="maie-field">';
        echo '<label for="' . esc_attr($name) . '">' . esc_html($label) . '</label>';
        echo '<textarea class="large-text code" rows="' . esc_attr((string) $rows) . '" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea>';
        if ($description !== '') {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
        echo '</div>';
    }

    private static function checkbox_field(string $name, string $label, bool $checked, string $description = ''): void
    {
        echo '<div class="maie-field maie-checkbox">';
        echo '<label><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked($checked, true, false) . '> ' . esc_html($label) . '</label>';
        if ($description !== '') {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
        echo '</div>';
    }

    private static function number_field(string $name, string $label, int $value, int $min, int $max, string $unit = ''): void
    {
        echo '<div class="maie-field">';
        echo '<label for="' . esc_attr($name) . '">' . esc_html($label) . '</label>';
        echo '<div class="maie-inline-number"><input type="number" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" min="' . esc_attr((string) $min) . '" max="' . esc_attr((string) $max) . '">';
        if ($unit !== '') {
            echo '<span>' . esc_html($unit) . '</span>';
        }
        echo '</div></div>';
    }

    private static function format_search_window(int $hours): string
    {
        $options = self::search_window_options();
        return isset($options[$hours]) ? (string) $options[$hours] : $hours . ' שעות';
    }

    private static function format_generation_interval(int $minutes): string
    {
        $options = self::generation_interval_options();
        return isset($options[$minutes]) ? (string) $options[$minutes] : $minutes . ' דקות';
    }


    private static function format_daily_cap(int $max_posts): string
    {
        if ($max_posts <= 0) {
            return 'ללא תקרה נוספת';
        }

        return 'עד ' . $max_posts . ' כתבות ביום';
    }

    private static function select_field(string $name, string $label, int $value, array $options, string $description = ''): void
    {
        echo '<div class="maie-field">';
        echo '<label for="' . esc_attr($name) . '">' . esc_html($label) . '</label>';
        echo '<select id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">';
        foreach ($options as $option_value => $option_label) {
            echo '<option value="' . esc_attr((string) $option_value) . '" ' . selected($value, (int) $option_value, false) . '>' . esc_html((string) $option_label) . '</option>';
        }
        echo '</select>';
        if ($description !== '') {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
        echo '</div>';
    }

    private static function search_window_options(): array
    {
        return [
            1 => 'שעה אחת',
            3 => '3 שעות',
            6 => '6 שעות',
            12 => '12 שעות',
            24 => '24 שעות',
            48 => '48 שעות',
            72 => '72 שעות',
            168 => '7 ימים',
            336 => '14 ימים',
            720 => '30 ימים',
        ];
    }

    private static function cron_jobs_per_run_options(): array
    {
        return [
            1 => '1 פרופיל בכל סבב – חסכוני מאוד',
            2 => '2 פרופילים בכל סבב',
            3 => '3 פרופילים בכל סבב',
            5 => '5 פרופילים בכל סבב – מומלץ',
            8 => '8 פרופילים בכל סבב',
            10 => '10 פרופילים בכל סבב',
            15 => '15 פרופילים בכל סבב',
            20 => '20 פרופילים בכל סבב – עומס גבוה',
        ];
    }

    private static function generation_interval_options(): array
    {
        return [
            5 => 'כל 5 דקות',
            10 => 'כל 10 דקות',
            15 => 'כל 15 דקות',
            30 => 'כל 30 דקות',
            60 => 'כל שעה',
            120 => 'כל שעתיים',
            180 => 'כל 3 שעות',
            240 => 'כל 4 שעות',
            360 => 'כל 6 שעות',
            720 => 'כל 12 שעות',
            1440 => 'כל 24 שעות',
        ];
    }


    private static function daily_cap_options(): array
    {
        return [
            0 => 'ללא תקרה נוספת – לפי מרווח היצירה בלבד',
            1 => 'עד כתבה אחת ביום',
            2 => 'עד 2 כתבות ביום',
            3 => 'עד 3 כתבות ביום',
            4 => 'עד 4 כתבות ביום',
            6 => 'עד 6 כתבות ביום',
            8 => 'עד 8 כתבות ביום',
            12 => 'עד 12 כתבות ביום',
            24 => 'עד 24 כתבות ביום',
            48 => 'עד 48 כתבות ביום',
        ];
    }

    private static function profile_toggle_switch(string $toggle_url, bool $enabled, string $label): string
    {
        $state_class = $enabled ? ' is-on' : ' is-off';
        $state_label = $enabled ? 'פעיל' : 'כבוי';
        $aria = $enabled ? 'כבה את הפרופיל ' . $label : 'הפעל את הפרופיל ' . $label;

        return '<div class="maie-profile-switch-wrap"><a class="maie-profile-switch' . esc_attr($state_class) . '" href="' . esc_url($toggle_url) . '" aria-label="' . esc_attr($aria) . '"><span class="maie-profile-switch-knob"></span></a><span class="maie-profile-switch-label">' . esc_html($state_label) . '</span></div>';
    }


    private static function get_profile_filters(array $raw): array
    {
        $filters = [];
        $search = sanitize_text_field((string) ($raw['profile_search'] ?? ''));
        $interval = absint($raw['profile_interval'] ?? 0);
        $enabled = sanitize_text_field((string) ($raw['profile_enabled'] ?? ''));
        $post_status = sanitize_key((string) ($raw['profile_post_status'] ?? ''));
        $orderby = sanitize_key((string) ($raw['orderby'] ?? 'default'));
        $order = strtoupper(sanitize_text_field((string) ($raw['order'] ?? 'ASC')));
        $order = $order === 'DESC' ? 'DESC' : 'ASC';

        if ($search !== '') {
            $filters['search'] = $search;
        }
        if ($interval > 0) {
            $filters['generation_interval_minutes'] = $interval;
        }
        if ($enabled === '1' || $enabled === '0') {
            $filters['enabled'] = (int) $enabled;
        }
        if (in_array($post_status, ['draft', 'pending', 'publish'], true)) {
            $filters['post_status'] = $post_status;
        }
        if ($orderby === 'frequency') {
            $filters['orderby'] = 'frequency';
            $filters['order'] = $order;
        }

        $filters['_raw'] = [
            'profile_search' => $search,
            'profile_interval' => $interval,
            'profile_enabled' => $enabled,
            'profile_post_status' => $post_status,
            'orderby' => $orderby,
            'order' => $order,
        ];

        return $filters;
    }

    private static function render_profile_filters(array $filters): void
    {
        $raw = is_array($filters['_raw'] ?? null) ? $filters['_raw'] : [];
        $search = (string) ($raw['profile_search'] ?? '');
        $interval = absint($raw['profile_interval'] ?? 0);
        $enabled = (string) ($raw['profile_enabled'] ?? '');
        $post_status = (string) ($raw['profile_post_status'] ?? '');

        echo '<form method="get" class="maie-filter-form maie-profile-filter-form">';
        echo '<input type="hidden" name="page" value="maie-profiles">';
        if (($raw['orderby'] ?? '') === 'frequency') {
            echo '<input type="hidden" name="orderby" value="frequency">';
            echo '<input type="hidden" name="order" value="' . esc_attr((string) ($raw['order'] ?? 'ASC')) . '">';
        }
        echo '<div class="maie-filter-grid">';
        echo '<div class="maie-field"><label for="profile_search">חיפוש פרופיל</label><input type="search" id="profile_search" name="profile_search" value="' . esc_attr($search) . '" placeholder="שם קטגוריה או קבוצה"></div>';
        echo '<div class="maie-field"><label for="profile_interval">סינון לפי תדירות</label><select id="profile_interval" name="profile_interval"><option value="0">כל התדירויות</option>';
        foreach (self::generation_interval_options() as $value => $label) {
            echo '<option value="' . esc_attr((string) $value) . '" ' . selected($interval, absint($value), false) . '>' . esc_html((string) $label) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="maie-field"><label for="profile_enabled">מצב פרופיל</label><select id="profile_enabled" name="profile_enabled">';
        echo '<option value="">פעילים וכבויים</option>';
        echo '<option value="1" ' . selected($enabled, '1', false) . '>פעילים בלבד</option>';
        echo '<option value="0" ' . selected($enabled, '0', false) . '>כבויים בלבד</option>';
        echo '</select></div>';
        echo '<div class="maie-field"><label for="profile_post_status">סטטוס פוסט חדש</label><select id="profile_post_status" name="profile_post_status">';
        echo '<option value="">כל הסטטוסים</option>';
        foreach (['draft' => 'טיוטה', 'pending' => 'ממתין לאישור', 'publish' => 'פרסום מיידי'] as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($post_status, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></div>';
        echo '<div class="maie-field maie-filter-submit"><label>&nbsp;</label><button class="button button-primary">סנן פרופילים</button></div>';
        echo '</div>';
        echo '<p class="maie-filter-reset"><a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=maie-profiles')) . '">איפוס סינון</a></p>';
        echo '</form>';
    }

    private static function profile_frequency_sort_url(array $filters): string
    {
        $raw = is_array($filters['_raw'] ?? null) ? $filters['_raw'] : [];
        $current_orderby = sanitize_key((string) ($raw['orderby'] ?? 'default'));
        $current_order = strtoupper(sanitize_text_field((string) ($raw['order'] ?? 'ASC')));
        $current_order = $current_order === 'DESC' ? 'DESC' : 'ASC';
        $next_order = ($current_orderby === 'frequency' && $current_order === 'ASC') ? 'DESC' : 'ASC';

        $args = [
            'page' => 'maie-profiles',
            'orderby' => 'frequency',
            'order' => $next_order,
        ];
        foreach (['profile_search', 'profile_interval', 'profile_enabled', 'profile_post_status'] as $key) {
            if (isset($raw[$key]) && (string) $raw[$key] !== '') {
                $args[$key] = (string) $raw[$key];
            }
        }

        return add_query_arg($args, admin_url('admin.php'));
    }

    private static function profile_frequency_sort_label(array $filters): string
    {
        $raw = is_array($filters['_raw'] ?? null) ? $filters['_raw'] : [];
        if (($raw['orderby'] ?? '') !== 'frequency') {
            return '↕';
        }

        return strtoupper((string) ($raw['order'] ?? 'ASC')) === 'DESC' ? '▼' : '▲';
    }

    private static function render_cron_health_panel(): void
    {
        $settings = MAIE_DB::get_settings();
        $health = MAIE_Cron::get_cron_health();
        $next_cron = wp_next_scheduled('maie_cron_event');
        $run_now_url = wp_nonce_url(admin_url('admin-post.php?action=maie_run_cron_now'), 'maie_run_cron_now');

        echo '<section class="maie-panel maie-cron-health">';
        echo '<div class="maie-cron-health-head"><div><h2>מצב Cron ואוטומציה</h2><p class="description">הפאנל מציג האם מנגנון האוטומציה הופעל בפועל, מתי הייתה הריצה האחרונה ומה קרה בה.</p></div>';
        echo '<a class="button button-primary" href="' . esc_url($run_now_url) . '">הרץ Cron עכשיו</a></div>';
        echo '<div class="maie-cron-health-grid">';
        echo '<div><strong>אוטומציה:</strong> ' . (!empty($settings['auto_cron_enabled']) ? '<span class="maie-ok">פעילה</span>' : '<span class="maie-bad">כבויה</span>') . '</div>';
        echo '<div><strong>הרצה הבאה:</strong> ' . ($next_cron ? esc_html(wp_date('d/m/Y H:i:s', $next_cron)) : '<span class="maie-bad">לא מתוזמן</span>') . '</div>';
        echo '<div><strong>הרצה אחרונה:</strong> ' . esc_html((string) ($health['last_run_at'] ?? 'עדיין לא נרשמה')) . '</div>';
        echo '<div><strong>פרופילים פעילים:</strong> ' . esc_html((string) absint($health['enabled_profiles'] ?? MAIE_DB::count_enabled_profiles())) . '</div>';
        echo '<div><strong>פרופילים שהיו מוכנים בריצה האחרונה:</strong> ' . esc_html((string) absint($health['due_profiles'] ?? 0)) . '</div>';
        echo '<div><strong>משימות שהופעלו:</strong> ' . esc_html((string) absint($health['jobs_attempted'] ?? 0)) . '</div>';
        echo '</div>';
        echo '<p class="maie-cron-summary"><strong>סיכום:</strong> ' . esc_html((string) ($health['last_summary'] ?? 'עדיין לא נרשמה הרצת Cron.')) . '</p>';

        $due_profiles = absint($health['due_profiles'] ?? 0);
        $configured_batch = max(1, absint($settings['max_jobs_per_cron'] ?? 1));
        if ($due_profiles > $configured_batch) {
            echo '<p class="maie-cron-backlog"><strong>שימו לב:</strong> בהרצה האחרונה היו ' . esc_html((string) $due_profiles) . ' פרופילים שהגיעו למועד יצירה, אך ההגדרה הנוכחית מעבדת עד ' . esc_html((string) $configured_batch) . ' בלבד בכל סבב Cron. במצב כזה חלק מהפרופילים ימתינו לסבבים הבאים.</p>';
        }

        echo '</section>';
    }

    private static function step_label(string $step): string
    {
        return match ($step) {
            'queued', 'manual_queue' => 'בתור',
            'research' => 'איתור נושא ומקורות',
            'topic_selected' => 'נבחר נושא',
            'title' => 'יצירת כותרת',
            'article' => 'כתיבת כתבה',
            'editorial_review' => 'בקרת איכות',
            'image_metadata' => 'בניית הנחיית תמונה',
            'image_generation' => 'יצירת תמונה ראשית',
            'image_review' => 'בדיקת תמונה',
            'post_insert' => 'שמירה בוורדפרס',
            'completed' => 'הושלם',
            'profile_missing' => 'פרופיל לא נמצא',
            'missing_api_key' => 'מפתח API חסר',
            'category_unmapped' => 'קטגוריה לא ממופה',
            'research_error' => 'שגיאת מחקר',
            'research_skip' => 'לא נמצא נושא מתאים',
            'topic_outside_search_window' => 'נושא מחוץ לחלון החיפוש',
            'topic_low_freshness_confidence' => 'טריות לא מספקת',
            'duplicate_topic', 'duplicate_title' => 'כפילות',
            'title_error', 'title_missing' => 'שגיאת כותרת',
            'article_error', 'article_missing' => 'שגיאת כתבה',
            'review_error', 'review_rejected' => 'נפסל בבקרת איכות',
            'image_metadata_error' => 'שגיאת נתוני תמונה',
            'image_failed' => 'יצירת תמונה נכשלה',
            'attachment_error' => 'שמירת קובץ התמונה נכשלה',
            'post_insert_error' => 'שמירת הפוסט נכשלה',
            default => $step !== '' ? $step : 'שלב לא ידוע',
        };
    }


    private static function content_mode_label(string $mode): string
    {
        return match ($mode) {
            'evergreen' => 'מגזין / מדריך',
            'press_release' => 'דוברות מעובדת',
            default => 'חדשות',
        };
    }

    private static function status_label(string $status): string
    {
        return match ($status) {
            'completed' => 'הושלם',
            'failed' => 'נכשל',
            'skipped' => 'דולג',
            'running' => 'רץ',
            'queued' => 'בתור',
            default => $status !== '' ? $status : 'לא ידוע',
        };
    }

    private static function guard(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('אין לך הרשאה לגשת לעמוד זה.');
        }
    }
}
