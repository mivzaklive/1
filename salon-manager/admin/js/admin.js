jQuery(function($) {
    'use strict';

    /* ---- Appointment form: load available time slots ---- */
    function loadSlots() {
        var date = $('#appointment-date').val();
        if (!date) return;

        var $select = $('#appointment-time');
        var currentVal = $select.val();

        $select.prop('disabled', true).empty().append('<option>טוען...</option>');

        $.post(salonAjax.ajaxurl, {
            action: 'salon_get_slots',
            nonce:  salonAjax.nonce,
            date:   date
        }, function(res) {
            $select.prop('disabled', false).empty();
            if (!res.success || !res.data.length) {
                $select.append('<option value="">אין שעות פנויות ביום זה</option>');
                return;
            }
            $select.append('<option value="">-- בחר שעה --</option>');
            $.each(res.data, function(i, slot) {
                var label = slot.time + (slot.booked ? ' (תפוס)' : '');
                var opt = $('<option>').val(slot.time).text(label);
                if (slot.booked) opt.prop('disabled', true).css('color','#aaa');
                if (slot.time === currentVal) opt.prop('selected', true);
                $select.append(opt);
            });
        }).fail(function() {
            $select.prop('disabled', false).empty().append('<option value="">שגיאה בטעינת שעות</option>');
        });
    }

    $('#load-slots').on('click', loadSlots);
    $('#appointment-date').on('change', loadSlots);

    /* ---- Service select: auto-fill duration and price ---- */
    $('#service-select').on('change', function() {
        var $opt = $(this).find(':selected');
        var name = $opt.val();

        if (name === '__other__') {
            $('#service-custom').show().attr('name', 'service').focus();
            $(this).attr('name', '_service_select_disabled');
        } else {
            $('#service-custom').hide().attr('name', 'service_custom');
            $(this).attr('name', 'service');
        }

        var duration = $opt.data('duration');
        var price    = $opt.data('price');
        if (duration) $('#duration').val(duration);
        if (price !== undefined && price !== '') $('#price').val(price);
    });

    /* ---- Customer view: payment form toggle ---- */
    $('#add-payment-btn').on('click', function() {
        $('#payment-form-inline').slideToggle(200);
    });

    /* ---- Auto-dismiss notices ---- */
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut(600);
    }, 4000);

    /* ---- Confirm delete actions ---- */
    $(document).on('submit', 'form:has([name="action"][value="salon_delete_customer"])', function() {
        return confirm('האם למחוק את הלקוח? כל ההיסטוריה שלו תימחק לצמיתות!');
    });

    /* ---- Phone number formatting (optional cosmetic) ---- */
    $('input[type="tel"]').on('input', function() {
        var val = $(this).val().replace(/[^\d\-+\s]/g, '');
        $(this).val(val);
    });

    /* ---- Settings: Friday hours visibility ---- */
    $('input[name="settings[friday_enabled]"]').on('change', function() {
        $('#friday-hours').toggle(this.checked);
    });
});
