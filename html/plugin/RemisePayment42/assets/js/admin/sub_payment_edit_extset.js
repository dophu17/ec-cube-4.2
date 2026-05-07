$(function() {
    $('[id^=remise_option_extset_job_]').each(function() {
        $(this).click(function(event) {
            var aid = $(this).prop("id");
            if (aid == "remise_option_extset_job_sales") {
                extsetSales(event);
            } else if (aid == "remise_option_extset_job_change") {
                extsetChange(event);
            } else if (aid == "remise_option_extset_job_return") {
                extsetReturn(event);
            }
        });
    });

    $('#order_OrderStatus').on('change', function() {
        if ($('#order_OrderStatus').val() == "{{ constant('Eccube\\Entity\\Master\\OrderStatus::CANCEL') }}" ||
            $('#order_OrderStatus').val() == "{{ constant('Eccube\\Entity\\Master\\OrderStatus::RETURNED') }}") {
            alert("{{ 'remise_payment4.extset.admin_order_edit.text.order_status.alert'|trans }}");
        }
    });

    $('#order_Payment').on('change', function() {
        if ($('#order_Payment').val() != "{{ RemisePaymentCard.getId }}") {
            alert("{{ 'remise_payment4.extset.admin_order_edit.text.order_payment.alert'|trans }}");
        }
    });
});

function extsetSales(event) {
    if ($('#remise_option_extset_payment_total_change').val() != "0") {
        event.preventDefault();
        alert("{{ 'remise_payment4.extset.admin_order_edit.text.sales.alert'|trans }}");
        return false;
    }
    var msg = "{{ 'remise_payment4.extset.admin_order_edit.text.sales.confirm_msg'|trans }}";
    if (!confirm(msg)) {
        event.preventDefault();
        return false;
    }
    $('#remise_option_extset_job').val("SALES");
    $('form').attr('action', $('#url_remise_payment_extset4_sub_order_edit').val());
    remise_waitscreen();
}
function extsetChange(event) {
    if ($('#remise_option_extset_payment_total_change').val() == "0") {
        event.preventDefault();
        alert("{{ 'remise_payment4.extset.admin_order_edit.text.change.alert'|trans }}");
        return false;
    }
    var msg = "{{ 'remise_payment4.extset.admin_order_edit.text.change.confirm_msg'|trans }}";
    if (!confirm(msg)) {
        event.preventDefault();
        return false;
    }
    $('#remise_option_extset_job').val("CHANGE");
    $('form').attr('action', $('#url_remise_payment_extset4_sub_order_edit').val());
    remise_waitscreen();
}
function extsetReturn(event) {
    var msg = "{{ 'remise_payment4.extset.admin_order_edit.text.cancel.confirm_msg'|trans }}";
    if (!confirm(msg)) {
        event.preventDefault();
        return false;
    }
    $('#remise_option_extset_job').val("RETURN");
    $('form').attr('action', $('#url_remise_payment_extset4_sub_order_edit').val());
    remise_waitscreen();
}
