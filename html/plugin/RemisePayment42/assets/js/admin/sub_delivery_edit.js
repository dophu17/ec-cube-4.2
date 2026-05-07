<script type="text/javascript">
$(function(){
    deliverySaleTypeRemiseAutochargeCheck();
    $("#delivery_sale_type").change(function() {
            deliverySaleTypeRemiseAutochargeCheck();
    });
});

function deliverySaleTypeRemiseAutochargeCheck(){
    $("#remise_sale_type_msg").remove();
    {% for RemiseSaleType in RemiseSaleTypes %}
        if ($("#delivery_sale_type option:selected").val() == "{{ RemiseSaleType.id }}") {
            $("#ex-delivery-payment").first().after("<div class='card-footer' id='remise_sale_type_msg'><span style='color:red;'>※定期購買が有効な支払方法は、クレジットカード決済のみです。</span></div>");
        }
    {% endfor %}
}
</script>

