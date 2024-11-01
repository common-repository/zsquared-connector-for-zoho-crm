<script>
    jQuery(function($) {
        function zsq_crm_manualSyncPage(page) {
            var from = (page*100)-99;
            var to = page*100;
            $("#zsq_crm_manual_sync_message").text("Syncing "+from+" to "+to+" products, please wait...");
            $.ajax({
                url: ajaxurl,
                data: {
                    'action': 'zsq_crm_manual_sync',
                    'page': page
                },
                success: function(data) {
                    if(data === 'more') {
                        page++;
                        zsq_crm_manualSyncPage(page);
                    }
                    else {
                        $("#zsq_crm_manual_sync_message").text(data);
                        setTimeout(function(){
                            $("#zsq_crm_manual_sync_message").text("");
                        }, 10000);
                    }
                },
                error: function(error) {
                    $("#zsq_crm_manual_sync_message").text("ERROR: "+error);
                }
            });
        }

        $(document).ready(function(){
            $('#zsq_crm_manual_sync').on('click', function(event){
                event.preventDefault();
                zsq_crm_manualSyncPage(1);
            });

            $('.zspl-nav').on('click', function(event){
                event.preventDefault();
            });
        });
    });

    function zsq_switch_tabs(tabname) {
        event.preventDefault();
        jQuery('.zspl-tab').hide();
        jQuery('.zspl-nav').removeClass('active');
        jQuery('#zspl-tab-' + tabname).show();
        jQuery('#zspl-nav-' + tabname).addClass('active');
        const url = new URL(window.location.href);
        url.searchParams.delete('show');
        url.searchParams.append('show', tabname);
        window.history.replaceState({}, jQuery('title').text(), url.href);
    }
</script>
