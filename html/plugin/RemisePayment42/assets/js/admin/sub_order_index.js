<script>
    $(function(){
        var orderNoIdx = 0;
        var statusIdx = 0;
        $('#search_result').find('thead > tr').each(function(index, element){
            $(element).find('th').each(function(subIndex, subElement){
                if ($(subElement).text() == "{{ 'admin.order.orderer'|trans }}") {
                    orderNoIdx = subIndex;
                }
                else if ($(subElement).text() == "{{ 'admin.order.order_status'|trans }}") {
                    statusIdx = subIndex;
                }
            });
        });

        $('#search_result').find('tbody > tr').each(function(index, element){
            // search order_no
            $(element).find('td').each(function(subIndex, subElement){
                // find order_no label
                if (orderNoIdx == subIndex) {
                    {% for OrderResultCard in NotCompletedOrderResultCards %}
                    // find order_no
                    var str = $(subElement).find('a.action-edit').first().html();
                    var idx = str.indexOf("<br");
                    if (str.substr(0, idx) == "{{ orderNos[OrderResultCard.getId] }}") {
                        // search status
                        $(element).find('td').each(function(subIndex2, subElement2){
                            // find status label
                            if (statusIdx == subIndex2) {
                                $(subElement2).find('span').first().html(
                                    $(subElement2).find('span').first().html()
                                    + "<br/>(<span style=\"color:#fe0000;\">{{ 'remise_payment4.common.text.card.state.result'|trans }}</span>)"
                                );
                            }
                        });
                    }
                    {% endfor %}
                    {% for OrderResultCard in AcFailedOrderResultCards %}
                    // find order_no
                    var str = $(subElement).find('a.action-edit').first().html();
                    var idx = str.indexOf("<br");
                    if (str.substr(0, idx) == "{{ orderNos[OrderResultCard.getId] }}") {
                        // search status
                        $(element).find('td').each(function(subIndex2, subElement2){
                            // find status label
                            if (statusIdx == subIndex2) {
                                $(subElement2).find('span').first().html(
                                    $(subElement2).find('span').first().html()
                                    + "<br/>(<span style=\"color:#fe0000;\">{{ 'remise_payment4.common.text.card.state.result.ac.failed'|trans }}</span>)"
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
