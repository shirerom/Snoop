<?php



require_once dirname(__FILE__).'/Snoop.php';


/**
 * SnoopFilter funktioniert wie Snoop mit dem Zusatz, dass man Dateien anhand
 * eines Textfilters, der auf Name und Pfad (ohne $basePath) angewendet wird
 *
 * Anwendungsbeispiel:
 *
 *	$Snoop = new SnoopFilter("/home/codepfuscher");
 *	$Snoop->addExtension("js");
 *	$Snoop->addFilter("UberCoder");
 *	$Snoop->scanRecursively();
 *	$Snoop->scan();
 *
 *	foreach($Snoop as $file) {
 *		var_dump($file);
 *	}
 */
class SnoopFilter extends Snoop {

	protected $filter;

	public function __construct($basePath) {
		parent::__construct($basePath);
	}

	public function addFilter($filter) {

		// erste Erweiterung
		if(strlen($this->filter) == 0) {
			$this->filter = $filter;
		}

		// alle weiteren Erweiterungen
		else {
			$this->filter .= "|{$filter}";
		}

	}

	public function handleFile(&$file) {

		// Filter berücksichtigen
		if(strlen($this->filter) == 0 or preg_match("/({$this->filter})/{$this->i}", $file)) {
			parent::handleFile($file);
		}

	}
}

?>
