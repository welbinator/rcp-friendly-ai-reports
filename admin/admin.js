jQuery(document).ready(function ($) {
    $('#rcp_fai_reports_run_report_button').on('click', function () {
        $.ajax({
            url: rcpFaiReportsAjax.ajaxUrl,
            type: 'post',
            data: {
                action: 'rcp_fai_reports_run_report',
                nonce: rcpFaiReportsAjax.nonce
            },
            beforeSend: function () {
                $('#rcp_fai_reports_run_report_button').prop('disabled', true);
                $('#rcp_fai_reports_result').text('Loading...');
            },
            success: function (response) {
                $('#rcp_fai_reports_result').html(response);
            },
            complete: function () {
                $('#rcp_fai_reports_run_report_button').prop('disabled', false);
            }
        });
    });
});
