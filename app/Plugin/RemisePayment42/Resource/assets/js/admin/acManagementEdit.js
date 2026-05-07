$(function(){

    $('input[name="ac_management_edit[limitless]"]').change(function(){
        checkAcTypeEditCount();
    });
    $('#ac_management_edit_stop').change(function(){
        checkAcTypeEditUsage();
    });

    checkAcTypeEditCount();
    checkAcTypeEditUsage();

    if (!$('#acmember_contents').hasClass('show')) {
        document.getElementById('acmember_toggle').click();
    }

    if (!$('#option_contents').hasClass('show')) {
        document.getElementById('option_toggle').click();
    }

    if (!$('#customer_contents').hasClass('show')) {
        document.getElementById('customer_toggle').click();
    }

    if (!$('#order_contents').hasClass('show')) {
        document.getElementById('order_toggle').click();
    }

    if (!$('#shipping_contents').hasClass('show')) {
        document.getElementById('shipping_toggle').click();
    }

    if (!$('#order_history_contents').hasClass('show')) {
        document.getElementById('order_history_toggle').click();
    }

    // 停止処理
    $('#stop_button').on('click', function() {
        if($('input[name="ac_management_edit[stop_option]"]:checked').val() == true){
            var msg = "{{ 'remise_payment4.ac.admin_management_edit.text.submit.stop.confirm_msg1'|trans|raw }}";
        }else{
            var msg = "{{ 'remise_payment4.ac.admin_management_edit.text.submit.stop.confirm_msg2'|trans|raw }}";
        }
        if (!confirm(msg)) return false;
        remise_waitscreen();
        $("#ac_management_edit_mode").val("stop");
        $('form').submit();
    });

    // 再開処理
    $('#restart_button').on('click', function() {
        if($('input[name="ac_management_edit[restart_option]"]:checked').val() == true){
            var msg = "{{ 'remise_payment4.ac.admin_management_edit.text.submit.restart.confirm_msg1'|trans|raw }}";
        }else{
            var msg = "{{ 'remise_payment4.ac.admin_management_edit.text.submit.restart.confirm_msg2'|trans|raw }}";
        }
        if (!confirm(msg)) return false;
        remise_waitscreen();
        $("#ac_management_edit_mode").val("restart");
        $('form').submit();
    });

    // 登録処理
    $('#submit_button').on('click', function() {
        if($('input[name="ac_management_edit[update_option]"]:checked').val() == true){
            var msg = "{{ 'remise_payment4.ac.admin_management_edit.text.submit.confirm_msg1'|trans|raw }}";
        }else{
            var msg = "{{ 'remise_payment4.ac.admin_management_edit.text.submit.confirm_msg2'|trans|raw }}";
        }
        if (!confirm(msg)) return false;
        remise_waitscreen();
        $("#ac_management_edit_mode").val("update");
        $('form').submit();
    });

    //定期購買停止オプションクリック
    $('#detailView_ac_management_edit_stop_option').click(function() {

        // 定期購買停止ルミーズ同期チェックボタン
        if($("#sapn_ac_management_edit_stop_option").css("visibility") == "hidden")
        {
            $("#sapn_ac_management_edit_stop_option").css("visibility","visible");
        }
        else
        {
            $("#sapn_ac_management_edit_stop_option").css("visibility","hidden");
        }

        // 表示修正
        if($("#detailView_ac_management_edit_stop_option").text() == "{{ 'remise_payment4.ac.admin_management_edit.label.block.acmember.stop_option.title1'|trans }}")
        {
            $("#detailView_ac_management_edit_stop_option").text("{{ 'remise_payment4.ac.admin_management_edit.label.block.acmember.stop_option.title2'|trans }}");
        }
        else
        {
            $("#detailView_ac_management_edit_stop_option").text("{{ 'remise_payment4.ac.admin_management_edit.label.block.acmember.stop_option.title1'|trans }}");
        }
    });

    //定期購買再開オプションクリック
    $('#detailView_ac_management_edit_restart_option').click(function() {

        // 定期購買再開ルミーズ同期チェックボタン
        {% if useKeizokuEditExtend %}
        if($("#sapn_ac_management_edit_restart_option").css("visibility") == "hidden")
        {
            $("#sapn_ac_management_edit_restart_option").css("visibility","visible");
        }
        else
        {
            $("#sapn_ac_management_edit_restart_option").css("visibility","hidden");
        }
        {% endif %}

        // 表示修正
        if($("#detailView_ac_management_edit_restart_option").text() == "{{ 'remise_payment4.ac.admin_management_edit.label.block.acmember.restart_option.title1'|trans }}")
        {
            $("#detailView_ac_management_edit_restart_option").text("{{ 'remise_payment4.ac.admin_management_edit.label.block.acmember.restart_option.title2'|trans }}");
        }
        else
        {
            $("#detailView_ac_management_edit_restart_option").text("{{ 'remise_payment4.ac.admin_management_edit.label.block.acmember.restart_option.title1'|trans }}");
        }
    });

    // メモ保存クリック
    $('#note_button').click(function() {
        var msg = "{{ 'remise_payment4.ac.admin_management_edit.text.block.acmember.note.confirm_msg'|trans|raw }}";
        if (!confirm(msg)) return false;
        remise_waitscreen();
        $("#ac_management_edit_mode").val("note");
        $('form').submit();
    });

});

function checkAcTypeEditCount() {
    if ($('input[name="ac_management_edit[limitless]"]:checked').val() === '1') {
        $('#ac_management_edit_count').prop("disabled", true);
    }
    else
    {
        $('#ac_management_edit_count').prop("disabled", false);
    }
}

function checkAcTypeEditUsage() {
    if ($('#ac_management_edit_stop').val() == "{{ 'remise_payment4.ac.plg_remise_payment4_remise_ac_type.stop.usage.key'|trans }}") {
        $('#ac_management_edit_usage_value').prop("disabled", false);
        $('#ac_management_edit_usage_mark').prop("disabled", false);
    }
    else
    {
        $('#ac_management_edit_usage_value').prop("disabled", true);
        $('#ac_management_edit_usage_mark').prop("disabled", true);
    }
}

