<script>
    $(function(){
        $('button[type="submit"]').closest('.col-auto').before(
            '<div class="col-auto">'
          + '<a class="btn btn-ec-conversion px-5" href="{{ url('remise_payment4_admin_payment_copy', {id: Payment.id}) }}">'
          + '{{'remise_payment4.admin_payment.button.copy_payment'|trans}}'
          + '</a>'
          + '</div>'
        );
    });
</script>
