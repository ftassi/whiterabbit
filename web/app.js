$(document).ready(function() {
    var tooltip = $('<div/>').qtip({
        id: 'calendar',
        content: {
            text: ' ',
            title: {
                button: true
            }
        },
        position: {
            my: 'bottom right',
            at: 'top center',
            target: 'event',
        },
        show: false,
        hide: false,
        style: 'qtip-blue'
    }).qtip('api');

    $('#calendar').fullCalendar({
        header: {
            right: 'next',
            center: 'title today',
            left: 'prev'
        },
        events: {
            url: '/time',
            data: function(){
                return {
                    user: $("#timesheet_users" ).val()
                }
            }
        },
        eventClick: function(data, event, view) {
            tooltip.set({
                'content.text': data.details,
                'style.classes': 'qtip-blue qtip-my ' + data.className[1]
            })
            .show(event);
         },
        dayClick: function() { tooltip.hide() },
        firstDay: 1,
        editable: true
      }
    );

    $('#timesheet_users').change(function(e){
        var optionSelected = $(this).find("option:selected");
        var textSelected   = optionSelected.text();
        localStorage.setItem('whiterabbit_user_id', optionSelected.val());
        $('#calendar').fullCalendar('refetchEvents');

        $('.showmsg').html("<span class='light'>Showing time for: </span><span class='bold textSelected'>" + textSelected + "</span>");

        getTotals();
    });

    if (localStorage.getItem('whiterabbit_user_id')) {
        $("#timesheet_users" ).val(localStorage.getItem('whiterabbit_user_id'));
        $("#timesheet_users" ).trigger("change");
    }

    function getTotals(){
        var user = $("#timesheet_users" ).val();
        // console.log("getting totals for user " + user);

        var view = $('#calendar').fullCalendar( 'getView' );
        var start = view.start.format('YYYY-MM-DD');
        var end = view.end.format('YYYY-MM-DD');
        var timestamp = new Date().getTime();

        $.get( "totals?user="+user+"&start="+start+"&end="+end+"&_=" + timestamp, function( data ) {
          $('.month-totals .stat-bar__not-billable').css('width', data.percUnbillable + "%")
          $('.month-totals .stat-percentage__billable').css('width', data.percBillable + "%")
          $('.month-totals .stat-percentage__billable').html(data.percBillable + "%")
          $('.month-totals .stat-percentage__not-billable').css('width', data.percUnbillable + "%")
          $('.month-totals .stat-percentage__not-billable').html(data.percUnbillable + "%")
        });

        var year = new Date().getFullYear();
        start = year + '01-01';
        end = year + '12-31'

        // var date1 = new Date();
        // var date2 = new Date(year+"-12-31");
        // var timeDiff = Math.abs(date2.getTime() - date1.getTime());
        // var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24)); // should be only working days?

      $.get( "totals?user="+user+"&start="+start+"&end="+end+"&_=" + timestamp, function( data ) {
        $('.year-totals .stat-bar__not-billable').css('width', data.percUnbillable + "%")
        $('.year-totals .stat-percentage__billable').css('width', data.percBillable + "%")
        $('.year-totals .stat-percentage__billable').html(data.percBillable + "%")
        $('.year-totals .stat-percentage__not-billable').css('width', data.percUnbillable + "%")
        $('.year-totals .stat-percentage__not-billable').html(data.percUnbillable + "%")
        // $('.year-totals .missing-days').html(diffDays);
      });
    }
});
