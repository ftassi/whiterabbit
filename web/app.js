$(document).ready(function() {

    var user = getUserIdFromUrl();
    if (user) {
        $('#timesheet_users').val(user);
        $('#calendar').fullCalendar('refetchEvents');
        var textSelected   = $('#timesheet_users option:selected').text();
        $('.showmsg').text("Showing time for " + textSelected);
    }

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
            },
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
    });

    $('#timesheet_users').change(function(e){
        var optionSelected = $(this).find("option:selected");
        var textSelected   = optionSelected.text();

        $('#calendar').fullCalendar('refetchEvents');

        $('.showmsg').text("Showing time for " + textSelected);
    });

});

function getUserIdFromUrl() {
  var pieces = [];
  window.location.search
    .substring(1)
    .split('&')
    .map(function(p) {
      return p.split('=')
    })
    .forEach(function(tuple) {
      pieces[tuple[0]] = tuple[1];
    })

  return pieces['user'];
}


