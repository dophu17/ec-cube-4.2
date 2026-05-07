{% if BaseInfo.option_mypage_order_status_display %}
<script>
    $(function(){
        // search status
        $('div.ec-orderOrder > div.ec-definitions > dt').each(function(index, element){
            if ($(element).text() == "{{ 'front.mypage.order_status'|trans }}") {
                $(element).closest('div').find('dd').html(
                    "<span class=\"text-danger\">{{ 'remise_payment4.common.text.card.state.result.customer'|trans }}</span>"
                );
            }
        });
    });
</script>
{% endif %}
