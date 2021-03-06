// ** I18N

// Calendar EN language
// Author: Mihai Bazon, <mihai_bazon@yahoo.com>
// Encoding: any
// Distributed under the same terms as the calendar itself.

// For translators: please use UTF-8 if possible.  We strongly believe that
// Unicode is the answer to a real internationalized world.  Also please
// include your contact information in the header, as can be seen above.

// full day names
Calendar._DN = new Array
("Sonntag",
 "Montag",
 "Dienstag",
 "Mittwoch",
 "Donnerstag",
 "Freitag",
 "Samstag");

// Please note that the following array of short day names (and the same goes
// for short month names, _SMN) isn't absolutely necessary.  We give it here
// for exemplification on how one can customize the short day names, but if
// they are simply the first N letters of the full name you can simply say:
//
//   Calendar._SDN_len = N; // short day name length
//   Calendar._SMN_len = N; // short month name length
//
// If N = 3 then this is not needed either since we assume a value of 3 if not
// present, to be compatible with translation files that were written before
// this feature.

// short day names
Calendar._SDN = new Array
("So",
 "Mo",
 "Di",
 "Mi",
 "Do",
 "Fr",
 "Sa");

// First day of the week. "0" means display Sunday first, "1" means display
// Monday first, etc.
Calendar._FD = 1;

// full month names
Calendar._MN = new Array
("Januar",
 "Februar",
 "März",
 "April",
 "Mai",
 "Juni",
 "Juli",
 "August",
 "September",
 "Oktober",
 "November",
 "Dezember");

// short month names
Calendar._SMN = new Array
("Jan",
 "Feb",
 "Mar",
 "Apr",
 "Mai",
 "Jun",
 "Jul",
 "Aug",
 "Sep",
 "Okt",
 "Nov",
 "Dez");

// tooltips
Calendar._TT = {};
Calendar._TT["INFO"] = "";

Calendar._TT["ABOUT"] = "";

Calendar._TT["PREV_YEAR"] = "Letztes Jahr (Gedrückt halten für Menu)";
Calendar._TT["PREV_MONTH"] = "Letzter Monat (Gedrückt halten für Menu)";
Calendar._TT["GO_TODAY"] = "Gehe zu Heute";
Calendar._TT["NEXT_MONTH"] = "Nächster Monat (Gedrückt halten für Menu)";
Calendar._TT["NEXT_YEAR"] = "Nächstes Jahr (Gedrückt halten für Menu)";
Calendar._TT["SEL_DATE"] = "Datum auswählen";
Calendar._TT["DRAG_TO_MOVE"] = "Halten um zu bewegen";
Calendar._TT["PART_TODAY"] = " (Heute)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "Zeige %s zuerst";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] = "0";

Calendar._TT["CLOSE"] = "Schliessen";
Calendar._TT["TODAY"] = "Heute";
Calendar._TT["TIME_PART"] = "Klicken oder ziehen um den Wert zu ändern";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%d-%m-%Y";
Calendar._TT["TT_DATE_FORMAT"] = "%A, %e. %B";

Calendar._TT["WK"] = "KW";
Calendar._TT["TIME"] = "Zeit:";
