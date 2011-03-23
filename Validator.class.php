<?php
namespace aeHelpers;

use \Exception;

/**
 * Simplifies the interface of the php build-in validators
 * 
 * Ever felt overwhelmed with those flags: FILTER_VALIDATE_INT, 
 * FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION etc.?
 * Then this class is for you.
 * Simply define the filter type through the user-friendly 
 * interface and take the advantage of the versatile 
 * build-in validators.
 * Three stages of the class inner logic:
 * 	- Instantiation
 * 		- Retrieve the input from the $_POST or $_GET
 * 		- Chech the required fields
 * 	-	Preparation of the validation methods
 * 		- Create an array with the filter constants, flags and options for each element
 * 		- Genereate error messages
 * 	- Validation
 * 		- Check that the validation test has been set for each required field
 * 		- Pass the array of filter constants, flags and options to the filter_input_array()
 * 		- Store the filtered results, error messages and names of the required fields that haven't been filled out
 * 
 * @author 	Andrey Esaulov
 * @license GPL
 * 
 * @version 1.0
 */
class Validator
{
	protected $_inputType;
	protected $_submitted;
	protected $_required;
	protected $_filterArgs;
	protected $_filtered;
	protected $_missing;
	protected $_errors;
	
	/**
	 * Performs initial checks and sets required fields and input type
	 * 
	 * Several checks are performed:
	 * 	- Is core Filter function activated?
	 * 	- Is the parameter $required an array? Even if we need to filter one value,
	 * it still has to be in an array.
	 * Input type is set and required data is checked.
	 * Filter arguments and errors are initialized.
	 * 
	 * @param 	array 	$required		An array with the required fields
	 * @param 	string 	$inputType	A type of input. Default is post
	 */
	public function __construct($required = array(), $inputType = 'post')
	{
		// Check if the core Filter Functions are activated
		if (!function_exists('filter_list'))
		{
			throw new Exception('Validator requires the Filter Functions to be activated. Use PHP >= 5.2 or PECL extensions');
		}
		
		// Check if the $required is an array
		if (!is_null($required) && !is_array($required))
		{
			throw new Exception('$required needs to be an array. Even if one value is checked');
		}
		
		// Set the required fields and input type
		$this->_required = $required;
		$this->setInputType($inputType);
		
		// Perform the check of the required fields
		if ($this->_required)
		{
			$this->checkRequired();
		}
		
		// Initialize filter arguments and errors
		$this->_filterArgs = array();
		$this->_errors		 = array();
	}
	
	/**
	 * Check if the filed contains an integer
	 * 
	 * Performs a chech for an integer.
	 * First the internal method is run to be sure, only one filter 
	 * has been applied.
	 * Than the $_filterArgs array is filled with the field name,
	 * FILTER_VALIDATE_INT flag and optional max/min range values.
	 * 
	 * @param 	string 	$fieldName 	Name of the field to be tested
	 * @param 	int 		$min				Optional minimum range value
	 * @param 	int 		$max				Optional maximum range value
	 */
	public function isInt($fieldName, $min = null, $max = null)
	{
		// Check for the One-Filter-One-Validator policy
		$this->checkDuplicateFilter($fieldName);
		
		// Set the type of the filter
		$this->_filterArgs[$fieldName] = array('filter' => FILTER_VALIDATE_INT);
		
		// Set the additional options
		if (is_int($min))
		{
			$this->_filterArgs[$fieldName]['options']['min_range'] = $min;
		}
		if (is_int($max))
		{
			$this->_filterArgs[$fieldName]['options']['max_range'] = $max;
		}
	}

	/**
	 * Checks if the field contains a floating point number
	 * 
	 * Performs a check for a floating point number. 
	 * Additional checks for the input values: 
	 * 	- decimal point should be either point or comma
	 * 	- use the thousand separator?
	 * 
	 * @param 		string 		$fieldName								Name of the field the filter is applied to
	 * @param 		string 		$decimalPoint							US or EU decimal point?
	 * @param 		boolean 	$allowThousandSeparator		US thousand separator?
	 */
	public function isFloat($fieldName, $decimalPoint = '.', $allowThousandSeparator = true)
	{
		// Check for the One-Filter-One-Validator policy
		$this->checkDuplicateFilter($fieldName);
		
		// Check if the decimal point is a valid character
		if ($decimalPoint != '.' && $decimalPoint != ',')
		{
			throw new Exception('isFloat() expects a decimal point to be either a comma or comma');
		}
		
		// Set the type of filter
		$this->_filterArgs[$fieldName] = array('filter' => FILTER_VALIDATE_FLOAT);
		
		// Set the additional options
		$this->_filterArgs[$fieldName]['options']['decimal'] = $decimalPoint;
		
		if ($allowThousandSeparator)
		{
			$this->_filterArgs[$fieldName]['flags'] = FILTER_FLAG_ALLOW_THOUSAND;
		}
	}	
	
	/**
	 * Checks if a field contains a numeric array
	 * 
	 * Used to mimic the FILTER_REQUIRE_ARRAY constant of the original PHP core
	 * filters. 
	 * 
	 * @param string $fieldName	The name of the field
	 * @param boolean $allowDecimalFractions 	Are the decimal fractions allowed? - default is true
	 * @param string $decimalPoint	The representation of decimal point - default is '.'
	 * @param boolean $allowThousandSeparator	Is the thousand separator allowed? - default is true
	 */
	public function isNumericArray($fieldName, $allowDecimalFractions = true, $decimalPoint = '.', $allowThousandSeparator = true)
	{
		// Check for one filter - one field policy
		$this->checkDuplicateFilter($fieldName);
		
		// Check the validity of decimal point sign
		if ($decimalPoint != '.' && $decimalPoint != ',')
		{
			throw new Exception('Decimal point must be a comma or a period in isNumericArray()');
		}
		
		// Add the arguments to the global filter array
		$this->_filterArgs[$fieldName] = array(
			'filter' 	=> FILTER_VALIDATE_FLOAT,
			'flags'  	=> FILTER_REQUIRE_ARRAY,
			'options' => array('decimal' => $decimalPoint)
		);
		
		// Additional arguments if provided by the user
		if ($allowDecimalFractions)
		{
			$this->_filterArgs[$fieldName]['flags'] |= FILTER_FLAG_ALLOW_FRACTION;
		}
		
		if ($allowThousandSeparator)
		{
			$this->_filterArgs[$fieldName]['flags'] |= FILTER_FLAG_ALLOW_THOUSAND;
		}
		
	}
	
	/**
	 * Checks if a field contains an e-mail address
	 * 
	 * Uses the internal FILTER_VALIDATE_EMAIL constant
	 * 
	 * @param string $fieldName	A filed name to check
	 */
	public function isEmail($fieldName)
	{
		// One field - One filter policy
		$this->checkDuplicateFilter($fieldName);
		
		// Add the field to the global filter array
		$this->_filterArgs[$fieldName] = FILTER_VALIDATE_EMAIL;
	}
	
	/**
	 * Checks if a field contains a full URL-Address
	 * 
	 * Sets the internal flags to FILTER_FLAG_SCHEME_REQUIRED, FILTER_FLAG_HOST_REQUIRED and
	 * FILTER_FLAG_PATH_REQUIRED
	 * Optionally the URL is checked for the query sting (inde.php?bla=tra&tra=bla)
	 * 
	 * @param string $fieldName	The name of the field to be checked.
	 * @param boolean $queryStringRequired	Is the query (?bla=tra) required? - default is false
	 */
	public function isFullURL($fieldName, $queryStringRequired = false)
	{
		// One field - One filter policy
		$this->checkDuplicateFilter($fieldName);
		
		// Populate the global filter array
		$this->_filterArgs[$fieldName] = array(
			'filter' 	=>	FILTER_VALIDATE_URL,
			'flags' 	=> FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_PATH_REQUIRED
		);
		
		// Set the optinal query string flag
		$this->_filterArgs[$fieldName]['flags'] = FILTER_FLAG_QUERY_REQUIRED;
	}
	
	/**
	 * Checks if a filed contains an URL-Address
	 * 
	 * URL might not contain scheme and host part. To check the full URL use
	 * Validator::isFullURL() method instead.
	 * 
	 * @param string $fieldName The name of the filed to be checked
	 * @param boolean $queryStringRequired Is the query string (index.php?bla=asdf&asdf=blah) required? - default is false
	 */ 
	public function isURL($fieldName, $queryStringRequired = false)
	{
		// One filter - One field policy
		$this->checkDuplicateFilter($fieldName);
		
		// Populate the global filter array
		$this->_filterArgs[$fieldName]['filter'] = array(
			'filter' => FILTER_VALIDATE_URL,
		);
		
		// Set the optional query string flag
		if ($queryStringRequired)
		{
			$this->_filterArgs[$fieldName]['flags'] = FILTER_FLAG_QUERY_REQUIRED;
		}
	}
	
	/**
	 * Sets the type of the input and populates the $_submitted with values
	 * 
	 * POST and GET are two types of values that are accepted by the validator.
	 * Everything else rises an error. 
	 * Once the POST/GET have been checked, the values inside these superglobals
	 * are signed to $_submitted (unfiltered) array.
	 * 
	 * @param string $inputType	Type of the input to use
	 */
	protected function setInputType($inputType)
	{
		switch(strtolower($inputType))
		{
			case 'post':
				$this->_inputType = INPUT_POST;
				$this->_submitted = $_POST;
				break;
			case 'get':
				$this->_inputType = INPUT_GET;
				$this->_submitted = $_GET;
				break;
			default:
				throw new Exception('Input type is not valid. Accepted input types are POST and GET');
		}
	}
	
	/**
	 * Checks if all the required fields have been filled out
	 * 
	 * Algorithm is simle - method compared two arrays - 
	 * keys of the submitted data against the names of the 
	 * required fileds.
	 * In the end the missing fields are stored in $_missing internal
	 * variable.
	 * 
	 */
	protected function checkRequired()
	{
		// Initialize the fields that have been field out
		$filledOutFields = array();
		
		// Which fields have been field out?
		foreach ($this->_submitted as $name => $value)
		{
			// Make sure the field is not just several white spaces
			$value = is_array($value) ? $value : trim($value);
			
			// Set the names of filled fields
			if (!empty($value))
			{
				$filledOutFields[] = $name;
			}
		}
		
		// Calculate the missing fields
		$this->_missing = array_diff($this->_required, $filledOutFields);
		
	}
	
	/**
	 * Ensures that only one filter is applied to a field
	 * 
	 * For each field a multidimensional array $_filterArgs is created, which holds the constants,
	 * flags and option for a filter to be applied to a field. This methods checks,
	 * that only one filter is app	lied to a field, by running the check of this array.
	 * This internal method is called by every filter before the filter is run.
	 * 
	 * @param string $fieldName	The name of the field to be checked
	 */
	protected function checkDuplicateFilter($fieldName)
	{
		if (isset($this->_filterArgs[$fieldName]))
		{
			throw new Exception('A filter has allready been set for this field. One field - One filter policy!');
		}
	}
}