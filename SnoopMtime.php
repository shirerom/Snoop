<?php

require_once dirname(__FILE__).'/Snoop.php';



class SnoopMtimeExc extends Exception {}
class SnoopMtimeCompareExc extends SnoopMtimeExc {}
class SnoopMtimeOperatorExc extends SnoopMtimeExc {}



/**
 * SnoopMtime funktioniert wie Snoop mit dem Zusatz, dass man Dateien anhand
 * der letzten Modifikation aus- bzw. einschließen kann
 *
 * Anwendungsbeispiel:
 *
 *	$Snoop = new SnoopMtime("/home/codepfuscher");
 *	$Snoop->addExtension("js");
 *	$Snoop->addExtension("php");
 *	$Snoop->scanRecursively();
 *	$Snoop->setMtimeOperator("<=");  // SnoopMtime <= FileMtime
 *	$Snoop->scan();
 *
 *	foreach($Snoop as $file) {
 *		var_dump($file);
 *	}
 */
class SnoopMtime extends Snoop {

	/**
	 * Wenn true, dann wird die Verzeichnisstruktur ab $basePath rekursiv
	 * durchsucht
	 *
	 * Default: false
	 *
	 * @var boolean
	 */
	protected $mtime;

	/**
	 * enthält die akzeptierten Rückgabewerte von compareMtime(). Der Standardwert
	 * bezeichnet "kleiner-gleich"
	 * 
	 * Default: array(-1, 0);
	 *
	 * @var string
	 */
	protected $mtimeOperator;

	/**
	 * @param string $basePath Verzeichnis von dem die Suche ausgeht
	 */
	public function  __construct($basePath) {

		parent::__construct($basePath);
		
		$this->scanRecursively = true;
		$this->cutBasePath = true;
		$this->mtime = time();
		$this->mtimeOperator = array(-1, 0);
	}

	/**
	 * verarbeitet gefundene Dateien
	 *
	 * @param string $file
	 */
	protected function handleFile(&$file) {

		// TS der letzten Änderung vergleichen
		$mtimeComparison = $this->compareMtime($this->mtime, filemtime($file));

		if(!in_array($mtimeComparison, $this->mtimeOperator)) {
			return;
		}

		parent::handleFile($file);
	}

	/**
	 * setzt $mtime
	 * 
	 * @param int $timestamp 
	 */
	public function setMtime($timestamp) {
		$this->mtime = (int)$timestamp;
	}


	/**
	 * setzt den Operator für den Vergleich der mtime
	 * 
	 * @param string $operator erlaubte Werte: <, >, <=, >=
	 *
	 * @throws SnoopMtimeOperatorExc wenn das erste Zeichen in Operator nicht "<" oder ">" ist
	 */
	public function setMtimeOperator($operator) {

		// zurücksetzen
		$this->mtimeOperator = array();

		// größer oder kleiner
		if(strcmp($operator, "<") === 0) {
			$this->mtimeOperator[] = -1;
		}

		else if(strcmp($operator, ">") === 0 ) {
			$this->mtimeOperator[] = -1;
		}

		else {
			throw new SnoopMtimeOperatorExc($operator);
		}

		// ist gleich
		if(strlen($operator) == 2 and strcmp($operator, "=") == 1) {
			$this->mtimeOperator[] = 0;
		}
	}

	/**
	 * vergleicht 2 Timestamps
	 *
	 * @param int $timestamp1
	 * @param int $timestamp2
	 *
	 * @return int <br>
	 *	-1 wenn T1 < T2,
	 *	0 wenn T1 == T2,
	 *	1 wenn T1 > T2
	 *
	 * @throws SnoopMtimeCompareExc, wenn keiner der Vergleiche zutrifft
	 */
	protected function compareMtime($timestamp1, $timestamp2) {

		$timestamp1 = (int)$timestamp1;
		$timestamp2 = (int)$timestamp2;

		if($timestamp1 == $timestamp2) {
			return 0;
		}

		elseif($timestamp1 < $timestamp2) {
			return -1;
		}

		elseif($timestamp1 > $timestamp2) {
			return 1;
		}

		else {
		 throw new SnoopMtimeCompareExc();
		}
	}

}
?>
