<?php

/**
 * Version 2011-03-15
 */


class SnoopBasePathExc extends Exception {}



/**
 * Snoop ist ein "FileIterator" und sucht nach Dateien in einem
 * gegebenen Verzeichnis
 *
 * Das Suchergebnis kann
 *
 *	- nach Dateierweiterungen eingeschränkt werden
 *	  (sowohl unter Berücksichtigung der Groß-/Kleinschreibung oder auch nicht)
 *	- rekursiv oder nicht durchgeführt werden
 *
 * Snoop kann als Iterator verwendet werden
 *
 * Anwendungsbeispiel:
 *
 *	$Snoop = new Snoop("/home/codepfuscher");
 *	$Snoop->addExtension("js");
 *	$Snoop->addExtension("php");
 *	$Snoop->scanRecursively();
 *	$Snoop->scan();
 *
 *	foreach($Snoop as $file) {
 *		var_dump($file);
 *	}
 */
class Snoop implements Iterator {

	/**
	 * Verzeichnis von dem die Suche ausgeht
	 *
	 * @var string
	 */
	protected $basePath;

	/**
	 * Dateierweiterungen, die bei Suche berücksichtigt werden.
	 *
	 * Die Erweiterungen sind durch "|" getrennt. Wenn der String leer ist, dann
	 * werden alle Dateien berücksichtigt
	 *
	 * @var string
	 */
	protected $extensions;

	/**
	 * Der "i"-Modifier für den regulären Ausdruck, der nach den Erweiterungen
	 * sucht.
	 *
	 * Default: i → case-insensitive Suche
	 *
	 * @var boolean
	 */
	protected $i;

	/**
	 * Wenn true, dann wird die Verzeichnisstruktur ab $basePath rekursiv
	 * durchsucht
	 *
	 * Default: false
	 *
	 * @var boolean
	 */
	protected $scanRecursively;

	/**
	 * Wenn true, dann wird bei der Suche das Basisverzeichnis von den
	 * gefundenen Dateien abgeschnitten
	 *
	 * Default: false
	 *
	 * @var boolean
	 */
	protected $cutBasePath;

	/**
	 * Wenn true, dann wird bei der Suche der führende Slash von den
	 * gefundenen Dateien abgeschnitten
	 *
	 * Default: false
	 *
	 * @var boolean
	 */
	protected $cutSlash;

	/**
	 * Liste der Dateien, die bei der Suche gefunden wurden
	 *
	 * @var array
	 */
	protected $files;

	/**
	 * Index in der Liste $files für die Iterator-Implementierung
	 *
	 * @var integer
	 */
	protected $iteratorIndex;

	/**
	 * @param string $basePath Verzeichnis von dem die Suche ausgeht
	 */
	public function  __construct($basePath) {
		$this->basePath = $basePath;
		$this->extensions = "";
		$this->scanRecursively = false;
		$this->useExtensionsCaseSensitive = false;
		$this->cutBasePath = false;
		$this->cutSlash = false;
		$this->files = array();
		$this->i = "i";
		$this->iteratorIndex = 0;
		clearstatcache();
	}

	/**
	 * sucht nach Dateien
	 *
	 * @return array Liste der gefundenen Dateien
	 * @throws SnoopBasePathExc wenn das Basisverzeichnis nicht existiert
	 */
	public function scan() {

		// Basisverzeichnis muss existieren
		if(!is_dir($this->basePath)) {
			throw new SnoopBasePathExc($this->basePath);
		}

		$this->scanPath($this->basePath);

		return $this->files;

	}

	/**
	 * sucht in einem Verzeichnis nach Dateien
	 *
	 * wenn $this->scanRecursively gleich true ist wird rekursiv gesucht
	 *
	 * @param string $path Verzeichnis in dem gesucht wird
	 */
	protected function scanPath(&$path) {

		// alle Dateien des Verzeichnisses lesen
		$files = glob("{$path}/*");

		foreach($files as &$file) {

			// Verzeichnis gefunden
			if(is_dir($file) and $this->scanRecursively === true) {
				$this->scanPath($file);
			}

			// Datei gefunden
			elseif(is_file($file)) {
				$this->handleFile($file);
			}
		}
	}

	/**
	 * verarbeitet gefundene Dateien
	 *
	 * !!!
	 * diese Methode eignet sich gut zum Überschreiben
	 * !!!
	 *
	 * @param string $file
	 */
	protected function handleFile(&$file) {

		// Erweiterungen berücksichtigen
		if(strlen($this->extensions) == 0 or preg_match("/({$this->extensions})$/{$this->i}", $file)) {

			// Basisverzeichnis abschneiden und anhängen
			if($this->cutBasePath === true) {

				if($this->cutSlash === true) {
					$this->files[] = substr($file, strlen($this->basePath) + 1);
				}

				else {
					$this->files[] = substr($file, strlen($this->basePath));
				}
			}

			// Basisverzeichnis nur anhängen
			else {
				$this->files[] = $file;
			}
		}
	}

	/**
	 * fügt eine Dateierweiterung in die Whilelist ein
	 *
	 * @param string $extension
	 */
	public function addExtension($extension) {

		// erste Erweiterung
		if(strlen($this->extensions) == 0) {
			$this->extensions = $extension;
		}

		// alle weiteren Erweiterungen
		else {
			$this->extensions .= "|{$extension}";
		}

	}

	/**
	 * wird diese Methode vor dem aufrufen von scan() aufgerufen, dann
	 * wird das Basisverzeichnis rekursiv durchsucht
	 */
	public function scanRecursively() {
		$this->scanRecursively = true;
	}

	/**
	 * wird diese Methode vor dem aufrufen von scan() aufgerufen, dann
	 * findet der Abgleich mit den Dateierweiterungen unter der Berücksichtigung
	 * der Groß-/Kleinschreibung statt
	 */
	public function useExtesionsCaseSensitive() {
		$this->i = "";
	}

	/**
	 * wird diese Methode vor dem aufrufen von scan() aufgerufen, dann
	 * wird das Basisverzeichnis von den gefundenen Dateien abgeschnitten
	 *
	 * Funktionsweise: Anzahl der Zeichen, die das Basisverzeichnis hat
	 * wird von den gefundenen Dateien abgeschnitten
	 */
	public function cutBasePath() {
		$this->cutBasePath = true;
	}

	/**
	 * wird diese Methode vor dem aufrufen von scan() aufgerufen, dann
	 * wird führende Slash von Dateinamen abgetrennt
	 *
	 * Funktioniert nur in Verbindung mit cutBasePath()
	 */
	public function cutSlash() {
		$this->cutSlash = true;
	}

	// ---------------------------------------------------------------------
	// Iterator-Methden
	//
	// Dokumentation: http://de.php.net/manual/de/class.iterator.php
	// ---------------------------------------------------------------------

	public function rewind() {
		$this->iteratorIndex = 0;
	}

	public function current() {
		return $this->files[$this->iteratorIndex];
	}

	public function key() {
		return $this->iteratorIndex;
	}

	public function next() {
		++$this->iteratorIndex;
	}

	public function valid() {
		return isset ($this->files[$this->iteratorIndex]);
	}

}
?>
