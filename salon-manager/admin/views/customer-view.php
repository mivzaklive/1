<?php if ( ! defined( 'ABSPATH' ) ) exit;
$currency = $settings['currency'] ?? '₪';
$status_labels = ['scheduled'=>'מתוכנן','completed'=>'בוצע','cancelled'=>'בוטל','no_show'=>'לא הגיע'];
?>
<div class="wrap salon-wrap" dir="rtl">
    <h1 class="salon-page-title">
        <span class="dashicons dashicons-admin-users"></span>
        <?php echo esc_html($customer->first_name . ' ' . $customer->last_name); ?>
    </h1>
    <div class="salon-customer-actions-top">
        <a href="<?php echo admin_url('admin.php?page=salon-customers'); ?>" class="button">← חזרה לאלפון</a>
        <a href="<?php echo admin_url('admin.php?page=salon-customers&action=edit&id='.$customer->id); ?>" class="button">עריכת פרטים</a>
        <a href="<?php echo admin_url('admin.php?page=salon-appointments&action=new'); ?>" class="button button-primary">+ קבע תור</a>
    </div>

    <?php if (isset($_GET['saved'])) : ?><div class="notice notice-success is-dismissible"><p>הלקוח נשמר בהצלחה</p></div><?php endif; ?>

    <div class="salon-customer-grid">
        <div class="salon-card salon-customer-info">
            <h2>פרטי לקוח</h2>
            <table class="salon-info-table">
                <tr><th>שם</th><td><?php echo esc_html($customer->first_name . ' ' . $customer->last_name); ?></td></tr>
                <tr><th>טלפון</th><td><a href="tel:<?php echo esc_attr($customer->phone); ?>"><?php echo esc_html($customer->phone); ?></a></td></tr>
                <?php if ($customer->email) : ?><tr><th>אימייל</th><td><?php echo esc_html($customer->email); ?></td></tr><?php endif; ?>
                <?php if ($customer->address) : ?><tr><th>כתובת</th><td><?php echo esc_html($customer->address); ?></td></tr><?php endif; ?>
                <?php if ($customer->notes) : ?><tr><th>הערות</th><td><?php echo nl2br(esc_html($customer->notes)); ?></td></tr><?php endif; ?>
                <tr><th>לקוח מאז</th><td><?php echo date_i18n('d/m/Y', strtotime($customer->created_at)); ?></td></tr>
            </table>
        </div>

        <div class="salon-card salon-customer-stats">
            <h2>סטטיסטיקות</h2>
            <div class="salon-stats-mini">
                <div class="salon-stat-mini">
                    <span class="salon-stat-num"><?php echo $stats['appointments']; ?></span>
                    <span class="salon-stat-lbl">ביקורים</span>
                </div>
                <div class="salon-stat-mini">
                    <span class="salon-stat-num"><?php echo $currency.number_format($stats['total_paid'],0); ?></span>
                    <span class="salon-stat-lbl">שולם</span>
                </div>
                <div class="salon-stat-mini <?php echo $stats['balance'] > 0 ? 'salon-stat-debt' : ''; ?>">
                    <span class="salon-stat-num"><?php echo $currency.number_format(abs($stats['balance']),0); ?></span>
                    <span class="salon-stat-lbl"><?php echo $stats['balance'] > 0 ? 'חוב' : ($stats['balance'] < 0 ? 'זכות' : 'מאוזן'); ?></span>
                </div>
                <?php if ($stats['last_visit']) : ?>
                <div class="salon-stat-mini">
                    <span class="salon-stat-num"><?php echo date_i18n('d/m/Y', strtotime($stats['last_visit'])); ?></span>
                    <span class="salon-stat-lbl">ביקור אחרון</span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($stats['balance'] > 0) : ?>
            <div class="salon-debt-alert">
                <strong>חוב פתוח: <?php echo $currency.number_format($stats['balance'],0); ?></strong>
                <button type="button" class="button button-primary" id="add-payment-btn">הוסף תשלום</button>
            </div>
            <div id="payment-form-inline" style="display:none;margin-top:12px">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('salon_save_payment'); ?>
                    <input type="hidden" name="action" value="salon_save_payment">
                    <input type="hidden" name="customer_id" value="<?php echo $customer->id; ?>">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr(admin_url('admin.php?page=salon-customers&action=view&id='.$customer->id.'&saved=1')); ?>">
                    <div class="salon-inline-form">
                        <input type="number" name="amount" placeholder="סכום" step="0.01" min="0" value="<?php echo $stats['balance']; ?>" class="salon-input-small" required>
                        <select name="payment_method" class="salon-select-small">
                            <option value="cash">מזומן</option>
                            <option value="credit">אשראי</option>
                            <option value="transfer">העברה</option>
                            <option value="bit">ביט</option>
                            <option value="paybox">פייבוקס</option>
                        </select>
                        <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" class="salon-input-small">
                        <button type="submit" class="button button-primary">שמור</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="salon-card">
        <div class="salon-card-header">
            <h2>היסטוריית תורים</h2>
            <a href="<?php echo admin_url('admin.php?page=salon-appointments&action=new'); ?>" class="button">+ תור חדש</a>
        </div>
        <?php if (empty($appointments)) : ?>
            <p class="salon-empty">אין תורים עדיין</p>
        <?php else : ?>
        <table class="salon-table">
            <thead><tr>
                <th>תאריך</th><th>שעה</th><th>שירות</th><th>מחיר</th><th>סטטוס</th><th>הערות</th><th>פעולות</th>
            </tr></thead>
            <tbody>
            <?php foreach ($appointments as $a) : ?>
            <tr class="salon-row-<?php echo esc_attr($a->status); ?>">
                <td><?php echo date_i18n('d/m/Y', strtotime($a->appointment_date)); ?></td>
                <td><?php echo substr($a->appointment_time,0,5); ?></td>
                <td><?php echo esc_html($a->service); ?></td>
                <td><?php echo $currency.number_format($a->price,0); ?></td>
                <td><span class="salon-badge salon-badge-<?php echo esc_attr($a->status); ?>"><?php echo $status_labels[$a->status] ?? $a->status; ?></span></td>
                <td><?php echo esc_html($a->notes); ?></td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=salon-appointments&action=edit&id='.$a->id); ?>" class="button button-small">עריכה</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="salon-card">
        <div class="salon-card-header">
            <h2>תשלומים</h2>
        </div>
        <?php if (empty($payments)) : ?>
            <p class="salon-empty">אין תשלומים עדיין</p>
        <?php else : ?>
        <table class="salon-table">
            <thead><tr>
                <th>תאריך</th><th>שירות</th><th>סכום</th><th>אמצעי תשלום</th><th>הערות</th><th>פעולות</th>
            </tr></thead>
            <tbody>
            <?php foreach ($payments as $p) :
                $method_labels = ['cash'=>'מזומן','credit'=>'אשראי','transfer'=>'העברה','bit'=>'ביט','paybox'=>'פייבוקס'];
            ?>
            <tr>
                <td><?php echo date_i18n('d/m/Y', strtotime($p->payment_date)); ?></td>
                <td><?php echo esc_html($p->service ?? ''); ?></td>
                <td class="salon-amount"><?php echo $currency.number_format($p->amount,0); ?></td>
                <td><?php echo $method_labels[$p->payment_method] ?? $p->payment_method; ?></td>
                <td><?php echo esc_html($p->notes); ?></td>
                <td>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
                        <?php wp_nonce_field('salon_delete_payment'); ?>
                        <input type="hidden" name="action" value="salon_delete_payment">
                        <input type="hidden" name="payment_id" value="<?php echo $p->id; ?>">
                        <button type="submit" class="button button-small button-link-delete" onclick="return confirm('למחוק תשלום זה?')">מחק</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
