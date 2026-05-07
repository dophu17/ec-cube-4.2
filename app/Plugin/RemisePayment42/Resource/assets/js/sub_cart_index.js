<script>
    $(function(){
        $('a.ec-blockBtn--action').each(function(index, element){
            var href = $(element).attr('href');
            {% for remiseAcCartKey in remiseAcCartKeys %}
            if(href == "{{ path('cart_buystep', {'cart_key':remiseAcCartKey}) }}")
            {
                $(element).attr("href","{{path('cart')}}");
            }
            {% endfor %}
        });
    });
</script>
