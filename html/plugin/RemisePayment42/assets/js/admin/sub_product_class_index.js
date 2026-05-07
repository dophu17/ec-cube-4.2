<script type="text/javascript">
$(function(){
    var colIdx = 0;
    var theadTrCnt = 0;
    var headStr = "";

    // thead
    headStr += '<th class="pt-2 pb-2">{{ 'remise_payment4.ac.admin_product_class.label.thead.th.amount.title'|trans|raw }}</th>';
    headStr += '<th class="pt-2 pb-2">{{ 'remise_payment4.ac.admin_product_class.label.thead.th.actype.title'|trans|raw }}</th>';
    headStr += '<th class="pt-2 pb-2">{{ 'remise_payment4.ac.admin_product_class.label.thead.th.acpoint.title'|trans|raw }}</th>';

    $('#ex-product_class').find('thead').each(function(index, element){
        $(element).find('th').each(function(subIndex, subElement){
            if ($(subElement).text() == "{{ 'admin.product.sale_type'|trans }}") {
                colIdx = subIndex;
                $(subElement).after(headStr);
            }
        });
    });

    // tbody
    {% set tdRowCnt = 0 %}
    {% for product_class_form in form.product_classes %}
        {% set product_class_form = form.product_classes[tdRowCnt] %}
        $('#ex-product_class').find('tr[id^="ex-product_class"]').each(function(index, element){
            if(index == {{ tdRowCnt }})
            {
                $(element).find('td').each(function(subIndex, subElement){
                    if (colIdx == subIndex) {
                        $(subElement).after('<td class="align-middle pr-3">{{ form_widget(product_class_form.remise_payment4_ac_amount) }}{{ form_errors(product_class_form.remise_payment4_ac_amount) }}</td>' +
                                            '<td class="align-middle pr-3">{{ form_widget(product_class_form.remise_payment4_ac_actype_id) }}{{ form_errors(product_class_form.remise_payment4_ac_actype_id) }}</td>' +
                                            '<td class="align-middle pr-3">{{ form_widget(product_class_form.remise_payment4_ac_point_flg) }}{{ form_errors(product_class_form.remise_payment4_ac_point_flg) }}</td>');
                    }
                });
            }
        });

        {% set tdRowCnt = tdRowCnt + 1 %}
    {% endfor %}

});
</script>

