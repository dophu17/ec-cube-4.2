{% if (BaseInfo.option_mypage_order_status_display) %}
<script>
    $(function(){
        $('.ec-historyListHeader').each(function(index, element){
            // search order_no
            $(element).find('dl.ec-definitions > dt').each(function(subIndex, subElement){
                // find order_no label
                if ($(subElement).text() == "{{ 'front.mypage.order_no'|trans }}") {
                    {% for OrderResultCard in NotCompletedOrderResultCards %}
                    // find order_no
                    if ($(subElement).closest('dl').find('dd').first().text() == "{{ OrderResultCard.getId }}") {
                        // search status
                        $(element).find('dl.ec-definitions > dt').each(function(subIndex2, subElement2){
                            // find status label
                            if ($(subElement2).text() == "{{ 'front.mypage.order_status'|trans }}") {
                                $(subElement2).closest('dl').find('dd').html(
                                    "<span class=\"text-danger\">{{ 'remise_payment4.common.text.card.state.result.customer'|trans }}</span>"
                                );
                            }
                        });
                    }
                    {% endfor %}
                }
            });
        });
    });
</script>
{% endif %}
