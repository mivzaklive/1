<?php

if (!defined('ABSPATH')) {
    exit;
}

final class MAIE_Generator
{
    public static function run_job(int $job_id): void
    {
        $job = MAIE_DB::get_job($job_id);
        if (!$job) {
            return;
        }

        $initial_payload = [];
        $raw_job_payload = (string) ($job['payload'] ?? '');
        if ($raw_job_payload !== '') {
            $decoded_payload = json_decode($raw_job_payload, true);
            if (is_array($decoded_payload)) {
                $initial_payload = $decoded_payload;
            }
        }

        $requested_post_status = sanitize_key((string) ($initial_payload['manual_requested_post_status'] ?? ''));
        if (!in_array($requested_post_status, ['draft', 'publish'], true)) {
            $requested_post_status = '';
        }

        $profile = MAIE_DB::get_profile(absint($job['profile_id'] ?? 0));
        if (!$profile) {
            MAIE_DB::update_job($job_id, [
                'status' => 'failed',
                'step' => 'profile_missing',
                'message' => 'פרופיל הקטגוריה לא נמצא.',
                'finished_at' => current_time('mysql'),
            ]);
            return;
        }

        $settings = MAIE_DB::get_settings();
        $client = new MAIE_OpenAI($settings);
        if (!$client->has_api_key()) {
            self::fail_job($job_id, 'missing_api_key', 'לא הוגדר מפתח OpenAI API.', []);
            return;
        }

        if (absint($profile['category_id'] ?? 0) <= 0) {
            self::skip_job($job_id, 'category_unmapped', 'הפרופיל אינו ממופה לקטגוריית וורדפרס פעילה.', []);
            return;
        }

        MAIE_DB::update_job($job_id, [
            'status' => 'running',
            'step' => 'research',
            'message' => 'התחיל שלב איתור הנושא.',
            'started_at' => current_time('mysql'),
        ]);

        $payload = $initial_payload;
        $payload['profile_id'] = absint($profile['id'] ?? 0);
        $payload['category_name'] = (string) ($profile['category_name'] ?? '');
        $payload['started_at'] = current_time('mysql');
        if ($requested_post_status !== '') {
            $payload['effective_requested_post_status'] = $requested_post_status;
        }

        $research = self::research_topic($client, $profile);
        $payload['research'] = $research;
        if (empty($research['ok'])) {
            self::fail_job($job_id, 'research_error', (string) ($research['error'] ?? 'שלב המחקר נכשל.'), $payload);
            return;
        }

        $topic = $research['data'] ?? [];
        if (($topic['status'] ?? '') !== 'ok') {
            self::skip_job($job_id, 'research_skip', (string) ($topic['skip_reason'] ?? 'לא נמצא נושא מתאים.'), $payload);
            return;
        }

        $content_mode = sanitize_key((string) ($profile['content_mode'] ?? 'news'));
        if ($content_mode === 'news') {
            $selected_window_hours = max(1, absint($profile['search_window_hours'] ?? 12));
            $estimated_age_hours = isset($topic['estimated_event_age_hours']) ? (float) $topic['estimated_event_age_hours'] : 999999.0;
            $freshness_confidence = sanitize_key((string) ($topic['freshness_confidence'] ?? 'low'));

            if ($estimated_age_hours > $selected_window_hours) {
                self::skip_job(
                    $job_id,
                    'topic_outside_search_window',
                    'הנושא נפסל: גיל ההתפתחות המשוער חורג מחלון הזמן שהוגדר לפרופיל.',
                    $payload
                );
                return;
            }

            if ($freshness_confidence === 'low') {
                self::skip_job(
                    $job_id,
                    'topic_low_freshness_confidence',
                    'הנושא נפסל: רמת הביטחון בטריות האירוע נמוכה מדי לכתבת חדשות אוטומטית.',
                    $payload
                );
                return;
            }
        }

        $topic_title = sanitize_text_field((string) ($topic['topic_title'] ?? ''));
        if ($topic_title === '') {
            self::fail_job($job_id, 'topic_title_missing', 'נבחר נושא ללא כותרת נושא.', $payload);
            return;
        }

        MAIE_DB::update_job($job_id, [
            'step' => 'topic_selected',
            'topic_title' => $topic_title,
            'message' => 'נבחר נושא: ' . $topic_title,
            'payload' => $payload,
        ]);

        if (self::is_duplicate_topic($topic_title, $profile)) {
            self::skip_job($job_id, 'duplicate_topic', 'הנושא דומה מדי לכתבה או למשימה קודמת.', $payload);
            return;
        }

        $facts_package = self::build_facts_package($topic, $profile);
        $payload['facts_package'] = $facts_package;

        MAIE_DB::update_job($job_id, [
            'step' => 'title',
            'message' => 'יוצר כותרת.',
            'payload' => $payload,
        ]);

        $title_response = self::generate_title($client, $profile, $facts_package);
        $payload['title_generation'] = $title_response;
        if (empty($title_response['ok'])) {
            self::fail_job($job_id, 'title_error', (string) ($title_response['error'] ?? 'יצירת הכותרת נכשלה.'), $payload);
            return;
        }

        $title = self::clean_title((string) ($title_response['data']['title'] ?? ''));
        if ($title === '') {
            self::fail_job($job_id, 'title_missing', 'המודל החזיר כותרת ריקה.', $payload);
            return;
        }

        if (self::is_duplicate_topic($title, $profile)) {
            self::skip_job($job_id, 'duplicate_title', 'הכותרת המוצעת דומה מדי לכתבה קיימת.', $payload);
            return;
        }

        MAIE_DB::update_job($job_id, [
            'step' => 'article',
            'message' => 'יוצר כתבה.',
            'payload' => $payload,
        ]);

        $article_response = self::generate_article($client, $profile, $title, $facts_package);
        $payload['article_generation'] = $article_response;
        if (empty($article_response['ok'])) {
            self::fail_job($job_id, 'article_error', (string) ($article_response['error'] ?? 'יצירת הכתבה נכשלה.'), $payload);
            return;
        }

        $article_data = $article_response['data'] ?? [];
        $content_html = self::clean_article_html((string) ($article_data['content_html'] ?? ''));
        $excerpt = sanitize_textarea_field((string) ($article_data['excerpt'] ?? ''));
        if ($content_html === '') {
            self::fail_job($job_id, 'article_missing', 'המודל החזיר גוף כתבה ריק.', $payload);
            return;
        }

        if (!empty($settings['qa_enabled'])) {
            MAIE_DB::update_job($job_id, [
                'step' => 'editorial_review',
                'message' => 'בודק איכות מערכתית.',
                'payload' => $payload,
            ]);

            $review_response = self::editorial_review($client, $profile, $title, $content_html, $excerpt, $facts_package);
            $payload['editorial_review'] = $review_response;
            if (empty($review_response['ok'])) {
                self::fail_job($job_id, 'review_error', (string) ($review_response['error'] ?? 'בדיקת האיכות נכשלה.'), $payload);
                return;
            }

            $review = $review_response['data'] ?? [];
            if (empty($review['approved'])) {
                $issues = isset($review['issues']) && is_array($review['issues']) ? implode(' | ', array_map('strval', $review['issues'])) : '';
                $message = $issues !== '' ? 'הכתבה נפסלה: ' . $issues : 'הכתבה נפסלה בבדיקת האיכות.';
                self::fail_job($job_id, 'review_rejected', $message, $payload);
                return;
            }

            $revised_title = self::clean_title((string) ($review['revised_title'] ?? ''));
            $revised_content = self::clean_article_html((string) ($review['revised_content_html'] ?? ''));
            $revised_excerpt = sanitize_textarea_field((string) ($review['revised_excerpt'] ?? ''));

            if ($revised_title !== '') {
                $title = $revised_title;
            }
            if ($revised_content !== '') {
                $content_html = $revised_content;
            }
            if ($revised_excerpt !== '') {
                $excerpt = $revised_excerpt;
            }
        }

        MAIE_DB::update_job($job_id, [
            'step' => 'image_metadata',
            'message' => 'יוצר הנחיית תמונה ושדות מדיה.',
            'payload' => $payload,
        ]);

        $image_meta_response = self::generate_image_metadata($client, $profile, $title, $content_html);
        $payload['image_metadata'] = $image_meta_response;
        if (empty($image_meta_response['ok'])) {
            if (!empty($settings['strict_publish_requires_image'])) {
                self::fail_job($job_id, 'image_metadata_error', (string) ($image_meta_response['error'] ?? 'יצירת מטא-נתוני התמונה נכשלה.'), $payload);
                return;
            }
        }

        $image_meta = is_array($image_meta_response['data'] ?? null) ? $image_meta_response['data'] : [];
        $image_result = null;
        $image_binary = '';
        $image_format = sanitize_text_field((string) ($settings['image_output_format'] ?? 'webp'));
        $image_review = null;

        if (!empty($image_meta['image_prompt'])) {
            MAIE_DB::update_job($job_id, [
                'step' => 'image_generation',
                'message' => 'יוצר תמונה ראשית.',
                'payload' => $payload,
            ]);

            $attempts = max(1, absint($settings['max_image_regeneration_attempts'] ?? 2) + 1);
            $base_prompt = (string) $image_meta['image_prompt'];
            $retry_guidance = '';

            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                $prompt_for_attempt = trim($base_prompt . "\n" . $retry_guidance);
                $image_result = $client->generate_image($prompt_for_attempt, self::effective_image_options($profile, $settings));
                $payload['image_generation_attempt_' . $attempt] = $image_result;

                if (empty($image_result['ok'])) {
                    continue;
                }

                $image_binary = base64_decode((string) ($image_result['image_b64'] ?? ''), true) ?: '';
                $image_format = sanitize_text_field((string) ($image_result['format'] ?? $image_format));
                if ($image_binary === '') {
                    continue;
                }

                if (empty($settings['image_qa_enabled'])) {
                    break;
                }

                MAIE_DB::update_job($job_id, [
                    'step' => 'image_review',
                    'message' => 'בודק רלוונטיות וניקיון תמונה.',
                    'payload' => $payload,
                ]);

                $image_review = self::review_image($client, $profile, $title, $topic, $image_binary, $image_format);
                $payload['image_review_attempt_' . $attempt] = $image_review;
                if (!empty($image_review['ok']) && !empty($image_review['data']['approved'])) {
                    break;
                }

                $retry_guidance = "\nImportant correction for regeneration: The previous image was rejected. " .
                    sanitize_text_field((string) ($image_review['data']['retry_guidance'] ?? 'Create a cleaner, more relevant image with absolutely no visible text or logos.'));
                $image_binary = '';
            }
        }

        if (!empty($settings['strict_publish_requires_image']) && $image_binary === '') {
            self::fail_job($job_id, 'image_failed', 'לא נוצרה תמונה ראשית תקינה לאחר ניסיונות היצירה והבדיקה.', $payload);
            return;
        }

        MAIE_DB::update_job($job_id, [
            'step' => 'post_insert',
            'message' => 'שומר כתבה בוורדפרס.',
            'payload' => $payload,
        ]);

        $post_id = self::insert_post($profile, $settings, $title, $content_html, $excerpt, $requested_post_status);
        if (is_wp_error($post_id) || absint($post_id) <= 0) {
            $message = is_wp_error($post_id) ? $post_id->get_error_message() : 'שמירת הפוסט נכשלה.';
            self::fail_job($job_id, 'post_insert_error', $message, $payload);
            return;
        }

        $post_id = absint($post_id);
        $payload['post_id'] = $post_id;
        update_post_meta($post_id, '_maie_generated', '1');
        update_post_meta($post_id, '_maie_profile_id', absint($profile['id'] ?? 0));
        update_post_meta($post_id, '_maie_job_id', $job_id);
        update_post_meta($post_id, '_maie_generated_at', current_time('mysql'));
        update_post_meta($post_id, '_maie_generation_source', $requested_post_status !== '' ? 'manual' : 'cron');

        if ($image_binary !== '') {
            $attachment = self::attach_featured_image($post_id, $title, $image_binary, $image_format, $image_meta);
            $payload['attachment'] = $attachment;
            if (is_wp_error($attachment)) {
                if (!empty($settings['strict_publish_requires_image'])) {
                    wp_update_post([
                        'ID' => $post_id,
                        'post_status' => 'draft',
                    ]);
                    self::fail_job($job_id, 'attachment_error', $attachment->get_error_message(), $payload, $post_id);
                    return;
                }
            }
        }

        MAIE_DB::update_job($job_id, [
            'status' => 'completed',
            'step' => 'completed',
            'post_id' => $post_id,
            'topic_title' => $topic_title,
            'message' => 'הכתבה נוצרה ונשמרה בהצלחה.',
            'payload' => $payload,
            'finished_at' => current_time('mysql'),
        ]);
    }

    private static function research_topic(MAIE_OpenAI $client, array $profile): array
    {
        $settings = MAIE_DB::get_settings();
        $use_web_search = self::profile_web_search_enabled($profile, $settings);
        $web_search_instruction = $use_web_search
            ? 'השתמש בחיפוש רשת עדכני.'
            : 'אין חיפוש רשת בפרופיל זה. בחר רק נושא שאינו תלוי בעובדות זמן-אמת, והימנע מחדשות מתפרצות.';
        $recent_titles = self::recent_titles_for_profile($profile);
        $recent_job_topics = MAIE_DB::recent_job_topics(absint($profile['id'] ?? 0));
        $window = max(1, absint($profile['search_window_hours'] ?? 12));
        $keywords = trim((string) ($profile['topic_keywords'] ?? ''));
        $preferred_domains = trim((string) ($profile['preferred_domains'] ?? ''));
        $blocked_domains = trim((string) ($profile['blocked_domains'] ?? ''));
        $prefer_israel = !empty($profile['prefer_israel']) ? 'כן' : 'לא';
        $mode = sanitize_key((string) ($profile['content_mode'] ?? 'news'));
        $brief = (string) ($profile['research_brief'] ?? '');

        $prompt = <<<PROMPT
{$brief}

פרטי הפרופיל:
- סוג תוכן: {$mode}
- חלון זמן נדרש: {$window} שעות
- עדיפות לזיקה לישראל: {$prefer_israel}
- מקורות מועדפים: {$preferred_domains}
- מקורות חסומים: {$blocked_domains}
- מילות מפתח ונושאי יעד:
{$keywords}

כותרות שפורסמו או נוצרו לאחרונה ויש להימנע מנושאים דומים להן:
{$recent_titles}
{$recent_job_topics}

הוראות קריטיות:
1. {$web_search_instruction}
2. עבור חדשות, ודא שההתפתחות המרכזית חדשה באמת בתוך חלון הזמן, לא רק שכתבה ישנה פורסמה מחדש.
3. אם אין ודאות סבירה לגבי טריות האירוע, דלג.
4. החזר חבילת עובדות מסודרת ומאומתת בלבד. אל תכתוב עדיין כתבה מלאה.
PROMPT;

        return $client->responses_json($prompt, self::research_schema(), [
            'model' => self::effective_text_model($profile, $settings),
            'web_search' => $use_web_search,
            'schema_name' => 'maie_research_topic',
            'reasoning_effort' => 'medium',
        ]);
    }

    private static function generate_title(MAIE_OpenAI $client, array $profile, string $facts_package): array
    {
        $prompt = strtr((string) ($profile['title_prompt'] ?? ''), [
            '{{FACTS}}' => $facts_package,
        ]);

        $settings = MAIE_DB::get_settings();
        return $client->responses_json($prompt, self::title_schema(), [
            'model' => self::effective_text_model($profile, $settings),
            'schema_name' => 'maie_title',
            'reasoning_effort' => 'low',
        ]);
    }

    private static function generate_article(MAIE_OpenAI $client, array $profile, string $title, string $facts_package): array
    {
        $prompt = strtr((string) ($profile['article_prompt'] ?? ''), [
            '{{TITLE}}' => $title,
            '{{FACTS}}' => $facts_package,
        ]);

        $settings = MAIE_DB::get_settings();
        return $client->responses_json($prompt, self::article_schema(), [
            'model' => self::effective_text_model($profile, $settings),
            'schema_name' => 'maie_article',
            'reasoning_effort' => 'medium',
        ]);
    }

    private static function editorial_review(MAIE_OpenAI $client, array $profile, string $title, string $content_html, string $excerpt, string $facts_package): array
    {
        $prompt = MAIE_Prompts::editorial_review_prompt() . <<<PROMPT

קטגוריה:
{$profile['category_name']}

כותרת לבדיקה:
{$title}

תקציר:
{$excerpt}

גוף כתבה:
{$content_html}

חבילת העובדות שעליה מותר להסתמך בלבד:
{$facts_package}
PROMPT;

        $settings = MAIE_DB::get_settings();
        return $client->responses_json($prompt, self::editorial_review_schema(), [
            'model' => self::effective_qa_model($profile, $settings),
            'schema_name' => 'maie_editorial_review',
            'reasoning_effort' => 'medium',
        ]);
    }

    private static function generate_image_metadata(MAIE_OpenAI $client, array $profile, string $title, string $content_html): array
    {
        $prompt = strtr((string) ($profile['image_prompt'] ?? ''), [
            '{{TITLE}}' => $title,
            '{{ARTICLE}}' => wp_strip_all_tags($content_html),
        ]);

        $settings = MAIE_DB::get_settings();
        return $client->responses_json($prompt, self::image_metadata_schema(), [
            'model' => self::effective_text_model($profile, $settings),
            'schema_name' => 'maie_image_metadata',
            'reasoning_effort' => 'low',
        ]);
    }

    private static function review_image(MAIE_OpenAI $client, array $profile, string $title, array $topic, string $image_binary, string $format): array
    {
        $mime_type = match ($format) {
            'png' => 'image/png',
            'jpeg', 'jpg' => 'image/jpeg',
            default => 'image/webp',
        };
        $base64 = base64_encode($image_binary);
        $summary = sanitize_textarea_field((string) ($topic['topic_summary'] ?? ''));
        $angle = sanitize_textarea_field((string) ($topic['recommended_angle'] ?? ''));

        $prompt = MAIE_Prompts::image_review_prompt() . <<<PROMPT

כותרת הכתבה:
{$title}

תקציר הסיפור:
{$summary}

זווית מרכזית:
{$angle}
PROMPT;

        $settings = MAIE_DB::get_settings();
        return $client->responses_vision_json($prompt, $mime_type, $base64, self::image_review_schema(), [
            'model' => self::effective_vision_model($profile, $settings),
            'schema_name' => 'maie_image_review',
            'reasoning_effort' => 'low',
        ]);
    }


    private static function effective_text_model(array $profile, array $settings): string
    {
        $override = sanitize_text_field((string) ($profile['text_model_override'] ?? ''));
        if ($override !== '') {
            return MAIE_Options::allowed_text_model($override, (string) ($settings['text_model'] ?? 'gpt-5.5'));
        }
        return MAIE_Options::allowed_text_model((string) ($settings['text_model'] ?? 'gpt-5.5'), 'gpt-5.5');
    }

    private static function effective_qa_model(array $profile, array $settings): string
    {
        $override = sanitize_text_field((string) ($profile['qa_model_override'] ?? ''));
        if ($override !== '') {
            return MAIE_Options::allowed_qa_model($override, (string) ($settings['qa_model'] ?? 'gpt-5.5'));
        }
        return MAIE_Options::allowed_qa_model((string) ($settings['qa_model'] ?? 'gpt-5.5'), 'gpt-5.5');
    }

    private static function effective_vision_model(array $profile, array $settings): string
    {
        $override = sanitize_text_field((string) ($profile['vision_model_override'] ?? ''));
        if ($override !== '') {
            return MAIE_Options::allowed_vision_model($override, (string) ($settings['vision_model'] ?? 'gpt-5.4'));
        }
        return MAIE_Options::allowed_vision_model((string) ($settings['vision_model'] ?? 'gpt-5.4'), 'gpt-5.4');
    }

    private static function effective_image_options(array $profile, array $settings): array
    {
        $model_override = sanitize_text_field((string) ($profile['image_model_override'] ?? ''));
        $size_override = sanitize_text_field((string) ($profile['image_size_override'] ?? ''));
        $quality_override = sanitize_text_field((string) ($profile['image_quality_override'] ?? ''));

        return [
            'model' => $model_override !== ''
                ? MAIE_Options::allowed_image_model($model_override, (string) ($settings['image_model'] ?? 'gpt-image-2'))
                : MAIE_Options::allowed_image_model((string) ($settings['image_model'] ?? 'gpt-image-2'), 'gpt-image-2'),
            'size' => $size_override !== ''
                ? MAIE_Options::allowed_image_size($size_override, (string) ($settings['image_size'] ?? '1280x720'))
                : MAIE_Options::allowed_image_size((string) ($settings['image_size'] ?? '1280x720'), '1280x720'),
            'quality' => $quality_override !== ''
                ? MAIE_Options::allowed_image_quality($quality_override, (string) ($settings['image_quality'] ?? 'medium'))
                : MAIE_Options::allowed_image_quality((string) ($settings['image_quality'] ?? 'medium'), 'medium'),
        ];
    }

    private static function profile_web_search_enabled(array $profile, array $settings): bool
    {
        if (empty($settings['web_search_enabled'])) {
            return false;
        }

        $mode = MAIE_Options::allowed_web_search_mode(sanitize_key((string) ($profile['web_search_mode'] ?? 'inherit')));
        if ($mode === 'enabled') {
            return true;
        }
        if ($mode === 'disabled') {
            return false;
        }
        return !empty($settings['web_search_enabled']);
    }

    private static function build_facts_package(array $topic, array $profile): string
    {
        $facts = isset($topic['facts']) && is_array($topic['facts']) ? $topic['facts'] : [];
        $sources = isset($topic['source_notes']) && is_array($topic['source_notes']) ? $topic['source_notes'] : [];
        $lines = [];
        $lines[] = 'קטגוריה: ' . sanitize_text_field((string) ($profile['category_name'] ?? ''));
        $lines[] = 'נושא: ' . sanitize_text_field((string) ($topic['topic_title'] ?? ''));
        $lines[] = 'תקציר עובדתי: ' . sanitize_textarea_field((string) ($topic['topic_summary'] ?? ''));
        $lines[] = 'זווית מומלצת: ' . sanitize_textarea_field((string) ($topic['recommended_angle'] ?? ''));
        $lines[] = 'מדוע הנושא חשוב: ' . sanitize_textarea_field((string) ($topic['why_newsworthy'] ?? ''));
        $lines[] = 'אומדן גיל ההתפתחות בשעות: ' . sanitize_text_field((string) ($topic['estimated_event_age_hours'] ?? ''));
        $lines[] = 'רמת ביטחון בטריות: ' . sanitize_text_field((string) ($topic['freshness_confidence'] ?? ''));
        $lines[] = 'זיקה לישראל: ' . (!empty($topic['is_israel_related']) ? 'כן' : 'לא');
        $lines[] = '';
        $lines[] = 'עובדות מאומתות לשימוש בכתבה בלבד:';
        foreach ($facts as $fact) {
            $fact = trim(sanitize_textarea_field((string) $fact));
            if ($fact !== '') {
                $lines[] = '- ' . $fact;
            }
        }
        if ($sources) {
            $lines[] = '';
            $lines[] = 'הערות אימות פנימיות, לא לפרסום:';
            foreach ($sources as $source_note) {
                $source_note = trim(sanitize_textarea_field((string) $source_note));
                if ($source_note !== '') {
                    $lines[] = '- ' . $source_note;
                }
            }
        }

        return implode("\n", $lines);
    }

    private static function recent_titles_for_profile(array $profile): string
    {
        $category_id = absint($profile['category_id'] ?? 0);
        if ($category_id <= 0) {
            return '- אין מידע';
        }

        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'pending', 'future'],
            'numberposts' => 25,
            'orderby' => 'date',
            'order' => 'DESC',
            'category' => $category_id,
            'date_query' => [
                [
                    'after' => '7 days ago',
                    'inclusive' => true,
                ],
            ],
        ]);

        if (!$posts) {
            return '- אין כותרות רלוונטיות מהשבוע האחרון.';
        }

        $lines = [];
        foreach ($posts as $post) {
            if ($post instanceof WP_Post) {
                $title = trim(wp_strip_all_tags(get_the_title($post)));
                if ($title !== '') {
                    $lines[] = '- ' . $title;
                }
            }
        }

        return $lines ? implode("\n", $lines) : '- אין כותרות רלוונטיות מהשבוע האחרון.';
    }

    private static function is_duplicate_topic(string $candidate, array $profile): bool
    {
        $candidate_norm = self::normalize_for_similarity($candidate);
        if ($candidate_norm === '') {
            return false;
        }

        $existing = [];
        $category_id = absint($profile['category_id'] ?? 0);
        if ($category_id > 0) {
            $posts = get_posts([
                'post_type' => 'post',
                'post_status' => ['publish', 'draft', 'pending', 'future'],
                'numberposts' => 30,
                'orderby' => 'date',
                'order' => 'DESC',
                'category' => $category_id,
                'date_query' => [
                    [
                        'after' => '10 days ago',
                        'inclusive' => true,
                    ],
                ],
            ]);
            foreach ($posts as $post) {
                if ($post instanceof WP_Post) {
                    $existing[] = get_the_title($post);
                }
            }
        }

        $existing = array_merge($existing, MAIE_DB::recent_job_topics(absint($profile['id'] ?? 0), 10, 40));
        foreach ($existing as $title) {
            $existing_norm = self::normalize_for_similarity((string) $title);
            if ($existing_norm === '') {
                continue;
            }
            similar_text($candidate_norm, $existing_norm, $percent);
            if ($percent >= 78.0) {
                return true;
            }
            if (str_contains($existing_norm, $candidate_norm) || str_contains($candidate_norm, $existing_norm)) {
                if (mb_strlen($candidate_norm, 'UTF-8') >= 18 && mb_strlen($existing_norm, 'UTF-8') >= 18) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function normalize_for_similarity(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = wp_strip_all_tags($text);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?: $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?: $text;
        return trim($text);
    }

    private static function clean_title(string $title): string
    {
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = wp_strip_all_tags($title);
        $title = trim(preg_replace('/\s+/u', ' ', $title) ?: $title);
        $title = str_replace(';', ',', $title);
        $title = rtrim($title, ". \t\n\r\0\x0B");
        return sanitize_text_field($title);
    }

    private static function clean_article_html(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $allowed = [
            'p' => [],
            'strong' => [],
            'em' => [],
            'br' => [],
            'blockquote' => [],
        ];

        $html = wp_kses($html, $allowed);
        if (!str_contains($html, '<p>')) {
            $paragraphs = preg_split('/\n{2,}/u', wp_strip_all_tags($html));
            $parts = [];
            if (is_array($paragraphs)) {
                foreach ($paragraphs as $paragraph) {
                    $paragraph = trim((string) $paragraph);
                    if ($paragraph !== '') {
                        $parts[] = '<p>' . esc_html($paragraph) . '</p>';
                    }
                }
            }
            $html = implode("\n", $parts);
        }

        return trim($html);
    }

    private static function insert_post(array $profile, array $settings, string $title, string $content_html, string $excerpt, string $post_status_override = '')
    {
        $author = absint($settings['default_post_author'] ?? 1);
        if ($author <= 0 || !get_user_by('id', $author)) {
            $author = get_current_user_id() ?: 1;
        }

        $post_status_override = sanitize_key($post_status_override);
        if (in_array($post_status_override, ['draft', 'publish'], true)) {
            $post_status = $post_status_override;
        } else {
            $post_status = in_array((string) ($profile['post_status'] ?? 'draft'), ['draft', 'publish', 'pending'], true)
                ? (string) $profile['post_status']
                : 'draft';
        }

        return wp_insert_post([
            'post_type' => 'post',
            'post_title' => $title,
            'post_content' => $content_html,
            'post_excerpt' => $excerpt,
            'post_status' => $post_status,
            'post_author' => $author,
            'post_category' => [absint($profile['category_id'] ?? 0)],
        ], true);
    }

    private static function attach_featured_image(int $post_id, string $title, string $image_binary, string $format, array $image_meta)
    {
        $extension = match ($format) {
            'png' => 'png',
            'jpeg', 'jpg' => 'jpg',
            default => 'webp',
        };
        $mime = match ($extension) {
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            default => 'image/webp',
        };

        $filename = 'mivzak-ai-' . $post_id . '-' . wp_generate_password(8, false, false) . '.' . $extension;
        $upload = wp_upload_bits($filename, null, $image_binary);
        if (!empty($upload['error'])) {
            return new WP_Error('maie_image_upload_error', sanitize_text_field((string) $upload['error']));
        }

        $attachment_id = wp_insert_attachment([
            'post_mime_type' => $mime,
            'post_title' => $title,
            'post_content' => self::keywords_to_description($image_meta['media_keywords'] ?? []),
            'post_excerpt' => sanitize_text_field((string) ($image_meta['caption'] ?? 'אילוסטרציה. חדשות מבזק לייב AI')),
            'post_status' => 'inherit',
        ], $upload['file'], $post_id, true);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        if (is_array($metadata)) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        $alt_text = sanitize_text_field((string) ($image_meta['alt_text'] ?? $title));
        if ($alt_text !== '') {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        }

        $keywords = $image_meta['media_keywords'] ?? [];
        if (is_array($keywords)) {
            update_post_meta($attachment_id, '_maie_media_keywords', array_values(array_filter(array_map('sanitize_text_field', $keywords))));
        }

        set_post_thumbnail($post_id, $attachment_id);
        return $attachment_id;
    }

    private static function keywords_to_description($keywords): string
    {
        if (!is_array($keywords)) {
            return '';
        }
        $clean = array_values(array_filter(array_map(static fn($keyword) => sanitize_text_field((string) $keyword), $keywords)));
        return implode(', ', $clean);
    }

    private static function fail_job(int $job_id, string $step, string $message, array $payload, int $post_id = 0): void
    {
        MAIE_DB::update_job($job_id, [
            'status' => 'failed',
            'step' => $step,
            'post_id' => $post_id,
            'message' => $message,
            'payload' => $payload,
            'finished_at' => current_time('mysql'),
        ]);
    }

    private static function skip_job(int $job_id, string $step, string $message, array $payload): void
    {
        MAIE_DB::update_job($job_id, [
            'status' => 'skipped',
            'step' => $step,
            'message' => $message,
            'payload' => $payload,
            'finished_at' => current_time('mysql'),
        ]);
    }

    private static function research_schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'status' => ['type' => 'string', 'enum' => ['ok', 'skip']],
                'skip_reason' => ['type' => 'string'],
                'topic_title' => ['type' => 'string'],
                'topic_summary' => ['type' => 'string'],
                'why_newsworthy' => ['type' => 'string'],
                'recommended_angle' => ['type' => 'string'],
                'estimated_event_age_hours' => ['type' => 'number'],
                'freshness_confidence' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                'is_israel_related' => ['type' => 'boolean'],
                'facts' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'source_notes' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => [
                'status', 'skip_reason', 'topic_title', 'topic_summary', 'why_newsworthy', 'recommended_angle',
                'estimated_event_age_hours', 'freshness_confidence', 'is_israel_related', 'facts', 'source_notes'
            ],
        ];
    }

    private static function title_schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'title' => ['type' => 'string'],
            ],
            'required' => ['title'],
        ];
    }

    private static function article_schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'content_html' => ['type' => 'string'],
                'excerpt' => ['type' => 'string'],
                'suggested_tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['content_html', 'excerpt', 'suggested_tags'],
        ];
    }

    private static function editorial_review_schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'approved' => ['type' => 'boolean'],
                'severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                'issues' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'revised_title' => ['type' => 'string'],
                'revised_content_html' => ['type' => 'string'],
                'revised_excerpt' => ['type' => 'string'],
                'final_notes' => ['type' => 'string'],
            ],
            'required' => ['approved', 'severity', 'issues', 'revised_title', 'revised_content_html', 'revised_excerpt', 'final_notes'],
        ];
    }

    private static function image_metadata_schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'image_prompt' => ['type' => 'string'],
                'caption' => ['type' => 'string'],
                'alt_text' => ['type' => 'string'],
                'media_keywords' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['image_prompt', 'caption', 'alt_text', 'media_keywords'],
        ];
    }

    private static function image_review_schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'approved' => ['type' => 'boolean'],
                'contains_visible_text' => ['type' => 'boolean'],
                'relevant_to_article' => ['type' => 'boolean'],
                'issue_summary' => ['type' => 'string'],
                'retry_guidance' => ['type' => 'string'],
            ],
            'required' => ['approved', 'contains_visible_text', 'relevant_to_article', 'issue_summary', 'retry_guidance'],
        ];
    }
}
