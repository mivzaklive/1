<?php if ( ! defined( 'ABSPATH' ) ) exit;
$is_edit = ! empty( $customer );
?>
<div class="wrap salon-wrap" dir="rtl">
    <h1 class="salon-page-title">
        <span class="dashicons dashicons-admin-users"></span>
        <?php echo $is_edit ? 'עריכת לקוח' : 'לקוח חדש'; ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=salon-customers'); ?>" class="button salon-back-btn">← חזרה</a>

    <div class="salon-card salon-form-card">
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('salon_save_customer'); ?>
        <input type="hidden" name="action" value="salon_save_customer">
        <input type="hidden" name="customer_id" value="<?php echo $is_edit ? esc_attr($customer->id) : 0; ?>">

        <div class="salon-form-grid">
            <div class="salon-form-group">
                <label>שם פרטי *</label>
                <input type="text" name="first_name" required value="<?php echo $is_edit ? esc_attr($customer->first_name) : ''; ?>" class="regular-text">
            </div>
            <div class="salon-form-group">
                <label>שם משפחה *</label>
                <input type="text" name="last_name" required value="<?php echo $is_edit ? esc_attr($customer->last_name) : ''; ?>" class="regular-text">
            </div>
            <div class="salon-form-group">
                <label>טלפון *</label>
                <input type="tel" name="phone" required value="<?php echo $is_edit ? esc_attr($customer->phone) : ''; ?>" class="regular-text" dir="ltr">
            </div>
            <div class="salon-form-group">
                <label>אימייל</label>
                <input type="email" name="email" value="<?php echo $is_edit ? esc_attr($customer->email) : ''; ?>" class="regular-text" dir="ltr">
            </div>
            <div class="salon-form-group salon-form-full">
                <label>כתובת</label>
                <input type="text" name="address" value="<?php echo $is_edit ? esc_attr($customer->address) : ''; ?>" class="large-text">
            </div>
            <div class="salon-form-group salon-form-full">
                <label>הערות (העדפות, אלרגיות, צבעים מועדפים...)</label>
                <textarea name="notes" rows="4" class="large-text"><?php echo $is_edit ? esc_textarea($customer->notes) : ''; ?></textarea>
            </div>
        </div>

        <div class="salon-form-actions">
            <button type="submit" class="button button-primary button-large">שמור לקוח</button>
            <a href="<?php echo admin_url('admin.php?page=salon-customers'); ?>" class="button button-large">ביטול</a>
            <?php if ($is_edit) : ?>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;float:left">
                <?php wp_nonce_field('salon_delete_customer'); ?>
                <input type="hidden" name="action" value="salon_delete_customer">
                <input type="hidden" name="customer_id" value="<?php echo $customer->id; ?>">
                <button type="submit" class="button button-link-delete" onclick="return confirm('למחוק לקוח זה? כל התורים והתשלומים שלו יימחקו!')">מחק לקוח</button>
            </form>
            <?php endif; ?>
        </div>
    </form>
    </div>
</div>
