$(function(){

    $('input[name="ac_import[import_count_choice]"]').change(function(){
         checkImportCount();
    });

    checkImportCount();

    // 停止処理
    $('#import_button').on('click', function() {
        var msg = "{{ 'remise_payment4.ac.admin_import_index.label.import_form.submit.confirm_msg'|trans|raw }}";
        if (!confirm(msg)) return false;
        remise_waitscreen();
        $('#import_form').submit();
    });

});

function checkImportCount() {
    if ($('input[name="ac_import[import_count_choice]"]:checked').val() === 'all') {
        $('#ac_import_import_count_start').prop("readonly", true);
        $('#ac_import_import_count_end').prop("readonly", true);
        $('#ac_import_import_count_start').val("1");
        $('#ac_import_import_count_end').val("999999");
    }
    else
    {
        $('#ac_import_import_count_start').prop("readonly", false);
        $('#ac_import_import_count_end').prop("readonly", false);
    }
}
