<script type="text/javascript">
    $(function(){
        $('#customer_form').submit(function(e){
            if($("#admin_customer_status option:selected").val() == 3){
                alert("{{ 'remise_payment4.ac.admin_customer_index.text.alert_msg'|trans }}");
                return false;
            }
        });
    });
</script>
