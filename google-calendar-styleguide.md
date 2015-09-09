# Library Google Calendar Styleguide #

## Regular/Updated Building Hours ##

The majority of events on the **Trexler Library** calendar are the building's hours, with the event beginning when the building opens and ending when it closes. The **Title** for standard hours events is "Library Hours".

## Closed Days ##

In the event that the building is closed, treat the event as an **all-day** one, and use the **Title** "Closed".

## Finals / Open 24 Hours ##

Here's where things get weird. For reasons I can't really remember, treating "Open 24 Hours" events as a single one that spans multiple days doesn't work out well (my immediate hunch is that, when displaying the open + close times there was no way to differentiate on _which_ day we closed). So we treat a day that we're open the entire day as an **all-day** event. That leaves us with the bookend days, when we open and remain open, and when we finally close.

Two assumptions are made, which we'll elaborate on in a minute: the Library will _never_ close at 11'59pm and will _never_ open at 12 midnight.

### Opening day ###

Since this is the beginning of our Finals Week schedule, we'll set the event with an open time, and set our closing time as **11'59pm**. We are making the assumption that the building will never close at this specific time (ie. always on an hour/half-hour).

### Closing day ###

Here's where our second assumption comes into play. We need to set the closing day to start at a time, so we'll use **12'00am**, assuming that we'll never actually open at Midnight. Then our closing time is set as the event's end.

### Example ###

For the sake of example, we'll treat the period of May 1st through May 5th as our Finals Week. We'll open at 11am May 1st and will remain open through 10pm May 5th:

* May 1st
  * open: 11'00am
  * close: 11'59pm
* May 2nd
  * all-day event
* May 3rd
  * all-day event
* May 4th
  * all-day event
* May 5th
  * open: 12'00am
  * close: 10'00pm

## A note on Titles ##

In cases of building hours, the Title field of the event isn't used. For the sake of consistency and, maybe?, planning ahead, I've been using "Library Hours".

In the other instances -- "Closed", "Begin 24 Hours", "Open 24 Hours", "Close at x:xx" -- the text that will appear in place of the hours on the website.
 
