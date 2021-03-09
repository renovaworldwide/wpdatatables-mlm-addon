jQuery(document).ready(function ($) {

    if (typeof wpdatatable_config !== 'undefined') {

        wpdatatable_config.setHistoryFilter = function (historyFilter) {
            wpdatatable_config.historyFilter = parseInt(historyFilter);
            jQuery('#wdt-commission-filter-in-form').prop('checked', parseInt(historyFilter));
            // console.log(historyFilter);
        };
        wpdatatable_config.setWeeklyFilter = function (weeklyFilter) {
            wpdatatable_config.weeklyFilter = parseInt(weeklyFilter);
            jQuery('#wdt-weekly-filter-in-form').prop('checked', parseInt(weeklyFilter));
        };

        $('#wdt-commission-filter-in-form').on('change', function (e) {
            wpdatatable_config.setHistoryFilter($(this).is(':checked') ? 1 : 0);
        });
        
        $('#wdt-weekly-filter-in-form').on('change', function (e) {
            wpdatatable_config.setWeeklyFilter($(this).is(':checked') ? 1 : 0);
        });

        if (typeof wpdatatable_init_config !== 'undefined' && wpdatatable_init_config.advanced_settings != '') {
            wpdatatable_config.setHistoryFilter(JSON.parse(wpdatatable_init_config.advanced_settings).historyFilter);
            wpdatatable_config.setWeeklyFilter(JSON.parse(wpdatatable_init_config.advanced_settings).weeklyFilter);
        }
    }

    $(document).on("change","select.period_filter", function () {

        var commission_period_id = $(this).val();
        var wp_table_id = $(this).closest('div.wpdt-c').find('table').attr('id');
        var period = $(this).data("period");
        $.ajax({
            url: mlm_wpdt.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'filter_commission_period_results',
                commission_period_id: commission_period_id,
                period: period,
                wp_table_id: wp_table_id
            },
            cache: false,
            success: function (json_data) {
                $('#' + wp_table_id).DataTable().draw();
            }
        });

    });
})