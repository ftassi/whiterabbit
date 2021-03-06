$(document).ready(function () {

  $.get("buttons", function (data) {
    $("#buttons").html(data);
  });

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
          data: function () {
            return {
              user: $("#timesheet_users .selected").data('userid')
            }
          }
        },
        eventRender: function (event, element, view) {
          var billableHours = parseFloat(event.title.split('||')[0]);
          var unBillableHours = parseFloat(event.title.split('||')[1]);
          var holidayHours = parseFloat(event.title.split('||')[2]);

          var tpl = '<a class="fc-day-grid-event fc-h-event fc-event fc-start fc-end event fc-draggable fc-resizable ' + event.className.join(' ') + '">' + '<div class="fc-content">' + '<span class="fc-title">';

          if (billableHours > 0 ) {
            tpl += billableHours + ' <img src="billable.svg">';
          }

          if (unBillableHours > 0 ) {

            if (billableHours > 0) {
              tpl += '<br>';
            }

            tpl += unBillableHours + ' <img src="not-billable.svg">';
          }

          if (holidayHours > 0 ) {


            if (billableHours > 0 && unBillableHours > 0) {
              tpl += '&nbsp;&nbsp;';
            } else if (billableHours > 0 || unBillableHours > 0) {
              tpl += '<br>';
            }

            tpl += holidayHours + ' <img src="holidays.svg">';
          }

          tpl += '</span>' + '</div>' + '<div class="fc-resizer fc-end-resizer"></div>' + '</a>';

          return $(tpl);
        },



        eventClick: function (data, event, view) {
          console.log();
          tooltip.set({
            'content.text': data.details,
            'style.classes': 'qtip-blue qtip-my ' + data.className[1]
          })
              .show(event);
        },
        dayClick: function () {
          tooltip.hide()
        },
        firstDay: 1,
        editable: true,
        viewRender: function (view, element) {
          getTotals();
        }
      }
  );

  $('#timesheet_users li').click(function () {
    if ($(this).hasClass('selected')) {
      return;
    }
    $('ul#timesheet_users li').removeClass('selected');
    $(this).addClass('selected');

    var userId = $(this).data('userid');
    var textSelected = $(this).text();
    localStorage.setItem('whiterabbit_user_id', userId);
    $('#calendar').fullCalendar('refetchEvents');
    $('.showmsg').html("<span class='light'>Showing time for: </span><span class='bold textSelected'>" + textSelected + "</span>");

    getTotals();
  });

  if (localStorage.getItem('whiterabbit_user_id')) {
    var storedUserId = localStorage.getItem('whiterabbit_user_id')
    $("li[data-userid='" + storedUserId + "']").click();
  }

  function getTotals () {
    var user = $("ul#timesheet_users li.selected").data('userid');

    var year = new Date().getFullYear();
    var start = year + '-01-01';
    var end = year + '-12-31'
    setTotals(user, start, end, '.year-totals');

    var view = $('#calendar').fullCalendar('getView');
    var viewTitleDate = new Date(view.title);
    var firstDay = new Date(viewTitleDate.getFullYear(), viewTitleDate.getMonth(), 1);
    var lastDay = new Date(viewTitleDate.getFullYear(), viewTitleDate.getMonth() + 1, 0);
    start = buildDateString(firstDay);
    end = buildDateString(lastDay);
    setTotals(user, start, end, '.month-totals');

    var curr = new Date();
    firstDay = new Date(curr.setDate(curr.getDate() - curr.getDay() + 1));
    lastDay = new Date(curr.setDate(curr.getDate() - curr.getDay() + 5));
    start = buildDateString(firstDay);
    end = buildDateString(lastDay);
    setTotals(user, start, end, '.week-totals');

    var statbarNumMonth = viewTitleDate.getMonth();
    var statbarYear = viewTitleDate.getFullYear();
    if (statbarNumMonth == 0) { var statbarMonth = "January"}
    if (statbarNumMonth == 1) { var statbarMonth = "February"}
    if (statbarNumMonth == 2) { var statbarMonth = "March"}
    if (statbarNumMonth == 3) { var statbarMonth = "April"}
    if (statbarNumMonth == 4) { var statbarMonth = "May"}
    if (statbarNumMonth == 5) { var statbarMonth = "June"}
    if (statbarNumMonth == 6) { var statbarMonth = "July"}
    if (statbarNumMonth == 7) { var statbarMonth = "August"}
    if (statbarNumMonth == 8) { var statbarMonth = "September"}
    if (statbarNumMonth == 9) { var statbarMonth = "October"}
    if (statbarNumMonth == 10) { var statbarMonth = "November"}
    if (statbarNumMonth == 11) { var statbarMonth = "December"}
    $('.month-totals p').html(statbarMonth + ' ' + statbarYear);
  }

  function setTotals (user, start, end, wrapperSelector) {
    var timestamp = new Date().getTime();
    $.get("totals?user=" + user + "&start=" + start + "&end=" + end + "&_=" + timestamp, function (data) {
      if( data.percUnbillable >= 40 ) {
        $(wrapperSelector + ' .stat-bar__not-billable').attr('class', 'stat-bar__not-billable').addClass('stat-bar--nogood');
      }
      if((data.percUnbillable >= 20) && (data.percUnbillable < 40)) {
        $(wrapperSelector + ' .stat-bar__not-billable').attr('class', 'stat-bar__not-billable').addClass('stat-bar--warning');
      }
      if(data.percUnbillable < 20) {
        $(wrapperSelector + ' .stat-bar__not-billable').attr('class', 'stat-bar__not-billable').addClass('stat-bar--good');
      }

      $(wrapperSelector + ' .stat-bar__not-billable').animate(
          {'width': data.percUnbillable + "%"}
      );
      $(wrapperSelector + ' .stat-percentage__billable').animate(
          {'width': data.percBillable + "%"}
      ).html(data.percBillable + "%");
      $(wrapperSelector + ' .stat-percentage__not-billable').animate(
          {'width': data.percUnbillable + "%"}
      ).html(data.percUnbillable + "%");
    });
  }

  function buildDateString (dateObj) {
    return dateObj.getFullYear() + "-" + ('0' + (dateObj.getMonth() + 1)).slice(-2) + "-" + ('0' + dateObj.getDate()).slice(-2);
  }
});
