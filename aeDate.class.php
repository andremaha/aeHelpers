<?php
namespace aeHelpers;


use \Exception;
use \DateTime;

/**
 * Expands and enriches the DateTime build-in class
 * 
 * Working with dates in the controlled environment. 
 * 
 * Following methods get overriden:
 *  - __construct()
 *  - modify()
 *  - setTime()
 *  - setDate()
 * 
 * New methods for setting dates:
 *  - setMDY()
 *  - setDMY()
 *  - setFromMySQL()
 * 
 * New methods for displaying dates:
 *  - getMDY()
 *  - getDMY()
 *  - getMySQLFormat()
 * 
 * New methods for displaying date parts:
 *  - getFullYear()
 *  - getYear()
 *  - getMonth()
 *  - getMonthName()
 *  - getMonthAbbr()
 *  - getDay()
 *  - getDayOrdinal()     - 1st 2nd 3rd
 *  - getDayName()        - Thursday
 *  - getDayAbbr()        - Fri
 * 
 * New methods for doing date calculations:
 * 	- addDays()
 * 	- subDays()
 * 	- addWeeks()
 * 	- subWeeks()
 * 	- addMonths()
 * 	- subMonths()
 * 	- addYears()
 * 	- subYears()
 * 	- dateDiff()
 * 
 * Magical methods defined:
 * 	- __toString()
 * 	- __get()
 *  
 * @author    Andrey Esaulov 
 * @license		GPL
 * @version   1.0
 */
class Date extends DateTime 
{
	
	protected $_year;
	protected $_month;
	protected $_day;
	
	
	/**
	 * Calculates the number of days between two dates
	 * 
	 * Converts the input start/end dates to the unix timestamps.
	 * UTC is used, so the light savings won't interfere. 
	 * Operates with the timestams and returns
	 * 	- a positive number, if the $startDate < (earlier) $endDate
	 * 	- a negative number, if the $startDate > (later) $endDate
	 * 
	 * @param Date $startDate		The Date object to start calculations with
	 * @param Date $endDate			The Date object to finish calculations with
	 * 
	 * @return	Amount of days left (positive number) / gone (negativ number)
	 */
	static public function dateDiff(Date $startDate, Date $endDate)
	{
		// Make a unix timestamp from start date
		$start = gmmktime(0, 0, 0, $startDate->_month, $startDate->_day, $startDate->_year);
		
		// Make a unix timestamp form the end date
		$end   = gmmktime(0, 0, 0, $endDate->_month, $endDate->_day, $endDate->_year);
		
		// Perform the calculation and return result
		return ($end - $start) / (60 * 60 * 24);
	}
	

	/**
	 * Expands the parent constructor of DateTime
	 * 
	 * Checks if the timezone is set, if no - defalut will be chosen.
	 * No need to pass the first argument, because the default 'now' is
	 * hard-coded.
	 * year, month and day variables are set.
	 * NOTE: As of PHP 5.3 we have to set DateTimeZone explicidly
	 * 
	 * @param    string    $timezone   Optional. Timezone is expected.
	 * @return   void
	 */
	public function __construct($timezone = null)
	{
		// Call the parent constructor
		if ($timezone)
		{
			parent::__construct('now', $timezone);
		}
		else
		{
			parent::__construct('now', new DateTimeZone('Europe/Berlin'));
		}
		
		// Assign values to protected variables
		$this->_year  = $this->format('Y');
		$this->_month = $this->format('n');
		$this->_day   = $this->format('j');	
	}
	

	/**
	 * Disable modify() method
	 * 
	 * modify() method accepts out of range dates and yield unexpected 
	 * results. For the sake of controll it is disabled in this class
	 */
	public function modify()
	{
		throw new Exception('modify() has been disabled');
	}
	
	
	/**
	 * Checks the time values and sets the time
	 * 
	 * Overrides the native DateTime method to perform additional checks:
	 *   - are the given values numeric
	 *   - are the given values in Range (i.e. hour is not negative, or 25)
	 * Finally the time is set
	 * 
	 * @param    int   $hours    Defines  hours to set
	 * @param    int   $minutes  Defines  mintes to set
	 * @param    int   $seconds  Optional. Defines  seconds to set.
	 * 
	 * @return   void
	 */
	public function setTime($hours, $minutes, $seconds = 0)
	{
		// Perform the numeric check
		if (!is_numeric($hours) || !is_numeric($minutes) || !is_numeric($seconds))
		{
			throw new Exception('setTime() expects two or three numbers separated by commas in the order: hours, minutes, seconds');
		}
		
		// Perform the out of range check
		$outOfRange = false;
		
		if ($hours < 0 || $hours > 23)
		{
			$outOfRange = true;
		}
		if ($minutes < 0 || $minutes > 59)
		{
			$outOfRange = true;
		}
		if ($seconds < 0 || $seconds > 59)
		{
			$outOfRange = true;
		}
		
		if ($outOfRange)
		{
			throw new Exception('Invalid time');
		}
		
		// Set the time
		parent::setTime($hours, $minutes, $seconds);
	}
	
	/**
	 * Checks the date values and sets the date
	 * 
	 * Performs additional checks of the date values:
	 *   - are the values numeric?
	 *   - is the date valid? (i.e. leap year, 32 December etc.)
	 * Sets the date using DateTime method setDate()
	 * Sets the values for internal variables year, month, day
	 * 
	 * @param    int   $year   Numeric value of the year  - YYYY
	 * @param    int   $month  Numeric value of the month - MM 
	 * @param    int   $day    Numeric value of the day   - DD
	 * 
	 * @return   void
	 */
	public function setDate($year, $month, $day)
	{
		// Perform the numeric check
		if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day))
		{
			throw new Exception('setDate() expects three numbers in this order: year, month, day');
		}
		
		// Perform the date validation check
		if (!checkdate($month, $day, $year))
		{
			throw new Exception('Non-existent date');
		}
		
		// Set the date
		parent::setDate($year, $month, $day);

		// Set the internal variables
		$this->_year  = $year;
		$this->_month = $month;
		$this->_day   = $day; 
	}
	

	/**
	 * Sets the date, using MM/DD/YYYY format as an argument
	 * 
	 * Accepted formats include:
	 *   - MM/DD/YYYY
	 *   - MM-DD-YYYY
	 *   - MM:DD:YYYY
	 *   - MM.DD.YYYY
	 *   - MM DD YYYY
	 * Method then checks the input data and passes the year, month and day
	 * to the customized Date::setDate() method, where additional checks
	 * are performed
	 * 
	 * @param    int   $USdate     Defines the date in the US-Format: MM/DD/YYYY
	 * 
	 * @return   void
	 */
	public function setMDY($USdate)
	{
		// Put month, day and year in an array
		$dateParts = preg_split('{[-/ :.]}', $USdate);
		
		// Check the array for values
		if (!is_array($dateParts) || count($dateParts) != 3)
		{
			throw new Exception('setMDY expects a date as MM/DD/YYYY');
		}
		if (strlen($dateParts[2]) != 4)
		{
			throw new Exception('setMDY() expects a year in 4-digit format');
		}
		
		// Pass the values to setDate method
		$this->setDate($dateParts[2], $dateParts[0], $dateParts[1]);
	}
	
	/**
   * Sets the date, using DD/MM/YYYY format as an argument
   * 
   * Accepted formats include:
   *   - DD/MM/YYYY
   *   - DD-MM-YYYY
   *   - DD:MM:YYYY
   *   - DD.MM.YYYY
   *   - DD MM YYYY
   * Method then checks the input data and passes the year, month and day
   * to the customized Date::setDate() method, where additional checks
   * are performed
   * 
   * @param    int   $EUdate     Defines the date in the EU-Format: DD/MM/YYYY
   * 
   * @return   void
   */
	public function setDMY($EUdate)
	{
		// Put day, month and year in an array
    $dateParts = preg_split('{[-/ :.]}', $EUdate);
    
    // Check the array for values
    // And the year has 4 digits
    if (!is_array($dateParts) || count($dateParts) != 3)
    {
      throw new Exception('setDMY() expects a date as DD/MM/YYYY');
    }
    elseif (strlen($dateParts[2]) != 4)
    {
    	throw new Exception('setDMY() expects a year in 4-digits format');
    }
    
    // Pass the values to setDate method
    $this->setDate($dateParts[2], $dateParts[1], $dateParts[0]);
	}
	
	/**
   * Sets the date, using YYYY-MM-DD format as an argument
   * 
   * Accepted formats include:
   *   - YYYY-MM-DD
   *   - YYYY/MM/DD
   *   - YYYY:MM:DD
   *   - YYYY MM DD
   *   - YYYY.MM.DD
   * Method then checks the input data and passes the year, month and day
   * to the customized Date::setDate() method, where additional checks
   * are performed
   * 
   * @param    int   $MySQLDate     Defines the date in the MySQL-Format: YYYY-MM-DD
   * 
   * @return   void
   */
	public function setFromMySQL($MySQLDate)
	{
		// Put year, month and day in an array
		$dateParts = preg_split('{[-/ :.]}', $MySQLDate);
		
		// Chech the array
		if (!is_array($dateParts) || count($dateParts) != 3)
		{
			throw new Exception('setFromMySQL() expects a data in this format: YYYY-MM-DD');
		}
		
		// Pass to the setDate() method
		$this->setDate($dateParts[0], $dateParts[1], $dateParts[2]);
	}
	

	/**
	 * Outputs a date in the MM/DD/YYYY format
	 * 
	 * Wraps the format() method of the DateTime into a human-friendly interface.
	 * By default no leading zeros will be in the output
	 * 
	 * @param    boolean   $leadingZeros   Optional. Will the output include leading zeros?
	 * 
	 * @return   string    Date in the MM/DD/YYYY format
	 */
	public function getMDY($leadingZeros = false)
	{
		if ($leadingZeros)
		{
			return $this->format('m/d/Y');
		}
		else
		{
			return $this->format('n/j/Y');
		}
	}
	
	/**
   * Outputs a date in the DD/MM/YYYY format
   * 
   * Wraps the format() method of the DateTime into a human-friendly interface.
   * By default no leading zeros will be in the output
   * 
   * @param    boolean   $leadingZeros   Optional. Will the output include leading zeros?
   * 
   * @return   string    Date in the DD/MM/YYYY format
   */
	public function getDMY($leadingZeros = false)
	{
		if ($leadingZeros)
		{
			return $this->format('d/m/Y');
		}
		else
		{
			return $this->format('j/n/Y');
		}
	}
	
	/**
  * Outputs a date in the YYYY-MM-DD format
  * 
  * Wraps the format() method of the DateTime into a human-friendly interface.
  * By default no leading zeros will be in the output
  * 
  * @return   string    Date in the YYYY-MM-DD format
  */
	public function getMySQLFormat()
	{
		return $this->format('Y-m-d');
	}
	
	/**
	 * Outputs the Full year - YYYY
	 * 
	 * @return   int  Year in the YYYY format
	 */
	public function getFullYear()
	{
		return $this->format('Y');
	}
	
	/**
	 * Outputs the two last digits of the year - YY
	 * 
	 * @return   int   Year in YY format
	 */
	public function getYear()
	{
		return $this->format('y');
	}
	
	/**
	 * Outputs the digit of the month
	 * 
	 * If parameter set to true, leading zero will be in the output
	 * 
	 * @param    boolen    $leadingZeros     Optional. Will the output include leading zero?
	 * 
	 * @return   int   Digit of the month
	 */
	public function getMonth($leadingZero = false)
	{
		if ($leadingZero)
		{
			return $this->format('m');
		}
		else 
		{
			return $this->format('n');
		}
	}
	
	/**
	 * Outputs the name of the month
	 * 
	 * @return   string    Name of the month
	 */
	public function getMonthName()
	{
		return $this->format('F');
	}
	
	/**
	 * Outputs the abbreviation of the month's name
	 * 
	 * @return   string    Month's abbreviation
	 */
	public function getMonthAbbr()
	{
		return $this->format('M');
	}
	
	/**
	 * Outputs the digit of the day
	 * 
	 * If parameter set to true, leading zero will be in the output
	 * 
	 * @param    boolean   $leadingZero    Optional. Will the output include leading zero?
	 * 
	 * @return   int   Digit of the day
	 */
	public function getDay($leadingZero = false)
	{
		if ($leadingZero)
		{
			return $this->format('d');
		}
		else
		{
			return $this->format('j');
		}
	}
	
	/**
	 * Outputs the day with the suffix behind it
	 * 
	 * Includes the corresponding suffix behind the day:
	 *   - 1st
	 *   - 2nd
	 *   - 3rd
	 *   - ...
	 * 
	 * @retrun   string    Day with st/nd/rd etc. suffix behind it
	 */
	public function getDayOrdinal()
	{
		return $this->format('jS');
	}
	
	/*
	 * Outputs the day of the week
	 * 
	 * @return   sting   The day of the week
	 */
	public function getDayName()
	{
		return $this->format('l');
	}
	
	/**
	 * Outputs the abbreviation of the day of the week
	 * 
	 * @return    string   Abbreviation of the day of the week - i.e. Mon., Fri.
	 */
	public function getDayAbbr()
	{
		return $this->format('D');
	}
	

	/**
	 * Adds x amount of days to the date
	 * 
	 * Performs extra checks if the argument passed is a positive number.
	 * Converts a number to an integer if it's a double.
	 * Sets the enternal year, month and day values.
	 * 
	 * @param  int   $numDays    Number of days to add - positive integer
	 * 
	 * @return void
	 */
	public function addDays($numDays)
	{
		// Perform a numeric chech
		if (!is_numeric($numDays) || $numDays < 1)
		{
			throw new Exception('addDays() expects a positive integer');
		}
		
		// Perform the addition
		parent::modify('+' . intval($numDays) . ' days');
		
		// Set the internal properties
		$this->_year  = (int) $this->format('Y');
		$this->_month = (int) $this->format('n');
		$this->_day   = (int) $this->format('j');
	}
	
	/**
	 * Subtracts x amount of days from the date
	 * 
	 * Performs additional check if the given argument is numeric.
	 * Transforms negative intger into positive and then performs a 
	 * subtraction.
	 * 
	 * @param  int   $numDays    Number of days to subtract - positive integer
	 * 
	 * @return void
	 */
	public function subDays($numDays)
	{
		// Perform a numeric check
		if (!is_numeric($numDays))
		{
			throw new Exception('subDays() expects a positive integer');
		}
		
		// Convert a negative number to a positive
		// Perform a subtraction
		parent::modify('-' . abs(intval($numDays)) . ' days');
		
		// Set the internal properties
    $this->_year  = (int) $this->format('Y');
    $this->_month = (int) $this->format('n');
    $this->_day   = (int) $this->format('j');
	}
	
	/**
	 * Adds x amount of weeks to the date
	 * 
	 * Performs additional chechs if the given argument is numeric.
	 * Transfers doubles into integers.
	 * 
	 * @param  int   $numWeeks   Number of weeks to add - positive integer
	 */
	public function addWeeks($numWeeks)
	{
		// Perform a numeric check
		if (!is_numeric($numWeeks) || $numWeeks < 1)
		{
			throw new Exception('addWeeks() expects a positive integer');
		}
		
		// Convert a double to an integer.
		// Perform an addition
		parent::modify('+' . intval($numWeeks) . ' weeks');
		
		// Set the internal properties
    $this->_year  = (int) $this->format('Y');
    $this->_month = (int) $this->format('n');
    $this->_day   = (int) $this->format('j');
	}
	
	/**
	 * Subtracts x amount of weeks from the date
	 * 
	 * Performs several checks - i.e. if the given argument is numeric.
	 * Converts doubles into positive integers.
	 * 
	 * @param   int    $numWeeks   Number of weeks to subtract - positive integer
	 */
	public function subWeeks($numWeeks)
	{
		// Perform a numeric check
		if (!is_numeric($numWeeks))
		{
			throw new Exception('subWeeks() expects a positive integer');
		}
		
		// Convert to positive integer
		// Perform a subtraction
		parent::modify('-' . abs(intval($numWeeks)) . ' weeks');
		
		// Set the internal properties
    $this->_year  = (int) $this->format('Y');
    $this->_month = (int) $this->format('n');
    $this->_day   = (int) $this->format('j');
	}
	
	/**
	 * Add x amount of months to the date
	 * 
	 * Performs additional very tricky checks. For instance, checks, if the
	 * result of the addition is valid and what we want. 
	 * We want to be able to add a month to 31 August and get 30 September as a result.
	 * And not 1 October.
	 * Manages the whole leap-year issues using heper-method isLeap()
	 * 
	 * @param    int   $numMonths    Number of months to add
	 * 
	 * @return   void
	 */
	public function addMonths($numMonths)
	{
		// Perform a numeric check
		if (!is_numeric($numMonths) || $numMonths < 1)
		{
			throw new Exception('addMonths() expects a positive integer');
		}
		
		// Convert to integer to perform following calculations
		$numMonths = (int) $numMonths;
		
		// Add the months to the current month number
		$newValue = $this->_month + $numMonths;
		
		// If the $newValue is less then or equal 12, we are still in the same year,
		// so just assign a new value to a month
		if ($newValue <= 12)
		{
			$this->_month = $newValue;
		}
		// The $newValue is greater then 12. We'll need to calculate both
		// a year and a month
		else
		{
			// Calculating a year is different for december,
			// so we do a modulo devision by 12 to check.
			// If the remainer is not 0, the new month is not December.
			$notDecember = $newValue % 12;
			
			if($notDecember)
			{
				// The remainder of the modulo division is a new month
				$this->_month = $notDecember;
				
				// To get the number of years to add,
				// divide the $newValue by 12 and round down the result
				$this->_year += floor($newValue / 12);
			}
			// The new month must be December
			else
			{
				$this->_month = 12;
				$this->_year += ($newValue / 12) - 1;
			}
		}
		
		// Perform the last day of the month check
		$this->checkLastDayOfMonth();
		
		// Set new date directly, without our setters.
		// Date is checked inside checkLastDayOfMonth(), 
		// so we're saving some calculation time.
		parent::setDate($this->_year, $this->_month, $this->_day);
	}
	

	

	
	/**
	 * Subtracts x amount of months from the date
	 * 
	 * Performs additional very tricky chechs. 
	 * Fist, we have to figure out if the amount of months we
	 * subtract does not get us of the current year. Then we 
	 * just have to change internal $_month value.
	 * If we're in the previous year(s), we have to take this 
	 * into account - we have to change both $_month and $_year.
	 * We also deal with the issue of December - since it is a special
	 * case - since there is no remainder by dividing to 12.
	 * At the end the last day of the month is checked, so we don't
	 * end up with default behaviour of DateTime::modify():
	 * 	- 31. August 2008 - 18 Months
	 * 		- DateTime - March 3rd, 2007
	 * 		- Date   - February 28th, 2007
	 * 
	 * @param		$numMonths		int			An amount of months to subtract
	 * 
	 * @return	void
	 */
	public function subMonths($numMonths)
	{
		// Check the input for a numeric value
		if (!is_numeric($numMonths))
		{
			throw new Exception('addMonths() expects an integer');
		}
		
		// Make negative numbers not an issue
		$numMonths = abs(intval($numMonths));
		
		// Subtract the months form the current month
		$newValue = $this->_month - $numMonths;
		
		// Are we in the same year? If the result is
		// greater than 0 we are - just set the month value
		if ($newValue > 0)
		{
			$this->_month = $newValue;
		}
		// We're back to the previous year(s).
		// Year should be changed as well.
		else
		{
			// Create an array for months in reverse
			// Logic: 
			//				February - 2 months is 2 - 2 =  0 is December
			//				February - 2 months is 2 - 3 = -1 is November
			// So, October is -2, Septermber -3 and so on till January is -12
			// We take away the minus and we've got our array from 12 to 1
			// $months[0] = 12 = December
			$months = range(12, 1);
			
			// Get the absolute (positive) value of $newValue
			$newValue = abs($newValue);
			
			// Get the array position of the resulting months
			// We divide the input by 12 and the result we get
			// is 
			//		- years  - full value
			//		- months - remainder
			$monthPosition = $newValue % 12;
			$this->_month  = $months[$monthPosition];
			
			// Check, if the month position is December
			// Since array key for December is 0, everything that is not 0
			// is December.
			if ($monthPosition)
			{
				$this->_year -= ceil($newValue / 12);
			}
			// It is December - fix the December issue by adding 1
			else
			{
				$this->_year -= ceil($newValue / 12) + 1;
			}
			
			// Normalize the end of the months days
			// See checkLastDayOfMonth() for details
			$this->checkLastDayOfMonth();
			parent::setDate($this->_year, $this->_month, $this->_day);
		}
	}
	
	/**
	 * Adds x amount of years to the date
	 * 
	 * Performs an addition and at the end checks if the date
	 * is valid - i.e. all the last days of the months issues
	 * are taken care of.
	 * 
	 * @param		$numYears		int		An amount of years to add
	 * 
	 * @return	void
	 */
	public function addYears($numYears)
	{
		// Perform numerical check of the input
		if (!is_numeric($numYears) || $numYears < 1)
		{
			throw new Exception('addYears() expects a positive integer');
		}
		
		// Set the internal value of the year
		$this->_year += (int) $numYears;
		
		// Perform the sanity check of the last day of the months
		$this->checkLastDayOfMonth();
		
		// Set the date
		parent::setDate($this->_year, $this->_month, $this->_day);
	}
	
	/**
	 * Subtracts x amount of years from the date
	 * 
	 * Performs a subtraction and additional checks like
	 * the numerical input and "sanity" of the resulting date
	 * 
	 * @param		$numYears		int		An amount of years to subtract
	 */
	public function subYears($numYears)
	{
		// Perform the numerical input check
		if (!is_numeric($numYears))
		{
			throw new Exception('subYears() expects an integer');
		}
		
		// Set the internal value of the year
		$this->_year -= abs(intval($numYears));
		
		// Perform the sanity check of the resulting date
		$this->checkLastDayOfMonth();
		
		// Set the date
		parent::setDate($this->_year, $this->_month, $this->_day);
	}
	

	
	/**
	 * Performs a check if this is a leap year
	 * 
	 * Leap years occur every four years that are:
	 *   - wholly divisible by 4
	 * Exception:
	 *   - divisible by 100 - not a leap year
	 * Unless:
	 *   - also divisible by 400
	 * 
	 * @return   boolean   Is is a leap year or not?
	 */
	public function isLeap()
	{
		if ($this->_year % 400 == 0 || ($this->_year % 4 == 0 || $this->year % 100 != 0 ))
		{
			return true;
		}
		else 
		{
			return false;
		}
	}
	
	/**
	 * Defines the default output format of the Date object
	 * 
	 * Default output is Thursday, 3rd Marz, 2011
	 * 
	 * @return	string	Default output format
	 */
	public function __toString()
	{
		return $this->format('l, jS F, Y');
	}
	
	/**
	 * Defines the read-only properties to get quick access to date parts
	 * 
	 * Helps to access the parts of the date, i.e. month, year, day, or even
	 * a date in the various formats - as a read-only property.
	 * May become handy, when getFORMAT() is too lazy to type
	 * 
	 * @param string $name The prefered format to output
	 */
	public function __get($name)
	{
		switch (strtolower($name))
		{
			case 'mdy':
				return $this->format('n/j/Y');
			case 'mdy0':
				return $this->format('m/d/Y');
			case 'dmy':
				return $this->format('j/n/Y');
			case 'dmy0':
				return $this->format('d/m/Y');
			case 'mysql':
				return $this->format('Y-m-d');
			case 'fullyear':
				return $this->_year;
			case 'year':
				return $this->format('y');
			case 'month':
				return $this->_month;
			case 'month0':
				return $this->format('m');
			case 'monthname':
				return $this->format('F');
			case 'monthabbr':
				return $this->format('M');
			case 'day':
				return $this->_day;
			case 'day0':
				return $this->format('d');
			case 'dayordinal':
				return $this->format('jS');
			case 'dayname':
				return $this->format('l');
			case 'dayabbr':
				return $this->format('D');
			default:
				return 'Invalid property';
		}
	}
	
	/**
	 * Sets the day to the last valid day in the month
	 * 
	 * Performs a check - if the date given is valid. 
	 * If it is - nothing happens. Everything is OK.
	 * If it is not - the last valid day of the month should be calculated.
	 * 
	 */
	final protected function checkLastDayOfMonth()
	{
		// Perform the date check - do we need to do anything?
		if (!checkdate($this->_month, $this->_day, $this->_year))
		{
			// Months that have 30 days
			$use30 = array(4, 6, 9, 11);
			
			// Our date is one of these months
			if(in_array($this->_month, $use30))
			{
				// The last day is 30th then
				$this->_day = 30;
			}
			// Our date is none of these months - must be February then
			else 
			{
				// Is this a leap year - then the last day is 29
				// If this year is not a leap year - 28
				$this->_day = $this->isLeap() ? 29 : 28;
			}
		}
	}
}