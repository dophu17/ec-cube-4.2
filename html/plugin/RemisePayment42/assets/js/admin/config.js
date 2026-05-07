$(function(){
    $('input[name="config[use_payment][]"]').change(function(){
        if ($(this).attr('id') == 'config_use_payment_0') {
            checkUsePaymentCard();
        }
        else if ($(this).attr('id') == 'config_use_payment_1') {
            checkUsePaymentCvs();
        }
    });
    $('input[name="config[use_option][]"]').change(function(){
        if ($(this).attr('id') == 'config_use_option_0') {
            checkUseOptionExtset();
        }
        if ($(this).attr('id') == 'config_use_option_1') {
            checkUseOptionAc();
        }
    });
    $('input[name="config[payquick_flag]"]').change(function(){
        checkPayquickFlag();
    });
    $('input[name="config[use_method][]"]').click(function(){
        checkUseMethod();
    });
    $('input[name="config[ac_acquisition_method]"]').change(function(){
        checkAcAcquisitionMethod();
   });

    checkUsePaymentCard();
    checkUsePaymentCvs();
    checkUseOptionExtset();
    checkUseOptionAc();
    checkPayquickFlag();
    checkUseMethod();
    checkAcAcquisitionMethod();

    $('#config_ac_password').attr('type','password');
});

function checkUsePaymentCard() {
    if ($('#config_use_payment_0').prop('checked')) {
        if (!$('#card_setting_contents').hasClass('show')) {
            document.getElementById('card_setting_toggle').click();
        }
    } else {
        if ($('#card_setting_contents').hasClass('show')) {
            document.getElementById('card_setting_toggle').click();
        }
    }
}

function checkUsePaymentCvs() {
    if ($('#config_use_payment_1').prop('checked')) {
        if (!$('#cvs_setting_contents').hasClass('show')) {
            document.getElementById('cvs_setting_toggle').click();
        }
    } else {
        if ($('#cvs_setting_contents').hasClass('show')) {
            document.getElementById('cvs_setting_toggle').click();
        }
    }
}

function checkUseOptionExtset() {
    if ($('#config_use_option_0').prop('checked')) {
        if (!$('#extset_setting_contents').hasClass('show')) {
            document.getElementById('extset_setting_toggle').click();
        }
    } else {
        if ($('#extset_setting_contents').hasClass('show')) {
            document.getElementById('extset_setting_toggle').click();
        }
    }
}

function checkUseOptionAc() {
    if ($('#config_use_option_1').prop('checked')) {
        if (!$('#ac_setting_contents').hasClass('show')) {
            document.getElementById('ac_setting_toggle').click();
        }
    } else {
        if ($('#ac_setting_contents').hasClass('show')) {
            document.getElementById('ac_setting_toggle').click();
        }
    }
}

function checkPayquickFlag() {
    if ($('#config_payquick_flag_0').prop('checked')) {
        $('#config_use_method_area').show('normal');
        checkUseMethod();
    } else {
        $('#config_use_method_area').hide('normal');
        $('#config_ptimes_area').hide('normal');
    }
}

function checkUseMethod() {
    if ($('#config_payquick_flag_0').prop('checked')) {
        var isPtimes = false;
        $('input[name="config[use_method][]"]').each(function(){
            if ($(this).attr('id') == 'config_use_method_1' && $(this).prop('checked')) {
                isPtimes = true;
            }
        });
        if (isPtimes) {
            $('#config_ptimes_area').show('normal');
            defaultPtimes();
        } else {
            $('#config_ptimes_area').hide('normal');
        }
    } else {
        $('#config_ptimes_area').hide('normal');
    }
}

function defaultPtimes() {
    {% if not form_errors(form.ptimes) %}
    if ($('input[name="config[ptimes][]"]').length == 1) {
        $('input[name="config[ptimes][]"]').each(function(){$(this).prop('checked', true)});
    }
    {% endif %}
}

function checkAcAcquisitionMethod() {
    if ($('input[name="config[ac_acquisition_method]"]:checked').val() === 'real') {
        $('#config_ac_max_number_acquisitions').prop("disabled", true);
    }
    else
    {
        $('#config_ac_max_number_acquisitions').prop("disabled", false);
    }
}
