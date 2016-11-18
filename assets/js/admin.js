jQuery(document).ready(function($){
    $(document).on('click', 'td.order_actions .button.wctc_tracking', function(e){
        e.preventDefault();
        var codice = prompt(admin_ajax_script.prompt_text);

        if (codice === "") {
            // user pressed OK, but the input field was empty
        } else if (codice) {
            var tr_id = $(this).closest('tr').attr('id');
            var order_id = tr_id.substring(tr_id.indexOf('-')+1);
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: admin_ajax_script.ajax_url,
                data: {action: 'wc_add_tracking', order_id: order_id, wctc_tracking: codice},
                success: function(response){
                    if(response.success){
                        location.reload();
                    }
                }
            });
        } else {
            // user hit cancel
        }
    })
});