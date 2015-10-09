<?php

$XMLHandler = new XMLHandler;
$XMLHandler->compareFiles();

/**
 * Compares two XML Files for changes then updates the DB with it's changes.
 *
 * @version 1.0
 */ 
class XMLHandler
{
	protected $currentXMLFileLocation = 'xml_file/current.xml';
	protected $oldXMLFileLocation = 'xml_file/old.xml';

	protected $responseError = 0;
	protected $responseMessage = 'Action Successful. Differences found!';

	public function __construct()
	{
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
	}

	/**
	 * Gets two XML Files then compares for changes
	 *
	 * @return null
	 */ 
	public function compareFiles()
	{	
		$currentXMLFileLocation = $this->currentXMLFileLocation;
		$oldXMLFileLocation = $this->oldXMLFileLocation;

		if(!file_exists($currentXMLFileLocation)) {
			$this->responseError = 1;
			$this->responseMessage = 'current.xml does not exist!';
			return;
		}

		if(!file_exists($oldXMLFileLocation)) {
			if(is_writable('xml_file')) {
				copy($currentXMLFileLocation, $oldXMLFileLocation);
				$this->responseError = 0;
				$this->responseMessage = 'old.xml doesn\'t exist. Nothing to compare.';
			} else {
				$this->responseError = 1;
				$this->responseMessage = 'XML Directory isn\'t writable. Can\'t create old.xml.';
			}
			return;
		}

		$currentXMLFile = file_get_contents($currentXMLFileLocation);
		$oldXMLFile = file_get_contents($oldXMLFileLocation);

		$currentFileArray = $this->convertXMLToArray($currentXMLFile);
		$oldFileArray = $this->convertXMLToArray($oldXMLFile);

		$differenceArray = $this->arrayRecursiveDiff($currentFileArray, $oldFileArray);
		
		if($differenceArray == null || empty($differenceArray)) {
			$this->responseError = 0;
			$this->responseMessage = 'Action Successful. No differences found.';
		} else {
			if(is_writable('xml_file')) {
				copy($currentXMLFileLocation, $oldXMLFileLocation);
				echo '<strong>Differences:</strong><br /><pre>';
				print_r($differenceArray);
				echo '</pre>';
				//input to database
			} else {
				$this->responseError = 1;
				$this->responseMessage = 'XML Directory isn\'t writable. Can\'t copy old.xml.';
			}
		}
	}

	/**
	 * Con
	 *
	 * @param  XMLObject $XMLFile An XML File
	 * @return Array $fileArray
	 */ 
	protected function convertXMLToArray($XMLFile)
	{
		$simpleXMLElements = new SimpleXMLElement($XMLFile);

		$fileArray = [
			'users' => []
		];

		foreach($simpleXMLElements as $simpleXMLElement) {
			$userId = (int) $simpleXMLElement->attributes()->id;

			$fileArray['users'][$userId] = [
				'first_name' => (string) $simpleXMLElement->first_name,
				'last_name' => (string) $simpleXMLElement->last_name,
				'positive_points' => (int) $simpleXMLElement->positive_points,
				'negative_points' => (int) $simpleXMLElement->negative_points
			];
		}

		return $fileArray;
	}

	/**
	 * Compares two PHP arrays for changes
	 *
	 * @param  Array $array1 The first array
	 * @param Array $array2 The array that gets checked against $array1
	 * @return Array $differenceArray
	 */ 
	public function arrayRecursiveDiff($array1, $array2)
	{
		$differenceArray = [];
		foreach ($array1 as $key => $value) {
			if (array_key_exists($key, $array2)) {
				if (is_array($value)) {
					$recursiveDifference = $this->arrayRecursiveDiff($value, $array2[$key]);
					if (count($recursiveDifference)) { $differenceArray[$key] = $recursiveDifference; }
				} else {
					if ($value != $array2[$key]) {
						$differenceArray[$key] = $value;
					}
				}
			} else {
				$differenceArray[$key] = $value;
			}
		}
		return $differenceArray;
	} 

	/**
	 * Creates and echos a Json response
	 *
	 * @return JsonArray $responseArray
	 */ 
    public function __destruct()
    {
    	$responseArray = [
    		'error' => $this->responseError,
    		'message' => $this->responseMessage
    	];

    	echo json_encode($responseArray);
    }
}