<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap salon-wrap" dir="rtl">
    <h1 class="salon-page-title">
        <span class="dashicons dashicons-scissors"></span>
        <?php echo esc_html( $settings['business_name'] ?? 'סלון היופי' ); ?>
    </h1>

    <div class="salon-stats-grid">
        <div class="salon-stat-card salon-stat-today">
            <div class="salon-stat-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
            <div class="salon-stat-info">
                <span class="salon-stat-number"><?php echo $today; ?></span>
                <span class="salon-stat-label">תורים היום</span>
            </div>
        </div>
        <div class="salon-stat-card salon-stat-month">
            <div class="salon-stat-icon"><span class="dashicons dashicons-chart-bar"></span></div>
            <div class="salon-stat-info">
                <span class="salon-stat-number"><?php echo $month; ?></span>
                <span class="salon-stat-label">תורים החודש</span>
            </div>
        </div>
        <div class="salon-stat-card salon-stat-revenue">
            <div class="salon-stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
            <div class="salon-stat-info">
                <span class="salon-stat-number"><?php echo esc_html( $settings['currency'] ?? '₪' ); ?><?php echo number_format( $revenue, 0 ); ?></span>
                <span class="salon-stat-label">הכנסות החודש</span>
            </div>
        </div>
        <div class="salon-stat-card salon-stat-customers">
            <div class="salon-stat-icon"><span class="dashicons dashicons-groups"></span></div>
            <div class="salon-stat-info">
                <span class="salon-stat-number"><?php echo $customers; ?></span>
                <span class="salon-stat-label">לקוחות</span>
            </div>
        </div>
    </div>

    <div class="salon-dashboard-grid">
        <div class="salon-card">
            <div class="salon-card-header">
                <h2>תורים קרובים</h2>
                <a href="<?php echo admin_url('admin.php?page=salon-appointments&action=new'); ?>" class="button button-primary">+ תור חדש</a>
            </div>
            <?php if ( empty( $upcoming ) ) : ?>
                <p class="salon-empty">אין תורים קרובים</p>
            <?php else : ?>
                <table class="salon-table">
                    <thead><tr>
                        <th>תאריך</th><th>שעה</th><th>לקוח</th><th>טלפון</th><th>שירות</th><th>מחיר</th><th>פעולות</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ( $upcoming as $a ) : ?>
                        <tr>
                            <td><?php echo date_i18n( 'd/m/Y', strtotime( $a->appointment_date ) ); ?></td>
                            <td><?php echo substr( $a->appointment_time, 0, 5 ); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=salon-customers&action=view&id=' . $a->customer_id); ?>">
                                    <?php echo esc_html( $a->customer_name ); ?>
                                </a>
                            </td>
                            <td><a href="tel:<?php echo esc_attr($a->phone); ?>"><?php echo esc_html($a->phone); ?></a></td>
                            <td><?php echo esc_html( $a->service ); ?></td>
                            <td><?php echo esc_html($settings['currency']); ?><?php echo number_format($a->price,0); ?></td>
                            <td class="salon-actions">
                                <a href="<?php echo admin_url('admin.php?page=salon-appointments&action=edit&id='.$a->id); ?>" class="button button-small">עריכה</a>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
                                    <?php wp_nonce_field('salon_update_status'); ?>
                                    <input type="hidden" name="action" value="salon_update_appointment_status">
                                    <input type="hidden" name="appointment_id" value="<?php echo $a->id; ?>">
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="button button-small button-success">בוצע</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $debtors ) ) : ?>
        <div class="salon-card salon-card-warning">
            <div class="salon-card-header">
                <h2>חובות פתוחים</h2>
            </div>
            <table class="salon-table">
                <thead><tr>
                    <th>לקוח</th><th>טלפון</th><th>חוב</th>
                </tr></thead>
                <tbody>
                <?php foreach ( $debtors as $d ) : $balance = $d->total_owed - $d->total_paid; ?>
                    <tr>
                        <td><a href="<?php echo admin_url('admin.php?page=salon-customers&action=view&id='.$d->id); ?>"><?php echo esc_html($d->customer_name); ?></a></td>
                        <td><a href="tel:<?php echo esc_attr($d->phone); ?>"><?php echo esc_html($d->phone); ?></a></td>
                        <td class="salon-debt"><?php echo esc_html($settings['currency']); ?><?php echo number_format($balance,0); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="salon-quick-actions">
        <a href="<?php echo admin_url('admin.php?page=salon-appointments&action=new'); ?>" class="salon-quick-btn">
            <span class="dashicons dashicons-plus-alt"></span> קביעת תור
        </a>
        <a href="<?php echo admin_url('admin.php?page=salon-customers&action=new'); ?>" class="salon-quick-btn">
            <span class="dashicons dashicons-admin-users"></span> לקוח חדש
        </a>
        <a href="<?php echo admin_url('admin.php?page=salon-appointments&view=day&date='.date('Y-m-d')); ?>" class="salon-quick-btn">
            <span class="dashicons dashicons-calendar"></span> תורים היום
        </a>
        <a href="<?php echo admin_url('admin.php?page=salon-payments'); ?>" class="salon-quick-btn">
            <span class="dashicons dashicons-money-alt"></span> תשלומים
        </a>
    </div>
</div>
