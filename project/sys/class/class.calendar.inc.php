<?php
	declare(strict_types=1);

	class Calendar extends DB_Connect{
		private $_useDate;
		private $_m;
		private $_y;
		private $_daysInMonth;
		private $_startDay;
		public function __construct($dbo=NULL, $useDate=NULL)
		{
			/*
			* Call the parent constructor to check for
			* a database object
			*/
			parent::__construct($dbo);

				/*
			* Gather and store data relevant to the month
			*/
			if ( isset($useDate) )
			{
				$this->_useDate = $useDate;
			}
			else
			{
				$this->_useDate = date('Y-m-d H:i:s');
			}
			/*
			* Convert to a timestamp, then determine the month
			* and year to use when building the calendar
			*/
			$ts = strtotime($this->_useDate);
			$this->_m = (int)date('m', $ts);
			$this->_y = (int)date('Y', $ts);

			/*
			* Determine how many days are in the month
			*/
			$this->_daysInMonth = cal_days_in_month(
				CAL_GREGORIAN,
				$this->_m,
				$this->_y
			);

			/*
			* Determine what weekday the month starts on
			*/
			$ts = mktime(0, 0, 0, $this->_m, 1, $this->_y);
			$this->_startDay = (int)date('w', $ts);
		}

		/**
		* Loads event(s) info into an array
		*
		* @param int $id an optional event ID to filter results
		* @return array an array of events from the database
		*/
		private function _loadEventData($id=NULL)
		{
			$sql = "SELECT
			`event_id`, `event_title`, `event_desc`,
			`event_start`, `event_end`
			FROM `events`";

			/*
			* If an event ID is supplied, add a WHERE clause
			* so only that event is returned
			*/

			if ( !empty($id))
			{	
				$sql .= "WHERE `event_id`= $id LIMIT 1";
			}

			/*
			* Otherwise, load all events for the month in use
			*/
			else
			{
				/*
				* Find the first and last days of the month
				*/
				$start_ts = mktime(0, 0, 0, $this->_m, 1, $this->_y);
				$end_ts = mktime(23, 59, 59, $this->_m+1, 0, $this->_y);
				$start_date = date('Y-m-d H:i:s', $start_ts);
				$end_date = date('Y-m-d H:i:s', $end_ts);
				/*
				* Filter events to only those happening in the
				* currently selected month
				*/
				$sql .= "WHERE `event_start`
				BETWEEN '$start_date'
				AND '$end_date'
				ORDER BY `event_start`";
			}

			try
			{	
				if(!empty($id)){
					$stmp = mysqli_query($this->db,$sql);
					$results = mysqli_fetch_assoc($stmp);
				}
				else{
					$results = mysqli_query($this->db,$sql);
				}
				return $results;
			}
			catch ( Exception $e )
			{
				die ( $e->getMessage() );
			}
		}


				/**
		* Loads all events for the month into an array
		*
		* @return array events info
		*/
		private function _createEventObj()
		{
			/*
			* Load the events array
			*/
			$arr = $this->_loadEventData();
			/*
			* Create a new array, then organize the events
			* by the day of the month on which they occur
			*/
			$events = array();
			foreach ( $arr as $event )
			{
				$day = date('j', strtotime($event['event_start']));
				try
				{
					$events[$day][] = new Event($event);
				}
				catch ( Exception $e )
				{
					die ( $e->getMessage() );
				}
			}
			return $events;
		}

				/**
		* Returns a single event object
		*
		* @param int $id an event ID
		* @return object the event object
		*/
		private function _loadEventById($id)
		{
			/*
			* If no ID is passed, return NULL
			*/
			if ( empty($id) )
			{
				return NULL;
			}
			/*
			* Load the events info array
			*/
			$event = $this->_loadEventData($id);
			/*
			* Return an event object
			*/
			if ( isset($event) )
			{
				return new Event($event);
			}
			else
			{
				return NULL;
			}
		}


		/**
		* Returns HTML markup to display the calendar and events
		*
		* Using the information stored in class properties, the
		* events for the given month are loaded, the calendar is
		* generated, and the whole thing is returned as valid markup.
		*
		* @return string the calendar HTML markup
		*/
		public function buildCalendar()
		{
			$events = $this->_createEventObj();
			/*
			* Determine the calendar month and create an array of
			* weekday abbreviations to label the calendar columns
			*/
			$cal_month = date('F Y', strtotime($this->_useDate));
			define('WEEKDAYS', array('Sun', 'Mon', 'Tue',
			'Wed', 'Thu', 'Fri', 'Sat'));
			/*
			* Add a header to the calendar markup
			*/
			$html = "<h2>$cal_month</h2>";
			for ( $d=0, $labels=NULL; $d<7; ++$d )
			{
				$labels .= "<li>" . WEEKDAYS[$d] . "</li>";
			}
			$html .= "<ul class='weekdays'>". $labels . "</ul>";

			/*
			* Create the calendar markup
			*/
			$html .= "<ul>"; // Start a new unordered list
			for ( $i=1, $c=1, $t=date('j'), $m=date('m'), $y=date('Y');
			$c<=$this->_daysInMonth; ++$i )
			{
				/*
				* Apply a "fill" class to the boxes occurring before
				* the first of the month
				*/
				$class = $i<=$this->_startDay ? "fill" : NULL;

				/*
				* Add a "today" class if the current date matches
				* the current date
				*/
				if ( $c==$t && $m==$this->_m && $y==$this->_y )
				{
					$class = "today";
				}
				/*
				* Build the opening and closing list item tags
				*/
				$ls = sprintf("\n\t\t<li class=\"%s\">", $class);
				$le = "\n\t\t</li>";
			    /*
				* Add the day of the month to identify the calendar box
				*/
				if ( $this->_startDay<$i && $this->_daysInMonth>=$c)
				{
					/*
					* Format events data
					*/
					$event_info = NULL; // clear the variable
					if ( isset($events[$c]) )
					{
						foreach ( $events[$c] as $event )
						{
							$link = '<a href="view.php?event_id='
							. $event->id . '">' . $event->title. '</a>';
							$event_info .= "\n\t\t\t$link";
						}
					}

					$date = sprintf("\n\t\t\t<strong>%02d</strong>",$c++);
				}
				else { $date="&nbsp;"; }
					/*
					* If the current day is a Saturday, wrap to the next row
					*/
					$wrap = $i!=0 && $i%7==0 ? "\n\t</ul>\n\t<ul>" : NULL;
					/*
					* Assemble the pieces into a finished item
					*/
					$html .= $ls . $date . $event_info . $le . $wrap;

			    }

			/*
			* Add filler to finish out the last week
			*/
			while ( $i%7!=1 )
			{
				$html .= "\n\t\t<li class=\"fill\">&nbsp;</li>";
				++$i;
			}
			/*
			* Close the final unordered list
			*/
			$html .= "\n\t</ul>\n\n";
			/*
			* Return the markup for output
			*/
			return $html;
		}

				/**
		* Displays a given event's information
		*
		* @param int $id the event ID
		* @return string basic markup to display the event info
		*/
		public function displayEvent($id)
		{
			/*
			* Make sure an ID was passed
			*/
			if ( empty($id) ) { return NULL; }
			/*
			* Make sure the ID is an integer
			*/
			$id = preg_replace('/[^0-9]/', '', $id);
			/*
			* Load the event data from the DB
			*/
			$event = $this->_loadEventById($id);
			/*
			* Generate strings for the date, start, and end time
			*/
			$ts = strtotime($event->start);
			$date = date('F d, Y', $ts);
			$start = date('g:ia', $ts);
			$end = date('g:ia', strtotime($event->end));
			/*
			* Generate and return the markup
			*/
			return "<h2>$event->title</h2>"
			. "\n\t<p class=\"dates\">$date, $start&mdash;$end</p>"
			. "\n\t<p>$event->description</p>";
		}

		/**
		* Generates a form to edit or create events
		*
		* @return string the HTML markup for the editing form
		*/
		public function displayForm()
		{
			/*
			// * Check if an ID was passed
			// */
			// if ( isset($_POST['event_id']) )
			// {
			// 	$id = (int) $_POST['event_id'];
			// 	// Force integer type to sanitize data
			// }
			// else
			// {
			// 	$id = NULL;
			// }
			// /*
			// * Instantiate the headline/submit button text
			// */
			// $submit = "Create a New Event";
			// /*
			// * If no ID is passed, start with an empty event object.
			// */
			// $event = new Event();
			// /*
			// * Otherwise load the associated event
			// */
			// if ( !empty($id) )
			// {
			// 	$event = $this->_loadEventById($id);
			// 	/*
			// 	* If no object is returned, return NULL
			// 	*/
			// 	if ( !is_object($event) ) { return NULL; }
			// 	$submit = "Edit This Event";
			// }
			// /*
			// * Build the markup
			// */
			// return <<<FORM_MARKUP
			// 	<form action="assets/inc/process.inc.php" method="post">
			// 		<fieldset>
			// 			<legend>$submit</legend>
			// 			<label for="event_title">Event Title</label>
			// 			<input type="text" name="event_title" id="event_title" value="$event->title" />
			// 			<label for="event_start">Start Time</label>
			// 			<input type="text" name="event_start" id="event_start" value="$event->start" />
			// 			<label for="event_end">End Time</label>
			// 			<input type="text" name="event_end" id="event_end" value="$event->end" />
			// 			<label for="event_description">Event Description</label>
			// 			<textarea name="event_description" id="event_description">$event->description</textarea>
			// 			<input type="hidden" name="event_id" value="$event->id" />
			// 		    <input type="hidden" name="token" value="$_SESSION[token]" />
			// 			<input type="hidden" name="action" value="event_edit" />
			// 			<input type="submit" name="event_submit" value="$submit" />
			// 			or <a href="./">cancel</a>
			// 		</fieldset>
			// 	</form>
			// FORM_MARKUP;
		}
	}