# aeHelpers / classes for your everyday web work that just work

aeHelpers are the set of the helper-classes that can be used to solve
day-to-day issues of an average web-worker.

Contents:
	- aeDate
		Extends a build-in DateTime PHP class and helps setting,
		getting and calculating Date/Time data.
		Tired to remember error-prone PHP date() codes like j, S, d?
		Then this class is for you!
		
		Examples:
		
		- Set a date
			$date = new aeDate();
			$date->setDMY('3/3/2011');
		- Get a date
			$date = new aeDate();
			$date->getDMY();
			
	- Validator
	   !WORK IN PROGRESS!
	   Simplifies the interface of the php build-in validators

     Ever felt overwhelmed with those flags: FILTER_VALIDATE_INT, 
     FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION etc.?
     Then this class is for you.
     Simply define the filter type through the user-friendly 
     interface and take the advantage of the versatile 
     build-in validators.
