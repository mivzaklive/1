<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap salon-wrap" dir="rtl">
    <h1 class="salon-page-title"><span class="dashicons dashicons-admin-settings"></span> הגדרות</h1>

    <?php if ($msg === 'saved') : ?><div class="notice notice-success is-dismissible"><p>ההגדרות נשמרו בהצלחה</p></div><?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('salon_save_settings'); ?>
        <input type="hidden" name="action" value="salon_save_settings">

        <div class="salon-card">
            <h2>פרטי עסק</h2>
            <table class="form-table">
                <tr>
                    <th>שם העסק</th>
                    <td><input type="text" name="settings[business_name]" value="<?php echo esc_attr($settings['business_name']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>שם הבעלים</th>
                    <td><input type="text" name="settings[owner_name]" value="<?php echo esc_attr($settings['owner_name'] ?? ''); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>טלפון</th>
                    <td><input type="tel" name="settings[phone]" value="<?php echo esc_attr($settings['phone'] ?? ''); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th>סמל מטבע</th>
                    <td><input type="text" name="settings[currency]" value="<?php echo esc_attr($settings['currency'] ?? '₪'); ?>" class="small-text"></td>
                </tr>
            </table>
        </div>

        <div class="salon-card">
            <h2>שעות עבודה</h2>
            <table class="form-table">
                <tr>
                    <th>ימי עבודה (ימי חול)</th>
                    <td>
                        <div class="salon-days-grid">
                        <?php
                        $days = ['sun'=>'ראשון','mon'=>'שני','tue'=>'שלישי','wed'=>'רביעי','thu'=>'חמישי'];
                        foreach ($days as $val => $label) : ?>
                        <label class="salon-day-label">
                            <input type="checkbox" name="settings[working_days][]" value="<?php echo $val; ?>"
                                <?php checked(in_array($val, (array)($settings['working_days']??[]))); ?>>
                            <?php echo $label; ?>
                        </label>
                        <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>שעת התחלה (חול)</th>
                    <td><input type="time" name="settings[weekday_start]" value="<?php echo esc_attr($settings['weekday_start']); ?>"></td>
                </tr>
                <tr>
                    <th>שעת סיום (חול)</th>
                    <td><input type="time" name="settings[weekday_end]" value="<?php echo esc_attr($settings['weekday_end']); ?>"></td>
                </tr>
                <tr>
                    <th>עבודה בשישי</th>
                    <td>
                        <label>
                            <input type="checkbox" name="settings[friday_enabled]" value="1" <?php checked(!empty($settings['friday_enabled'])); ?>>
                            פעיל בשישי
                        </label>
                    </td>
                </tr>
                <tr id="friday-hours" <?php echo empty($settings['friday_enabled']) ? 'style="display:none"' : ''; ?>>
                    <th>שעות שישי</th>
                    <td>
                        <input type="time" name="settings[friday_start]" value="<?php echo esc_attr($settings['friday_start']); ?>">
                        עד
                        <input type="time" name="settings[friday_end]" value="<?php echo esc_attr($settings['friday_end']); ?>">
                    </td>
                </tr>
                <tr>
                    <th>משך תור (דקות)</th>
                    <td>
                        <select name="settings[slot_duration]">
                            <?php foreach ([30,45,60,90,120] as $d) : ?>
                            <option value="<?php echo $d; ?>" <?php selected(($settings['slot_duration']??60)===$d); ?>><?php echo $d; ?> דקות</option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div class="salon-card">
            <h2>שירותים ומחירים</h2>
            <table class="salon-table" id="services-table">
                <thead>
                    <tr><th>שם השירות</th><th>משך (דקות)</th><th>מחיר</th><th></th></tr>
                </thead>
                <tbody id="services-body">
                <?php foreach ($settings['services'] as $i => $s) : ?>
                <tr class="service-row">
                    <td><input type="text" name="settings[services][<?php echo $i; ?>][name]" value="<?php echo esc_attr($s['name']); ?>" class="regular-text" required></td>
                    <td><input type="number" name="settings[services][<?php echo $i; ?>][duration]" value="<?php echo esc_attr($s['duration']); ?>" min="15" step="15" class="salon-input-small"></td>
                    <td><input type="number" name="settings[services][<?php echo $i; ?>][price]" value="<?php echo esc_attr($s['price']); ?>" step="0.01" min="0" class="salon-input-small"></td>
                    <td><button type="button" class="button button-small remove-service">הסר</button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="add-service">+ הוסף שירות</button>
            </p>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-large">שמור הגדרות</button>
        </p>
    </form>
</div>

<script>
jQuery(function($) {
    var idx = <?php echo count($settings['services']); ?>;

    $('#add-service').on('click', function() {
        var row = '<tr class="service-row">' +
            '<td><input type="text" name="settings[services]['+idx+'][name]" class="regular-text" required></td>' +
            '<td><input type="number" name="settings[services]['+idx+'][duration]" value="60" min="15" step="15" class="salon-input-small"></td>' +
            '<td><input type="number" name="settings[services]['+idx+'][price]" value="" step="0.01" min="0" class="salon-input-small"></td>' +
            '<td><button type="button" class="button button-small remove-service">הסר</button></td>' +
            '</tr>';
        $('#services-body').append(row);
        idx++;
    });

    $(document).on('click', '.remove-service', function() {
        $(this).closest('tr').remove();
    });

    $('input[name="settings[friday_enabled]"]').on('change', function() {
        $('#friday-hours').toggle(this.checked);
    });
});
</script>
