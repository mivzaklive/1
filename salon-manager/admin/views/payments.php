<?php if ( ! defined( 'ABSPATH' ) ) exit;
$currency = $settings['currency'] ?? '₪';
$method_labels = ['cash'=>'מזומן','credit'=>'אשראי','transfer'=>'העברה','bit'=>'ביט','paybox'=>'פייבוקס'];
?>
<div class="wrap salon-wrap" dir="rtl">
    <h1 class="salon-page-title">
        <span class="dashicons dashicons-money-alt"></span> תשלומים
    </h1>

    <?php if (isset($_GET['saved'])) : ?><div class="notice notice-success is-dismissible"><p>התשלום נשמר</p></div><?php endif; ?>

    <div class="salon-dashboard-grid">

        <div class="salon-card">
            <div class="salon-card-header"><h2>הוסף תשלום</h2></div>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('salon_save_payment'); ?>
                <input type="hidden" name="action" value="salon_save_payment">
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr(admin_url('admin.php?page=salon-payments&saved=1')); ?>">
                <div class="salon-form-grid">
                    <div class="salon-form-group">
                        <label>לקוח *</label>
                        <select name="customer_id" required class="salon-select">
                            <option value="">-- בחר לקוח --</option>
                            <?php foreach ($customers as $c) : ?>
                            <option value="<?php echo $c->id; ?>"><?php echo esc_html($c->first_name . ' ' . $c->last_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="salon-form-group">
                        <label>סכום (<?php echo esc_html($currency); ?>) *</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required class="salon-input-small" placeholder="0">
                    </div>
                    <div class="salon-form-group">
                        <label>אמצעי תשלום</label>
                        <select name="payment_method" class="salon-select">
                            <option value="cash">מזומן</option>
                            <option value="credit">אשראי</option>
                            <option value="transfer">העברה בנקאית</option>
                            <option value="bit">ביט</option>
                            <option value="paybox">פייבוקס</option>
                        </select>
                    </div>
                    <div class="salon-form-group">
                        <label>תאריך</label>
                        <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" class="salon-input">
                    </div>
                    <div class="salon-form-group salon-form-full">
                        <label>הערות</label>
                        <input type="text" name="notes" class="large-text" placeholder="הערה אופציונלית">
                    </div>
                </div>
                <div class="salon-form-actions">
                    <button type="submit" class="button button-primary">שמור תשלום</button>
                </div>
            </form>
        </div>

        <div class="salon-card">
            <div class="salon-card-header"><h2>סיכום חודשי - <?php echo date_i18n('F Y'); ?></h2></div>
            <div class="salon-stats-mini" style="margin-bottom:16px">
                <div class="salon-stat-mini">
                    <span class="salon-stat-num"><?php echo $currency.number_format($revenue,0); ?></span>
                    <span class="salon-stat-lbl">סה"כ הכנסות</span>
                </div>
            </div>
            <?php if (!empty($report)) : ?>
            <table class="salon-table">
                <thead><tr><th>אמצעי תשלום</th><th>עסקאות</th><th>סכום</th></tr></thead>
                <tbody>
                <?php foreach ($report as $r) : ?>
                <tr>
                    <td><?php echo $method_labels[$r->payment_method] ?? $r->payment_method; ?></td>
                    <td><?php echo $r->count; ?></td>
                    <td><?php echo $currency.number_format($r->total,0); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
                <p class="salon-empty">אין תשלומים החודש</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($debtors)) : ?>
    <div class="salon-card salon-card-warning">
        <div class="salon-card-header"><h2>חובות פתוחים</h2></div>
        <table class="salon-table">
            <thead><tr><th>לקוח</th><th>טלפון</th><th>חיובים</th><th>שולם</th><th>חוב</th><th>פעולה</th></tr></thead>
            <tbody>
            <?php foreach ($debtors as $d) : $balance = $d->total_owed - $d->total_paid; ?>
            <tr>
                <td><a href="<?php echo admin_url('admin.php?page=salon-customers&action=view&id='.$d->id); ?>"><?php echo esc_html($d->customer_name); ?></a></td>
                <td><a href="tel:<?php echo esc_attr($d->phone); ?>"><?php echo esc_html($d->phone); ?></a></td>
                <td><?php echo $currency.number_format($d->total_owed,0); ?></td>
                <td><?php echo $currency.number_format($d->total_paid,0); ?></td>
                <td class="salon-debt"><strong><?php echo $currency.number_format($balance,0); ?></strong></td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=salon-customers&action=view&id='.$d->id); ?>" class="button button-small button-primary">גבה</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="salon-card">
        <div class="salon-card-header"><h2>תשלומים אחרונים</h2></div>
        <?php if (empty($payments)) : ?>
            <p class="salon-empty">אין תשלומים עדיין</p>
        <?php else : ?>
        <table class="salon-table">
            <thead><tr><th>תאריך</th><th>לקוח</th><th>סכום</th><th>אמצעי</th><th>הערות</th><th>פעולות</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p) : ?>
            <tr>
                <td><?php echo date_i18n('d/m/Y', strtotime($p->payment_date)); ?></td>
                <td><a href="<?php echo admin_url('admin.php?page=salon-customers&action=view&id='.$p->customer_id); ?>"><?php echo esc_html($p->customer_name); ?></a></td>
                <td class="salon-amount"><?php echo $currency.number_format($p->amount,0); ?></td>
                <td><?php echo $method_labels[$p->payment_method] ?? $p->payment_method; ?></td>
                <td><?php echo esc_html($p->notes); ?></td>
                <td>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
                        <?php wp_nonce_field('salon_delete_payment'); ?>
                        <input type="hidden" name="action" value="salon_delete_payment">
                        <input type="hidden" name="payment_id" value="<?php echo $p->id; ?>">
                        <button type="submit" class="button button-small button-link-delete" onclick="return confirm('למחוק?')">מחק</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
