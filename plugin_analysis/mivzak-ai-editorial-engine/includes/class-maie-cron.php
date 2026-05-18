<?php

if (!defined('ABSPATH')) {
    exit;
}

final class MAIE_Cron
{
    public static function init(): void
    {
        add_filter('cron_schedules', [__CLASS__, 'add_custom_schedule']);
        add_action('maie_cron_event', [__CLASS__, 'run']);
        add_action('maie_manual_cron_run_event', [__CLASS__, 'run']);
        add_action('maie_run_job_event', ['MAIE_Generator', 'run_job'], 10, 1);
    }

    public static function add_custom_schedule(array $schedules): array
    {
        if (!isset($schedules['maie_every_five_minutes'])) {
            $schedules['maie_every_five_minutes'] = [
                'interval' => 5 * MINUTE_IN_SECONDS,
                'display' => __('Every 5 minutes - Mivzak AI Editorial Engine', 'mivzak-ai-editorial-engine'),
            ];
        }

        return $schedules;
    }

    public static function run(): void
    {
        // אם אירוע ה-Cron החוזר חסר משום מה, משחזרים אותו לפני עיבוד המשימות.
        // הדבר חשוב במיוחד לאחר עדכון תוסף או בסביבות שבהן אירועי WP-Cron נמחקו.
        if (class_exists('MAIE_Install')) {
            MAIE_Install::ensure_cron_scheduled();
        }

        $started_at = current_time('mysql');
        $settings = MAIE_DB::get_settings();

        if (empty($settings['auto_cron_enabled'])) {
            self::update_cron_health([
                'last_run_at' => $started_at,
                'last_status' => 'disabled',
                'last_summary' => 'Cron הופעל, אך האוטומציה כבויה בהגדרות.',
                'enabled_profiles' => MAIE_DB::count_enabled_profiles(),
                'due_profiles' => 0,
                'jobs_attempted' => 0,
                'jobs_completed' => 0,
                'jobs_skipped' => 0,
                'jobs_failed' => 0,
            ]);
            return;
        }

        $max_jobs = max(1, absint($settings['max_jobs_per_cron'] ?? 1));
        $profiles = MAIE_DB::get_profiles(['enabled' => 1]);
        $eligible_profiles = [];

        foreach ($profiles as $profile) {
            $profile_id = absint($profile['id'] ?? 0);
            if ($profile_id <= 0) {
                continue;
            }

            if (!MAIE_DB::profile_is_due($profile)) {
                continue;
            }

            $daily_cap = max(0, absint($profile['max_posts_per_day'] ?? 0));
            if ($daily_cap > 0 && MAIE_DB::daily_completed_count($profile_id) >= $daily_cap) {
                continue;
            }

            $eligible_profiles[] = [
                'profile' => $profile,
                'last_job_created_at' => MAIE_DB::last_job_created_at($profile_id),
            ];
        }

        usort($eligible_profiles, static function (array $left, array $right): int {
            $left_time = !empty($left['last_job_created_at']) ? strtotime((string) $left['last_job_created_at']) : 0;
            $right_time = !empty($right['last_job_created_at']) ? strtotime((string) $right['last_job_created_at']) : 0;
            return $left_time <=> $right_time;
        });

        $attempted = 0;
        $completed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($eligible_profiles as $eligible) {
            if ($attempted >= $max_jobs) {
                break;
            }

            $profile = is_array($eligible['profile'] ?? null) ? $eligible['profile'] : [];
            $profile_id = absint($profile['id'] ?? 0);
            if ($profile_id <= 0) {
                continue;
            }

            $job_id = MAIE_DB::create_job($profile_id, 'queued', 'cron', 'נוצרה משימת Cron אוטומטית.');
            if ($job_id <= 0) {
                continue;
            }

            MAIE_Generator::run_job($job_id);
            $attempted++;

            $job = MAIE_DB::get_job($job_id);
            $status = sanitize_key((string) ($job['status'] ?? ''));
            if ($status === 'completed') {
                $completed++;
            } elseif ($status === 'skipped') {
                $skipped++;
            } elseif ($status === 'failed') {
                $failed++;
            }
        }

        $due_count = count($eligible_profiles);
        if ($attempted === 0) {
            $summary = $due_count === 0
                ? 'Cron רץ בהצלחה, אך לא נמצא פרופיל שהגיע למועד יצירה או שהיה מותר לו ליצור כתבה כעת.'
                : 'Cron רץ, אך לא נוצרה משימה חדשה.';
        } else {
            $summary = sprintf(
                'Cron רץ: %d משימות הופעלו, %d הושלמו, %d דולגו, %d נכשלו.',
                $attempted,
                $completed,
                $skipped,
                $failed
            );
        }

        self::update_cron_health([
            'last_run_at' => $started_at,
            'last_status' => $attempted > 0 ? 'processed' : 'idle',
            'last_summary' => $summary,
            'enabled_profiles' => MAIE_DB::count_enabled_profiles(),
            'due_profiles' => $due_count,
            'jobs_attempted' => $attempted,
            'jobs_completed' => $completed,
            'jobs_skipped' => $skipped,
            'jobs_failed' => $failed,
        ]);
    }

    public static function get_cron_health(): array
    {
        $health = get_option('maie_cron_health', []);
        return is_array($health) ? $health : [];
    }

    private static function update_cron_health(array $health): void
    {
        $existing = self::get_cron_health();
        update_option('maie_cron_health', array_merge($existing, $health), false);
    }

}
