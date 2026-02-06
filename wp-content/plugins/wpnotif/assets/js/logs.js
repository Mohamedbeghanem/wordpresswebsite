jQuery(function () {

    var data_table = jQuery('#wpnotif_message_logs');
    data_table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            "url": wpnmeslog.ajax_url,
            "data": function (d) {
                d.action = 'wpnotif_message_log_data';
                d.nonce = data_table.data('nonce');

            },
            "type": "POST"
        },
        order: [[1, "ASC"]],
        pageLength: 15,
        searching: false,
        lengthChange: false,
        ordering: false,
        columns: [
            {data: 'date_time'},
            {data: 'to'},
            {data: 'route'},
            {data: 'plugin'},
            {data: 'user_type'},
            {data: 'action'},
            {data: 'content'},
        ],
        language: {
            paginate: {
                next: '<span class="wpnotif-log-arrow wpnotif-log-arrow_right"></span>',
                previous: '<span class="wpnotif-log-arrow wpnotif-log-arrow_left"></i>'
            }
        }
    });

});