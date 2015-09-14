// client-side file to generate Hours table w/ forward/backward capabilities,
// as well as the Today's Hours widget and footer table of the week's hours.
// (see http://trexler.muhlenberg.edu/about/hours.html)
// requires jQuery + uses Bootstrap 2.3.2 classes

$.get('http://libapp.muhlenberg.edu/hours/year')
.done(function ( data ) {
  // fixes _old_ firefox bug (tested v12.0)
  if ( typeof data === 'string' ) { data = JSON.parse(data) }

  var $today_widget = $('#todaysHours')
    , $footer_table_holder = $('#hoursTable')
    , $hours_page_widget = $('#hoursWidget')
    , todays_index = get_todays_index(data['LibraryHours'])
    , today = data['LibraryHours'][todays_index]
    , i = 0, $tr, $footer_table = $('<table class="table nolines"/>')
      ;

  // firstly, the widget at the top of the page
  $today_widget.html( today.display )
  
  // secondly, the table on the footer
  for ( ; i < 7; i++ ) {
    $tr = $('<tr/>');

    if ( i === todays_index ) { $tr.addClass('highlight'); }
    $tr.html(
      '<td>' + data['LibraryHours'][i].day + '</td>'
    + '<td>' + data['LibraryHours'][i].display + '</td>'
    );

    $footer_table.append($tr);
  }

  $footer_table_holder.append($footer_table);

  // finally, where applicable, build the table widget
  if ( $hours_page_widget.length !== 0 ) {
    var $back = $('<a/>')
      , $frwd = $('<a/>')

      , counter = 0, i = 0
      ;

    $back.attr({ 'id': 'back', 'href': '#' });
    $back.addClass('pull-left');
    $back.css('visibility', 'hidden');
    $back.html('Previous Week');

    $frwd.attr({ 'id': 'back', 'href': '#' });
    $frwd.addClass('pull-right');
    $frwd.html('Next Week');

    buildTable($hours_page_widget, data['LibraryHours'], counter);
    $hours_page_widget.append($back);
    $hours_page_widget.append($frwd);

    $back.on('click', function(e) {
      e.preventDefault();
      counter = counter - 7;
      buildTable($hours_page_widget, data['LibraryHours'], counter);
      $back.css('visibility', (counter <= 0 ? 'hidden' : 'visible'));
      $frwd.css('visibility', (counter >= data['LibraryHours'].length - 7 ? 'hidden' : 'visible'));
    });

    $frwd.on('click', function(e) {
      e.preventDefault();
      counter = counter + 7;
      buildTable($hours_page_widget, data['LibraryHours'], counter);
      $back.css('visibility', (counter <= 0 ? 'hidden' : 'visible'));
      $frwd.css('visibility', (counter >= data['LibraryHours'].length - 7 ? 'hidden' : 'visible'));
    });
  }

});

function buildTable($el, data, counter) {
  var className = className || ''
    , counter = counter || 0
    , i = 0
    , count = data.length
    , todays_index = get_todays_index( data )
    , $table = $('<table/>')
    ;

  if ( typeof $el === 'string' ) { $el = $($el); }

  if ( $el.length === 0 ) { return; }

  $table.addClass('table table-bordered');

  // clear out table
  $el.children('table').each(function(idx, el) { $(el).remove(); });

  // + build it back out
  for ( ; i < 7; i++ ) {
    var internal = i + counter
      , entry = data[internal]
      , name = entry.day
      , hours = entry.display
      , $tr = $('<tr/>')
      , is_today
      ;

    if ( internal === todays_index ) { $tr.addClass('highlight'); }
    $tr.html( '<td>' + name + '</td>' + '<td>' + hours + '</td>' );
    $table.append($tr);
  }

  $el.prepend($table);
}

function get_todays_index( input ) {
  var i = 0 
    , len = input.length 
    , now = new Date()
    , day_string = now.toLocaleDateString('en-US', {month: 'long', day: 'numeric'})
    , day_regex = new RegExp(day_string)
    , day_match, day_close
    ;
  for ( ; i < len; i++ ) {
    if ( day_regex.test(input[i].day) || parseInt(Date.parse(input[i]['dateTime']['end'])) > now.valueOf() ) {
      return i;
    }
  }
}
