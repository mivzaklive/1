<?php if ( ! defined( 'ABSPATH' ) ) exit;
$is_edit  = ! empty( $appointment );
$currency = $settings['currency'] ?? '₪';
$services = $settings['services'] ?? [];
?>
<div class="wrap salon-wrap" dir="rtl">
    <h1 class="salon-page-title">
        <span class="dashicons dashicons-calendar-alt"></span>
        <?php echo $is_edit ? 'עריכת תור' : 'תור חדש'; ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=salon-appointments'); ?>" class="button salon-back-btn">← חזרה</a>

    <div class="salon-card salon-form-card">
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="salon-appointment-form">
        <?php wp_nonce_field('salon_save_appointment'); ?>
        <input type="hidden" name="action" value="salon_save_appointment">
        <input type="hidden" name="appointment_id" value="<?php echo $is_edit ? esc_attr($appointment->id) : 0; ?>">

        <div class="salon-form-grid">
            <div class="salon-form-group">
                <label>לקוח *</label>
                <select name="customer_id" required class="salon-select">
                    <option value="">-- בחר לקוח --</option>
                    <?php foreach ($customers as $c) : ?>
                    <option value="<?php echo $c->id; ?>" <?php selected($is_edit && $appointment->customer_id == $c->id); ?>>
                        <?php echo esc_html($c->first_name . ' ' . $c->last_name . ' - ' . $c->phone); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <a href="<?php echo admin_url('admin.php?page=salon-customers&action=new'); ?>" class="salon-link-small">+ לקוח חדש</a>
            </div>

            <div class="salon-form-group">
                <label>שירות *</label>
                <select name="service" required class="salon-select" id="service-select">
                    <option value="">-- בחר שירות --</option>
                    <?php foreach ($services as $s) : ?>
                    <option value="<?php echo esc_attr($s['name']); ?>"
                            data-duration="<?php echo esc_attr($s['duration']); ?>"
                            data-price="<?php echo esc_attr($s['price']); ?>"
                            <?php selected($is_edit && $appointment->service === $s['name']); ?>>
                        <?php echo esc_html($s['name']); ?> (<?php echo $currency.$s['price']; ?>, <?php echo $s['duration']; ?> דק')
                    </option>
                    <?php endforeach; ?>
                    <?php if ($is_edit) : ?>
                    <?php $found = false; foreach($services as $s) if($s['name']===$appointment->service) $found=true; ?>
                    <?php if (!$found) : ?>
                    <option value="<?php echo esc_attr($appointment->service); ?>" selected><?php echo esc_html($appointment->service); ?></option>
                    <?php endif; ?>
                    <?php endif; ?>
                    <option value="__other__">אחר (הזן ידנית)</option>
                </select>
                <input type="text" name="service_custom" id="service-custom" placeholder="שם שירות" style="display:none;margin-top:6px" class="regular-text">
            </div>

            <div class="salon-form-group">
                <label>תאריך *</label>
                <input type="date" name="appointment_date" id="appointment-date" required
                       value="<?php echo $is_edit ? esc_attr($appointment->appointment_date) : date('Y-m-d'); ?>"
                       class="salon-input">
            </div>

            <div class="salon-form-group">
                <label>שעה *</label>
                <select name="appointment_time" id="appointment-time" required class="salon-select">
                    <?php if ($is_edit) : ?>
                    <option value="<?php echo esc_attr(substr($appointment->appointment_time,0,5)); ?>" selected>
                        <?php echo substr($appointment->appointment_time,0,5); ?>
                    </option>
                    <?php else : ?>
                    <option value="">-- בחר תאריך תחילה --</option>
                    <?php endif; ?>
                </select>
                <button type="button" id="load-slots" class="button" style="margin-top:4px">טען שעות פנויות</button>
            </div>

            <div class="salon-form-group">
                <label>משך (דקות)</label>
                <input type="number" name="duration" id="duration" value="<?php echo $is_edit ? esc_attr($appointment->duration) : 60; ?>" min="15" step="15" class="salon-input-small">
            </div>

            <div class="salon-form-group">
                <label>מחיר (<?php echo esc_html($currency); ?>)</label>
                <input type="number" name="price" id="price" value="<?php echo $is_edit ? esc_attr($appointment->price) : ''; ?>" step="0.01" min="0" class="salon-input-small">
            </div>

            <div class="salon-form-group">
                <label>סטטוס</label>
                <select name="status" class="salon-select">
                    <option value="scheduled" <?php selected($is_edit && $appointment->status==='scheduled'); ?>>מתוכנן</option>
                    <option value="completed" <?php selected($is_edit && $appointment->status==='completed'); ?>>בוצע</option>
                    <option value="cancelled" <?php selected($is_edit && $appointment->status==='cancelled'); ?>>בוטל</option>
                    <option value="no_show" <?php selected($is_edit && $appointment->status==='no_show'); ?>>לא הגיע</option>
                </select>
            </div>

            <div class="salon-form-group salon-form-full">
                <label>הערות</label>
                <textarea name="notes" rows="3" class="large-text"><?php echo $is_edit ? esc_textarea($appointment->notes) : ''; ?></textarea>
            </div>
        </div>

        <div class="salon-form-actions">
            <button type="submit" class="button button-primary button-large">שמור תור</button>
            <a href="<?php echo admin_url('admin.php?page=salon-appointments'); ?>" class="button button-large">ביטול</a>
            <?php if ($is_edit) : ?>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;float:left">
                <?php wp_nonce_field('salon_delete_appointment'); ?>
                <input type="hidden" name="action" value="salon_delete_appointment">
                <input type="hidden" name="appointment_id" value="<?php echo $appointment->id; ?>">
                <button type="submit" class="button button-link-delete" onclick="return confirm('למחוק תור זה?')">מחק תור</button>
            </form>
            <?php endif; ?>
        </div>
    </form>
    </div>
</div>
