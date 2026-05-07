$(function(){

    $('input[name="ac_type_edit[limitless]"]').change(function(){
        checkAcTypeEditCount();
    });
    $('#ac_type_edit_interval_mark').change(function(){
        checkAcTypeEditDayOfMonth();
    });
    $('#ac_type_edit_stop').change(function(){
        checkAcTypeEditUsage();
        checkAcTypeEditStopDisplay();
    });

    checkAcTypeEditCount();
    checkAcTypeEditDayOfMonth();
    checkAcTypeEditUsage();
    checkAcTypeEditStopDisplay();

});

function checkAcTypeEditCount() {
    if ($('input[name="ac_type_edit[limitless]"]:checked').val() === '1') {
        $('#ac_type_edit_count').prop("disabled", true);
    } else {
        $('#ac_type_edit_count').prop("disabled", false);
    }
}

function checkAcTypeEditDayOfMonth() {
    if ($('#ac_type_edit_interval_mark').val() == "{{ 'remise_payment4.ac.plg_remise_payment4_remise_ac_type.interval_marks.m.key'|trans }}") {
        $('#ac_type_edit_day_of_month').prop("disabled", false);
    } else {
        $('#ac_type_edit_day_of_month').prop("disabled", true);
    }
}

function checkAcTypeEditUsage() {
    if ($('#ac_type_edit_stop').val() == "{{ 'remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.usage.key'|trans }}") {
        $('#ac_type_edit_usage_value').prop("disabled", false);
        $('#ac_type_edit_usage_mark').prop("disabled", false);
    } else {
        $('#ac_type_edit_usage_value').prop("disabled", true);
        $('#ac_type_edit_usage_mark').prop("disabled", true);
    }
}

function simulation() {
    var fd = new FormData($('form').get(0));
    $('#mode').val("simulation");
    $('form').submit();
}

function checkAcTypeEditStopDisplay() {
    if ($('#ac_type_edit_stop').val() == "{{ 'remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.false.key'|trans }}") {
        $('#ac_type_edit_stop_display').prop("disabled", false);
        if ($('#ac_type_edit_stop_display').val().length === 0) {
            $('#ac_type_edit_stop_display').val("{{ 'remise_payment4.ac.front.shopping.acinfo.stop.false.value'|trans }}");
        }
    } else {
        $('#ac_type_edit_stop_display').prop("disabled", true);
    }
}
