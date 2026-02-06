var graph_height = 242;

var dashboard = jQuery('.unovr_admin_dashboard_wrapper');
function load_dashboard_data() {
    if (!jQuery('#wpnotif_dashboard_view').length) {
        return;
    }
    get_data('monthly_notifications');
    get_data('daily_notifications');
}

function get_data(graph_type) {
    var formData = {};
    formData.action = 'wpnotif_admin_dashboard_stats';
    formData.graph_type = graph_type;
    formData.nonce = wpndashboard.nonce;
    jQuery.ajax({
        type: "POST",
        url: wpndashboard.ajax_url,
        data: formData,
        success: function (res) {
            if (res.success) {
                var data = res.data;

                if (data.type === 'daily_notifications') {
                    var wrapper = dashboard.find('#wpnotif_dashboard_graph_daily_notifications');
                    wrapper.find('.untdovr_admin_dashboard_graph_total_value').text(data.total_data)
                    render_daily_notifications_graph(data.graph);
                } else if (data.type === 'monthly_notifications') {
                    var wrapper = dashboard.find('#wpnotif_dashboard_graph_montly_notifications_stats');
                    wrapper.find('.untdovr_admin_dashboard_graph_total_value').text(data.total_data);
                    render_monthly_notifications_graph(data.graph);

                    var overall_total_messages = data.overall_total_messages;
                    var overall_total_sms = data.overall_total_sms;
                    dashboard.find('.total_notifications_served').text(overall_total_messages);
                    dashboard.find('.total_sms_served').text(overall_total_sms);
                }

            }
        },
        error: function () {
        }
    });
}

function render_daily_notifications_graph(data) {
    var options = {
        series: [{
            name: 'daily_notifications',
            data: data
        }],
        chart: {
            height: graph_height,
            type: 'area',
            background: '#fff',
            zoom: {
                enabled: false,
            },
            toolbar: {
                show: false,
                tools: {
                    download: false,
                }
            },
        },
        colors: ['#21EEAB'],
        grid: {
            show: false,
            padding: {
                left: 0,
                right: 0,
            }
        },
        yaxis: {
            show: false,
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth'
        },
        xaxis: {
            axisBorder: {
                show: false,
            },
            axisTicks: {
                show: false,
            },
            tooltip: {
                enabled: false,
            },
            type: 'datetime',
            labels: {
                datetimeFormatter: {
                    year: '',
                    month: "MMM",
                    day: '',
                    hour: '',
                },
                style: {
                    cssClass: 'untdovr_admin_dashboard_graph_labels',
                },
            }
        },
        tooltip: {
            custom: function (d) {
                var dataPointIndex = d.dataPointIndex;
                var obj = data[dataPointIndex];
                var timestamp = obj[0];
                var number = obj[1];
                var date = new Date(timestamp);
                var options = {month: 'short'};
                var date_str = date.getDate() + ' ' + date.toLocaleDateString("en-us", options);
                var dts = '<span class="x_label">&nbsp;on&nbsp;' + date_str + '</span>';
                return '<div class="untdovr_dashboard_graph_tooltip">' +
                    '<span>' + number + dts + '</span>' +
                    '</div>'
            }

        },
        fill: {
            colors: ['#7d39ff'],
            type: 'gradient',
            gradient: {
                opacityFrom: 0.5,
                opacityTo: 0,
                stops: [0, 100]
            }
        },
    };

    var chart = new ApexCharts(document.querySelector("#wpnotif_dashboard_graph_daily_stats"), options);
    chart.render();
}

function render_monthly_notifications_graph(data) {
    var options = {
        series: [{
            name: "monthly_notifications",
            data: data
        }],
        grid: {
            show: false
        },
        yaxis: {
            show: false,
        },
        chart: {
            type: 'bar',
            height: graph_height,
            background: '#fff',
            zoom: {
                enabled: false,
            },
            toolbar: {
                show: false,
                tools: {
                    download: false,
                }
            },
        },
        fill: {
            type: 'gradient',
            colors: ['rgba(33, 238, 171, 0.7)'],
            gradient: {
                type: "vertical",
                gradientToColors: ['rgba(33, 238, 171, 0.4)'],
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100],
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 8,
                borderRadiusApplication: 'end',
            },
        },
        dataLabels: {
            enabled: false,
        },
        xaxis: {
            type: 'category',
            labels: {
                formatter: function (val) {
                    return val;
                },
                style: {
                    cssClass: 'untdovr_admin_dashboard_graph_labels',
                },
            },
            crosshairs: {
                show: false
            },
            axisBorder: {
                show: false,
            },
            axisTicks: {
                show: false,
            },
        },
        tooltip: {
            custom: function (data) {
                var series = data.series;
                var seriesIndex = data.seriesIndex;
                var dataPointIndex = data.dataPointIndex;
                var w = data.w;
                return '<div class="untdovr_dashboard_graph_tooltip">' +
                    '<span>' + series[seriesIndex][dataPointIndex] + '</span>' +
                    '</div>'
            }

        },
        states: {
            hover: {
                filter: {
                    type: 'none',
                    value: 0,
                }
            },
            active: {
                filter: {
                    type: 'none',
                    value: 0,
                }
            },
        }

    };

    var chart = new ApexCharts(document.querySelector("#wpnotif_dashboard_graph_montly_notifications"), options);
    chart.render();
}

load_dashboard_data();