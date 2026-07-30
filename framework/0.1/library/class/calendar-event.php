<?php

	class calendar_event_base extends check {

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {
				$this->setup($config);
			}

			protected function setup($config) {
			}

		//--------------------------------------------------
		//

			public function ics_get() {

				$summary = '[MySummary]';
				$description = '[MyMessageText]';
				$location = '[MyLocation]';
				$body = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">' . "\n" . '<HTML><BODY>' . html($description) . '</BODY></HTML>';

				$ics = [];
				$ics[] = 'BEGIN:VCALENDAR';
				$ics[] = 'VERSION:2.0';
				$ics[] = 'CALSCALE:GREGORIAN';
				$ics[] = 'METHOD:PUBLISH';
				$ics[] = 'BEGIN:VTIMEZONE';
				$ics[] = 'TZID:Europe/London';
				$ics[] = 'X-LIC-LOCATION:Europe/London';
				$ics[] = 'LAST-MODIFIED:20250523T094234Z';
				$ics[] = 'X-LIC-LOCATION:Europe/London';
				$ics[] = 'BEGIN:DAYLIGHT';
				$ics[] = 'TZNAME:BST';
				$ics[] = 'TZOFFSETFROM:+0000';
				$ics[] = 'TZOFFSETTO:+0100';
				$ics[] = 'DTSTART:19700329T010000';
				$ics[] = 'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU';
				$ics[] = 'END:DAYLIGHT';
				$ics[] = 'BEGIN:STANDARD';
				$ics[] = 'TZNAME:GMT';
				$ics[] = 'TZOFFSETFROM:+0100';
				$ics[] = 'TZOFFSETTO:+0000';
				$ics[] = 'DTSTART:19701025T020000';
				$ics[] = 'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU';
				$ics[] = 'END:STANDARD';
				$ics[] = 'END:VTIMEZONE';
				$ics[] = 'BEGIN:VEVENT';
				$ics[] = 'UID:57fa2779-3f0b-3cc0-ba4b-fd512441bc38';
				$ics[] = 'DTSTAMP:20260522T165722Z';
				$ics[] = 'DTSTART;TZID=Europe/London:20260603T114000';
				$ics[] = 'DTEND;TZID=Europe/London:20260603T115000';
				$ics[] = 'SUMMARY:' . $summary;
				$ics[] = 'DESCRIPTION:' . $description;
				$ics[] = 'X-ALT-DESC;FMTTYPE=text/html:';
				$ics[] = ' ' . $body; // Each line indented by a space? and max-line length?
				$ics[] = 'LOCATION:' . $location;
				$ics[] = 'SEQUENCE:0';
				$ics[] = 'STATUS:CONFIRMED';
				$ics[] = 'CREATED:20260522T165722Z';
				$ics[] = 'LAST-MODIFIED:20260522T165722Z';
				$ics[] = 'END:VEVENT';
				$ics[] = 'END:VCALENDAR';

			}

			public function urls_get() {

				$urls = [];

				$urls['google'] = [
					'name' => 'Google',
					'url' => url('https://calendar.google.com/calendar/u/0/r/eventedit', [
							'dates'    => '20260603T114000/20260603T115000',
							'ctz'      => 'Europe/London',
							'text'     => '[MyHeading]',
							'location' => '[MyLocation]',
							'details'  => '[MyMessageText]',
						]),
					];

				$urls['outlook'] = [
					'name' => 'Outlook',
					'url' => url('https://outlook.live.com/calendar/0/action/compose', [
							'rru'      => 'addevent',
							'startdt'  => '2026-06-03T10:40:00Z',
							'enddt'    => '2026-06-03T10:50:00Z',
							'subject'  => '[MyHeading]',
							'location' => '[MyLocation]',
							'body'     => '[MyMessageText]',
						]),
					];

				// MS-365?

					// https://outlook.live.com
					// https://outlook.office.com"
					// 	/calendar/0/deeplink/compose?path=%2Fcalendar%2Faction%2Fcompose&rru=addevent
					// 	/calendar/0/action/compose?rru=addevent
					// 	&startdt=
					// 	&enddt=
					// 	&allday=true
					// 	&subject=
					// 	&location=
					// 	&body=

					// 	https://outlook.live.com/calendar/0/addfromweb/
					// 	https://outlook.office.com/calendar/0/addfromweb/

				// MS-Teams?

					// https://teams.microsoft.com/l/meeting/new

			}

		//--------------------------------------------------
		// SVGs

			public function svgs_get() {

				return [
						'ical'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><path d="M132.8 7.7c0-4.2 4.2-7.7 9.4-7.7s9.4 3.5 9.4 7.7v33.7c0 4.3-4.2 7.7-9.4 7.7s-9.4-3.4-9.4-7.7zM107.6 169c-.6 0-1-2.4-1-5.2s.4-5.3 1-5.3h25.7c.6 0 1 2.4 1 5.3s-.4 5.2-1 5.2zm-81.8-59.8c-.6 0-1-2.3-1-5.2s.4-5.2 1-5.2h25.7c.6 0 1 2.3 1 5.2s-.4 5.2-1 5.2zm40.9 0c-.6 0-1-2.3-1-5.2s.4-5.2 1-5.2h25.7c.6 0 1 2.3 1 5.2s-.4 5.2-1 5.2zm40.9 0c-.6 0-1-2.3-1-5.2s.4-5.2 1-5.2h25.7c.6 0 1 2.3 1 5.2s-.4 5.2-1 5.2zm41 0c-.6 0-1-2.3-1-5.2s.4-5.2 1-5.2h25.6c.6 0 1 2.3 1 5.2s-.4 5.2-1 5.2zM25.7 139.1c-.6 0-1-2.3-1-5.2s.4-5.2 1-5.2h25.7c.6 0 1 2.3 1 5.2s-.4 5.2-1 5.2zm40.9 0c-.6 0-1-2.3-1-5.2s.4-5.2 1-5.2h25.7c.6 0 1 2.3 1 5.2s-.4 5.2-1 5.2zm40.9 0c-.6 0-1-2.3-1-5.2s.4-5.2 1-5.2h25.7c.6 0 1 2.3 1 5.2s-.4 5.2-1 5.2zm41 0c-.6 0-1-2.3-1-5.2s.4-5.2 1-5.2h25.6c.6 0 1 2.3 1 5.2s-.4 5.2-1 5.2zM25.7 169c-.6 0-1-2.4-1-5.2s.4-5.3 1-5.3h25.7c.6 0 1 2.4 1 5.3s-.4 5.2-1 5.2zm40.9 0c-.6 0-1-2.4-1-5.2s.4-5.3 1-5.3h25.7c.6 0 1 2.4 1 5.3s-.4 5.2-1 5.2zM48.2 7.7c0-4.2 4.2-7.7 9.4-7.7S67 3.5 67 7.7v33.7c0 4.3-4.2 7.7-9.4 7.7s-9.4-3.4-9.4-7.7zm-37.8 66h179.2V35a5 5 0 0 0-4.8-4.7h-17.2a5.2 5.2 0 1 1 0-10.4h17.2A15 15 0 0 1 200 34.9v150a15 15 0 0 1-15.2 15.1H15.2A15 15 0 0 1 0 184.8V35a15 15 0 0 1 15.2-15.2h18.3a5.2 5.2 0 1 1 0 10.4H15.2a5 5 0 0 0-4.8 4.7zm179.2 10.5H10.4v100.6a5 5 0 0 0 4.8 4.8h169.6a5 5 0 0 0 4.8-4.8zM82 30.2a5.2 5.2 0 1 1 0-10.4h35a5.2 5.2 0 1 1 0 10.4z"/></svg>',
						'apple'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><path d="M154.6 106.3c-.2-25.4 20.7-37.5 21.6-38.1a47 47 0 0 0-36.6-19.8c-15.5-1.6-30.3 9.1-38.3 9.1-7.8 0-20-8.9-33-8.7A49 49 0 0 0 27 74c-17.7 30.6-4.5 76 12.6 100.8 8.4 12.1 18.5 25.8 31.6 25.3 12.7-.5 17.5-8.2 32.8-8.2s19.6 8.2 33 8c13.6-.3 22.2-12.5 30.6-24.7 9.6-14 13.6-27.7 13.8-28.4-.3-.1-26.5-10.2-26.8-40.4m-25.1-74.4A44 44 0 0 0 139.9 0c-10 .4-22.3 6.7-29.5 15.2A42 42 0 0 0 99.8 46a37 37 0 0 0 29.6-14.2"/></svg>',
						'yahoo'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><path d="M0 54.4h38.1l22.3 56.8 22.5-56.8h37L64.2 188.9H26.7L42 153.3zm163.2 45.4h-41.6l37-88.7H200zm-30.7 8.5a23.1 23.1 0 1 1 0 46.2 23.1 23.1 0 0 1 0-46.2"/></svg>',
						'ms365'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><path d="M183.4 183.3V17.2L123.9 0l-107 40.2h-.3v120.2L53.1 146V48.3L124 31.4v143.7L16.7 160.4 123.9 200l59.5-16.5z"/></svg>',
						'msteams' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><path fill="#5059c9" d="M195.3 46.4a21 21 0 1 1-41.8 0 21 21 0 0 1 41.8 0m-55.8 30.2h51.7c4.8 0 8.8 4 8.8 8.8v47c0 18-14.5 32.6-32.5 32.6h-.1c-18 0-32.5-14.6-32.5-32.5V81.2c0-2.5 2-4.6 4.6-4.6"/><path fill="#7b83eb" d="M149.6 76.6H64.3a8.7 8.7 0 0 0-8.5 9v53.6c-.7 29 22.2 53 51.2 53.7 29-.7 51.8-24.8 51.1-53.7V85.5a8.7 8.7 0 0 0-8.5-9m-10-39.5A30.2 30.2 0 0 1 79 37a30.2 30.2 0 0 1 60.4 0"/><path d="M111.6 76.6v75.2a8.6 8.6 0 0 1-8.5 8.5H60l-1.6-4.6a57 57 0 0 1-2.5-16.5V85.5a8.7 8.7 0 0 1 8.5-9z" opacity=".1"/><path d="M107 76.6v79.9q0 1.5-.7 3.2a9 9 0 0 1-7.8 5.3H62l-2.2-4.7-1.6-4.6a57 57 0 0 1-2.5-16.5V85.5a8.7 8.7 0 0 1 8.5-9z" opacity=".2"/><path d="M102.3 76.6v70.6c0 4.6-3.8 8.4-8.5 8.5H58.3a57 57 0 0 1-2.5-16.5V85.5a8.7 8.7 0 0 1 8.5-9z" opacity=".2"/><path d="M111.6 52.5v14.7H107q-2.4-.2-4.7-.7A30 30 0 0 1 80 44h23.2c4.7 0 8.5 3.9 8.5 8.5" opacity=".1"/><path d="M107 57.2v10q-2.4-.2-4.7-.7a30 30 0 0 1-20.9-17.8h17c4.8 0 8.6 3.8 8.6 8.5m0 19.4v70.6c0 4.6-3.8 8.4-8.5 8.5H58.3a57 57 0 0 1-2.5-16.5V85.5a8.7 8.7 0 0 1 8.5-9z" opacity=".2"/><path d="M102.3 57.2v9.3a30 30 0 0 1-20.9-17.8h12.4c4.7 0 8.5 3.8 8.5 8.5" opacity=".2"/><linearGradient id="a" x1="17.8" x2="84.5" y1="35.2" y2="150.8" gradientTransform="translate(0 6.8)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#5a62c3"/><stop offset=".5" stop-color="#4d55bd"/><stop offset="1" stop-color="#3940ab"/></linearGradient><path fill="url(#a)" d="M8.5 48.7h85.3c4.7 0 8.5 3.8 8.5 8.5v85.3c0 4.7-3.8 8.5-8.5 8.5H8.5a8.5 8.5 0 0 1-8.5-8.5V57.2c0-4.7 3.8-8.5 8.5-8.5"/><path fill="#fff" d="M73.6 81.1h-17v46.5h-11V81H28.8v-9h44.9z"/></svg>',
						'outlook' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><path fill="#0364b8" d="M178.7 12.5H71.3c-4.9 0-8.8 4-8.8 8.8v10L123.1 50l64.4-18.7v-10c0-4.9-4-8.8-8.8-8.8"/><path fill="#0a2767" d="M197.8 108.8c1-2.9 2.2-5.9 2.2-8.8 0-1.5-.8-2.9-1.5-3.3l-68.4-39a9 9 0 0 0-10.2 0L52.2 96.3l-.1.1q-2 1.2-2.1 3.6.8 4.5 2.2 8.8l71.8 52.5z"/><path fill="#28a8ea" d="M150 31.3h-43.7L93.6 50l12.7 18.8 43.7 37.5h37.5V68.8z"/><path fill="#50d9ff" d="M150 31.3h37.5v37.5H150z"/><path fill="#0364b8" d="m150 106.3-43.7-37.5H62.5v37.5l43.8 37.5 67.6 11z"/><path fill="#0078d4" d="M106.3 68.8v37.5H150V68.8zm43.7 37.5v37.5h37.5v-37.6zm-87.5-75h43.8v37.5H62.5z"/><path fill="#064a8c" d="M62.5 106.3h43.8v37.5H62.5z"/><path fill="#0a2767" d="M126.2 157.6 52.5 104l3-5.5 68.3 38.8q1.3.6 2.6 0l68.3-39 3.1 5.5z" opacity=".5"/><path fill="#1490df" d="M198 103.6h-.2l-67.7 38.7a9 9 0 0 1-9.2.5l23.6 31.7 51.6 11.2a9 9 0 0 0 3.9-7.6V100q-.1 2.4-2 3.6"/><path d="M200 178.1v-4.6L137.6 138l-7.5 4.3a9 9 0 0 1-9.1.5l23.5 31.7 51.6 11.2a9 9 0 0 0 3.9-7.6" opacity=".1"/><path d="m199.7 180.5-68.4-39-1.2.8a9 9 0 0 1-9.1.5l23.5 31.7 51.6 11.2q2.7-2 3.6-5.2" opacity=".1"/><path fill="#28a8ea" d="M51.5 103.2c-.8-.4-1.5-1.8-1.5-3.2v78.1c0 5.2 4.2 9.4 9.4 9.4h131.2a11 11 0 0 0 3.8-.8l1.7-1z"/><path d="M112.5 154.2V52c0-4.6-3.7-8.3-8.3-8.4H62.7v46.6l-10.5 6H52q-2 1.3-2.1 3.7v62.5h54.2c4.6 0 8.3-3.7 8.3-8.3" opacity=".1"/><path d="M106.3 160.4v-102c0-4.7-3.8-8.4-8.4-8.4H62.7v40.3l-10.5 6H52q-2 1.3-2.1 3.7v68.8h48c4.5 0 8.2-3.8 8.3-8.4" opacity=".2"/><path d="M106.3 148V58.2c0-4.6-3.8-8.3-8.4-8.3H62.7v40.3l-10.5 6H52q-2 1.3-2.1 3.7v56.3h48c4.5 0 8.2-3.8 8.3-8.4" opacity=".2"/><path d="M100 148V58.2c0-4.6-3.7-8.3-8.3-8.3h-29v40.3l-10.5 6H52q-2 1.3-2.1 3.7v56.3h41.7c4.6 0 8.3-3.8 8.3-8.4" opacity=".2"/><path fill="#0078d4" d="M8.3 50h83.4c4.6 0 8.3 3.7 8.3 8.3v83.4c0 4.6-3.7 8.3-8.3 8.3H8.3a8.3 8.3 0 0 1-8.3-8.3V58.3C0 53.7 3.7 50 8.3 50"/><path fill="#fff" d="M24.2 84.2q3.5-7.2 10.2-11.4a31 31 0 0 1 16.3-4q8-.2 15.1 3.8 6.6 4 10 10.9a35 35 0 0 1 3.5 15.9q.2 8.8-3.6 16.7a27 27 0 0 1-10.3 11.2 30 30 0 0 1-15.6 4q-8.3 0-15.5-4a26 26 0 0 1-10-10.8 34 34 0 0 1-3.6-15.8q-.2-8.6 3.5-16.5m11 26.6a17 17 0 0 0 5.7 7.5 15 15 0 0 0 9 2.7q5.2.1 9.6-2.8 4-3 5.6-7.5a31.6 31.6 0 0 0 .1-21q-1.6-4.6-5.4-7.7a15 15 0 0 0-9.5-3q-5 0-9.3 2.8a17 17 0 0 0-5.9 7.5c-2.6 7-2.7 14.6 0 21.5"/></svg>',
						'google'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><path fill="#fff" d="M152.6 47.4H47.4v105.2h105.2z"/><path fill="#f72a25" d="m152.6 200 47.4-47.4h-47.4z"/><path fill="#fbbc04" d="M200 47.4h-47.4v105.2H200z"/><path fill="#34a853" d="M152.6 152.6H47.4V200h105.2z"/><path fill="#188038" d="M0 152.6v31.6A16 16 0 0 0 15.8 200h31.6v-47.4z"/><path fill="#1967d2" d="M200 47.4V15.8A16 16 0 0 0 184.2 0h-31.6v47.4z"/><path fill="#4285f4" d="M15.8 0A16 16 0 0 0 0 15.8v136.8h47.4V47.4h105.2V0z"/><path fill="#1a73e8" d="M69 129q-6-4-8.2-11.6l9.2-3.8q1.2 4.7 4.3 7.3t7.5 2.6 7.7-2.7a9 9 0 0 0 3.2-7q0-4.2-3.4-7c-3.4-2.8-5.1-2.7-8.5-2.7h-5.3v-9h4.8q4.3 0 7.3-2.4 3-2.3 3-6.5 0-3.7-2.6-5.8c-2.6-2.1-4.1-2.2-6.8-2.2q-4.1 0-6.4 2.1a10 10 0 0 0-3.5 5.3l-9-3.8q1.8-5.1 6.6-9C73.7 69 76.2 69 81.2 69q5.5 0 10 2.2c4.5 2.2 5.3 3.4 7 6q2.5 3.7 2.5 8.5t-2.4 8.2a16 16 0 0 1-5.7 5.1v.5a17 17 0 0 1 7.3 5.8q3 3.8 2.9 9.2c-.1 5.4-1 6.8-2.7 9.6q-2.7 4.2-7.5 6.6a19 19 0 0 1-10.8 2.4q-7 0-12.8-4m56-45.3L115 91l-5-7.6 18-13h6.9v61.2H125z"/></svg>',
					];

			}

	}

?>