{% if PayquickInfo is not defined or not PayquickInfo or PayquickInfo.use_payquick != "1" %}
<script>
    $(function(){
        $('.ec-progress__item').last().before(
            '<li class="ec-progress__item">'
          + '<div class="ec-progress__number">{% if is_granted('ROLE_USER') == false %}5{% else %}4{% endif %}</div>'
          + '<div class="ec-progress__label">{{ 'remise_payment4.front.payment.label.flow'|trans }}</div>'
          + '</li>'
        );

        $('.ec-progress__number').last().text('{% if is_granted('ROLE_USER') == false %}6{% else %}5{% endif %}');
    });
</script>
{% endif %}
