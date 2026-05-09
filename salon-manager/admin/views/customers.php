<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap salon-wrap" dir="rtl">
    <h1 class="salon-page-title">
        <span class="dashicons dashicons-groups"></span> אלפון לקוחות
        <a href="<?php echo admin_url('admin.php?page=salon-customers&action=new'); ?>" class="page-title-action">+ לקוח חדש</a>
    </h1>

    <?php if (isset($_GET['deleted'])) : ?><div class="notice notice-success is-dismissible"><p>הלקוח נמחק</p></div><?php endif; ?>

    <div class="salon-search-bar">
        <form method="get">
            <input type="hidden" name="page" value="salon-customers">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="חיפוש לפי שם או טלפון..." class="salon-search-input">
            <button type="submit" class="button">חפש</button>
            <?php if ($search) : ?><a href="<?php echo admin_url('admin.php?page=salon-customers'); ?>" class="button">נקה</a><?php endif; ?>
        </form>
    </div>

    <?php if ( empty($customers) ) : ?>
        <div class="salon-card"><p class="salon-empty"><?php echo $search ? 'לא נמצאו תוצאות' : 'אין לקוחות עדיין'; ?></p></div>
    <?php else : ?>
    <div class="salon-card">
        <table class="salon-table">
            <thead><tr>
                <th>שם</th><th>טלפון</th><th>אימייל</th><th>הערות</th><th>פעולות</th>
            </tr></thead>
            <tbody>
            <?php foreach ($customers as $c) : ?>
            <tr>
                <td>
                    <strong>
                        <a href="<?php echo admin_url('admin.php?page=salon-customers&action=view&id='.$c->id); ?>">
                            <?php echo esc_html($c->first_name . ' ' . $c->last_name); ?>
                        </a>
                    </strong>
                </td>
                <td><a href="tel:<?php echo esc_attr($c->phone); ?>"><?php echo esc_html($c->phone); ?></a></td>
                <td><?php echo esc_html($c->email); ?></td>
                <td class="salon-notes-preview"><?php echo esc_html(mb_substr($c->notes,0,50)); ?><?php echo mb_strlen($c->notes)>50?'...':''; ?></td>
                <td class="salon-actions">
                    <a href="<?php echo admin_url('admin.php?page=salon-customers&action=view&id='.$c->id); ?>" class="button button-small">צפייה</a>
                    <a href="<?php echo admin_url('admin.php?page=salon-customers&action=edit&id='.$c->id); ?>" class="button button-small">עריכה</a>
                    <a href="<?php echo admin_url('admin.php?page=salon-appointments&action=new&customer_id='.$c->id); ?>" class="button button-small button-primary">+ תור</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
