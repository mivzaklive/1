<?php if ( ! defined( 'ABSPATH' ) ) exit;

$currency = $settings['currency'] ?? '₪';
$today    = date('Y-m-d');

$prev_date = date('Y-m-d', strtotime($date . ' -1 ' . ($view === 'month' ? 'month' : 'week')));
$next_date = date('Y-m-d', strtotime($date . ' +1 ' . ($view === 'month' ? 'month' : 'week')));

$status_labels = [
    'scheduled' => 'מתוכנן',
    'completed'  => 'בוצע',
    'cancelled'  => 'בוטל',
    'no_show'    => 'לא הגיע',
];
?>
<div class="wrap salon-wrap" dir="rtl">
    <h1 class="salon-page-title">
        <span class="dashicons dashicons-calendar-alt"></span> תורים
        <a href="<?php echo admin_url('admin.php?page=salon-appointments&action=new'); ?>" class="page-title-action">+ תור חדש</a>
    </h1>

    <?php if ( isset($_GET['saved']) ) : ?><div class="notice notice-success is-dismissible"><p>התור נשמר בהצלחה</p></div><?php endif; ?>
    <?php if ( isset($_GET['deleted']) ) : ?><div class="notice notice-success is-dismissible"><p>התור נמחק</p></div><?php endif; ?>

    <div class="salon-toolbar">
        <div class="salon-view-switcher">
            <a href="<?php echo admin_url('admin.php?page=salon-appointments&view=day&date='.$date); ?>"
               class="button <?php echo $view==='day' ? 'button-primary' : ''; ?>">יום</a>
            <a href="<?php echo admin_url('admin.php?page=salon-appointments&view=week&date='.$date); ?>"
               class="button <?php echo $view==='week' ? 'button-primary' : ''; ?>">שבוע</a>
            <a href="<?php echo admin_url('admin.php?page=salon-appointments&view=month&date='.$date); ?>"
               class="button <?php echo $view==='month' ? 'button-primary' : ''; ?>">חודש</a>
        </div>

        <div class="salon-date-nav">
            <a href="<?php echo admin_url('admin.php?page=salon-appointments&view='.$view.'&date='.$prev_date); ?>" class="button">&#8250;</a>
            <span class="salon-current-date">
                <?php
                if ($view === 'month') {
                    echo date_i18n('F Y', strtotime($date));
                } elseif ($view === 'week') {
                    $mon = date('d/m', strtotime('monday this week', strtotime($date)));
                    $sun = date('d/m/Y', strtotime('sunday this week', strtotime($date)));
                    echo $mon . ' - ' . $sun;
                } else {
                    echo date_i18n('l, d/m/Y', strtotime($date));
                }
                ?>
            </span>
            <a href="<?php echo admin_url('admin.php?page=salon-appointments&view='.$view.'&date='.$next_date); ?>" class="button">&#8249;</a>
            <a href="<?php echo admin_url('admin.php?page=salon-appointments&view='.$view.'&date='.$today); ?>" class="button">היום</a>
        </div>

        <form method="get" style="display:inline">
            <input type="hidden" name="page" value="salon-appointments">
            <input type="hidden" name="view" value="<?php echo esc_attr($view); ?>">
            <input type="date" name="date" value="<?php echo esc_attr($date); ?>" class="salon-date-picker" onchange="this.form.submit()">
        </form>
    </div>

    <?php if ( $view === 'month' ) : ?>
    <?php
        $y = (int)date('Y', strtotime($date));
        $m = (int)date('m', strtotime($date));
        $first_day = mktime(0,0,0,$m,1,$y);
        $days_in_month = date('t', $first_day);
        $start_dow = (int)date('N', $first_day);
        $by_day = [];
        foreach ($appointments as $a) $by_day[$a->appointment_date][] = $a;
    ?>
    <div class="salon-month-grid">
        <div class="salon-month-header">
            <?php foreach(['ראש','שני','שלישי','רביעי','חמישי','שישי','שבת'] as $d) : ?>
                <div class="salon-month-dow"><?php echo $d; ?></div>
            <?php endforeach; ?>
        </div>
        <div class="salon-month-body">
            <?php
            $dow = $start_dow % 7;
            for ($i = 0; $i < $dow; $i++) echo '<div class="salon-month-cell salon-empty-cell"></div>';
            for ($d = 1; $d <= $days_in_month; $d++) :
                $cell_date = sprintf('%04d-%02d-%02d', $y, $m, $d);
                $is_today = $cell_date === $today;
                $cell_appts = $by_day[$cell_date] ?? [];
            ?>
            <div class="salon-month-cell <?php echo $is_today ? 'salon-today' : ''; ?>">
                <div class="salon-month-day-num">
                    <a href="<?php echo admin_url('admin.php?page=salon-appointments&view=day&date='.$cell_date); ?>"><?php echo $d; ?></a>
                </div>
                <?php foreach ($cell_appts as $a) : ?>
                <div class="salon-month-appt salon-status-<?php echo esc_attr($a->status); ?>">
                    <?php echo substr($a->appointment_time,0,5); ?> <?php echo esc_html($a->customer_name); ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <?php else : ?>

    <?php if ( empty($appointments) ) : ?>
        <div class="salon-card"><p class="salon-empty">אין תורים בתקופה הנבחרת</p></div>
    <?php else : ?>
    <div class="salon-card">
        <table class="salon-table">
            <thead><tr>
                <?php if ($view === 'week') : ?><th>יום</th><?php endif; ?>
                <th>שעה</th><th>לקוח</th><th>טלפון</th><th>שירות</th><th>משך</th><th>מחיר</th><th>סטטוס</th><th>פעולות</th>
            </tr></thead>
            <tbody>
            <?php foreach ($appointments as $a) : ?>
            <tr class="salon-row-<?php echo esc_attr($a->status); ?>">
                <?php if ($view === 'week') : ?>
                <td><?php echo date_i18n('l d/m', strtotime($a->appointment_date)); ?></td>
                <?php endif; ?>
                <td class="salon-time"><?php echo substr($a->appointment_time,0,5); ?></td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=salon-customers&action=view&id='.$a->customer_id); ?>">
                        <?php echo esc_html($a->customer_name); ?>
                    </a>
                </td>
                <td><a href="tel:<?php echo esc_attr($a->phone); ?>"><?php echo esc_html($a->phone); ?></a></td>
                <td><?php echo esc_html($a->service); ?></td>
                <td><?php echo $a->duration; ?> דק'</td>
                <td><?php echo $currency.number_format($a->price,0); ?></td>
                <td><span class="salon-badge salon-badge-<?php echo esc_attr($a->status); ?>"><?php echo $status_labels[$a->status] ?? $a->status; ?></span></td>
                <td class="salon-actions">
                    <a href="<?php echo admin_url('admin.php?page=salon-appointments&action=edit&id='.$a->id); ?>" class="button button-small">עריכה</a>
                    <?php if ($a->status === 'scheduled') : ?>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
                        <?php wp_nonce_field('salon_update_status'); ?>
                        <input type="hidden" name="action" value="salon_update_appointment_status">
                        <input type="hidden" name="appointment_id" value="<?php echo $a->id; ?>">
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="button button-small button-success">בוצע</button>
                    </form>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
                        <?php wp_nonce_field('salon_update_status'); ?>
                        <input type="hidden" name="action" value="salon_update_appointment_status">
                        <input type="hidden" name="appointment_id" value="<?php echo $a->id; ?>">
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="button button-small button-link-delete">בטל</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
