<script type="text/javascript">
    $(function(){
        {% for Customer in Customers %}
            $("a[href='{{ url('admin_customer_delete', {'id' : Customer.id}) }}']").attr('href','javascript:remiseNotDeleteAlert()');
        {% endfor %}
    });
    function remiseNotDeleteAlert(){
        alert("{{ 'remise_payment4.ac.admin_customer_index.text.alert_msg'|trans }}");
    }
</script>

