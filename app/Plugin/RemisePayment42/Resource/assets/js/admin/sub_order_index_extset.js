<script>
    $(function(){
        var statusIdx = 0;

        // thead
        $('#search_result').find('thead > tr').each(function(index, element){
            $(element).find('th').each(function(subIndex, subElement){
                if ($(subElement).text() == "{{ 'admin.order.order_status'|trans }}") {
                    statusIdx = subIndex;
                    $(subElement).after('<th class="border-top-0 pt-2 pb-2 text-center"><input type="checkbox" id="remise_payment_extset4_toggle_check_all" name="filter" value="open">&nbsp;ルミーズカード決済状況</th>');
                }
            });
        });

        // tbody
        $('#search_result').find('tbody > tr').each(function(index, element){
            var str = $(element).find('a.action-edit').first().html();
            if(str)
            {
                var idx = str.indexOf("<br");
                var isMatch = false;
                $(element).find('td').each(function(subIndex, subElement){
                    if (statusIdx == subIndex) {
                        {% for ExtsetOrderResultCard in ExtsetOrderResultCards %}
                            if (str.substr(0, idx) == "{{ orderNos[ExtsetOrderResultCard.getId] }}") {
                                isMatch = true;
                                {% if ExtsetOrderResultCard.getJob == "CAPTURE" %}
                                    {% if ExtsetOrderResultCard.getState == 'remise_payment4.common.label.card.state.result.ac.failed'|trans %}
                                        $(subElement).after('<td class="align-middle text-center">'+"{{ 'remise_payment4.common.text.card.state.result.ac.failed'|trans }}"+'</td>');
                                    {% else %}
                                        $(subElement).after('<td class="align-middle text-center">'+"{{ 'remise_payment4.admin_order_edit.label.card.job.capture'|trans }}"+'</td>');
                                    {% endif %}
                                {% elseif ExtsetOrderResultCard.getJob == "AUTH" %}
                                    {% if ExtsetOrderResultCard.getState == 'remise_payment4.common.label.card.state.result'|trans
                                        or ExtsetOrderResultCardsOrderState[ExtsetOrderResultCard.getId] == constant('Eccube\\Entity\\Master\\OrderStatus::CANCEL')
                                        or ExtsetOrderResultCardsOrderState[ExtsetOrderResultCard.getId] == constant('Eccube\\Entity\\Master\\OrderStatus::RETURNED')
                                        or ExtsetOrderResultCardsOrderState[ExtsetOrderResultCard.getId] == constant('Eccube\\Entity\\Master\\OrderStatus::PENDING')
                                        or ExtsetOrderResultCardsOrderState[ExtsetOrderResultCard.getId] == constant('Eccube\\Entity\\Master\\OrderStatus::PROCESSING')
                                        %}
                                        $(subElement).after('<td class="align-middle text-center">'+"{{ 'remise_payment4.admin_order_edit.label.card.job.auth'|trans }}"+'</td>');
                                    {% else %}
                                        $(subElement).after('<td class="align-middle text-center">'
                                                +"{{ 'remise_payment4.admin_order_edit.label.card.job.auth'|trans }}"
                                                +'<br><input type="checkbox" id="remise_payment_extset4_check_{{ ExtsetOrderResultCard.getId }}" name="remise_payment_extset4_check[]" value="{{ ExtsetOrderResultCard.getId }}">'
                                                +'売上を行う</td>');
                                    {% endif %}
                                {% elseif ExtsetOrderResultCard.getJob == "SALES" %}
                                    $(subElement).after('<td class="align-middle text-center">'+"{{ 'remise_payment4.admin_order_edit.label.card.job.sales'|trans }}"+'</td>');
                                {% elseif ExtsetOrderResultCard.getJob == "VOID" %}
                                    $(subElement).after('<td class="align-middle text-center">'+"{{ 'remise_payment4.admin_order_edit.label.card.job.void'|trans }}"+'</td>');
                                {% elseif ExtsetOrderResultCard.getJob == "RETURN" %}
                                    $(subElement).after('<td class="align-middle text-center">'+"{{ 'remise_payment4.admin_order_edit.label.card.job.return'|trans }}"+'</td>');
                                {% else %}
                                    $(subElement).after('<td class="align-middle text-center">'+"{{ 'remise_payment4.admin_order_edit.label.card.job.other'|trans }}"+'</td>');
                                {% endif %}
                            }
                        {% endfor %}
                        if (!isMatch)
                        {
                            $(subElement).after('<td class="align-middle text-center">'+"{{ 'remise_payment4.admin_order_edit.label.card.job.other'|trans }}"+'</td>');
                        }
                    }
                });
            }
        });

        // ルミーズカード決済状況チェックボックス
        $('#remise_payment_extset4_toggle_check_all').on('change', function() {
            var checked = $(this).prop('checked');
            if (checked) {
                $('input[id^="remise_payment_extset4_check_"]').prop('checked', true);
            } else {
                $('input[id^="remise_payment_extset4_check_"]').prop('checked', false);
            }
            if(remisePaymentExtset4CheckCount())
            {
                $("#remise_payment_extset4_button_area").css("visibility","visible");
            }else{
                $("#remise_payment_extset4_button_area").css("visibility","hidden");
            }
        });
        $('input[id^="remise_payment_extset4_check_"]').on('change', function() {
            $('#remise_payment_extset4_toggle_check_all').prop('checked', false);
            if(remisePaymentExtset4CheckCount())
            {
                $("#remise_payment_extset4_button_area").css("visibility","visible");
            }else{
                $("#remise_payment_extset4_button_area").css("visibility","hidden");
            }
        });

        // 一括売上処理
        $('#remise_payment_extset4_select_sales').on('click', function() {
            var chkCount = 0;
            var orderIds = '';
            $('input[id^="remise_payment_extset4_check_"]').each(function() {
                if ($(this).prop("checked") == true) {
                    chkCount++;
                    if (orderIds.length != 0) orderIds += ",";
                    orderIds += $(this).val();
                }
            });
            if (chkCount == 0) {
                alert("{{ 'remise_payment4.extset.admin_order_index.text.remise_payment_extset4_select_sales.nocheck'|trans }}");
                return false;
            }

            var msg = "{{ 'remise_payment4.extset.admin_order_index.text.remise_payment_extset4_select_sales.confirm_msg1'|trans }}" + chkCount + "{{ 'remise_payment4.extset.admin_order_index.text.remise_payment_extset4_select_sales.confirm_msg2'|trans }}";
            if (!confirm(msg)) return false;

            remise_waitscreen();
            $('#remise_option_extset_order_ids').val(orderIds);
            $('#search_form').attr('action', "{{url('remise_payment_extset4_sub_order_sales')}}");
            $('#search_form').submit();
        });
    });

    function remisePaymentExtset4CheckCount()
    {
        var chkCount = 0;
        var orderIds = '';
        $('input[id^="remise_payment_extset4_check_"]').each(function() {
            if ($(this).prop("checked") == true) {
                chkCount++;
                if (orderIds.length != 0) orderIds += ",";
                orderIds += $(this).val();
            }
        });
        if (chkCount == 0) {
            return false;
        }
        else
        {
            return true;
        }
    }
</script>
