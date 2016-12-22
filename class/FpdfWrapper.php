<?php

namespace Zpdf;

class FpdfWrapper extends \FPDF {
	use MaxlineMultiCell;
	use Circles;
	public $headerCallback = false;
	public $footerCallback = false;
	public $pageBreakCallback = false;
	private $alreadyPublishedRows = 0;
	private $pageMarginsBefore;
	private $rowsPadding;
	private $indent = 0;
	private $hooks = [
		'beforePageBreak' => []
	];

	public function __construct($orientation, $unit, $size) {
		parent::__construct($orientation, $unit, $size);
		$this->headerCallback = function() {};
		$this->footerCallback = function() {};
		$this->pageBreakCallback = function() {};
		$this->unsetRows();
	}

	public function fontSizePt() {
		return $this->FontSizePt;
	}

	public function SetBottomMargin($bottom) {
		$this->bMargin = $bottom;
	}

	public function __set($prop, $name) {
		$this->{$prop} = $name;
	}

	public function __get($prop) {
		return $this->{$prop};
	}

	public function Header() {
		call_user_func($this->headerCallback, $this);
	}
	
	public function nextRow() {
		//Add a new page if this was the last row on the current page
		if ($this->getCurrentRow() == count($this->rowsPadding)-1) {
			$this->AddPage();
		}
		$this->alreadyPublishedRows++;
		$this->prepareColumn();
	}

	public function AcceptPageBreak() {
		foreach ($this->hooks['beforePageBreak'] as $hook) {
			if (false === call_user_func($hook, $this)) {
				return false;
			}
		}
		//Add a new page if this was the last row on the current page
		if ($this->getCurrentRow() == count($this->rowsPadding)-1) {
			$this->AddPage();
		}
		$this->alreadyPublishedRows++;
		$this->prepareColumn();

		call_user_func($this->pageBreakCallback, $this);

		return false;
	}

	public function Footer() {
		call_user_func($this->footerCallback, $this);
	}

	public function addBeforePageBreak($name, $callable) {
		$this->hooks['beforePageBreak'][$name] = $callable;
	}

	public function deleteBeforePageBreak($name) {
		unset ($this->hooks['beforePageBreak'][$name]);
		$this->hooks['beforePageBreak'] = array_values ($this->hooks['befoprePageBreak']);
	}

	/**
	 * Enable Row style of document
	 *
	 * $rowsPadding[]
	 * 		$rowsPadding[0][0] float Top-Margin von erster Spalte
	 * 		$rowsPadding[0][0] float Right-Margin von erster Spalte
	 * 		$rowsPadding[0][0] float Bottom-Margin von erster Spalte
	 * 		$rowsPadding[0][0] float Left-Margin von erster Spalte
	 * 		$rowsPadding[1][0] float Top-Margin von zweiter Spalte
	 * 		.....
	 *
	 * @param array|float[][] $rowsPadding Padding in jeder Richtung für jede einzelne Spalte
	 */
	public function setRows($rowsPadding) {
		$this->rowsPadding = $rowsPadding;
		$this->alreadyPublishedRows = 0;
		if (!empty(count(($this->pageMarginsBefore)))) {
			$this->SetMargins($this->pageMarginsBefore[3], $this->pageMarginsBefore[0], $this->pageMarginsBefore[1]);
		} else {
			$this->pageMarginsBefore = [$this->tMargin, $this->rMargin, $this->bMargin, $this->lMargin];
		}

		//Set Position to first Column
		$this->prepareColumn();
	}

	private function prepareColumn() {
		$this->SetXY($this->columnCornerX(0) + $this->indent, $this->columnCornerY(0));
		$this->SetMargins($this->columnCornerX(0) + $this->indent, $this->columnCornerY(0), abs($this->columnCornerX(1)-$this->GetPageWidth()));
		$this->SetAutoPageBreak(false, $this->GetPageHeight() - $this->columnCornerY(2));
	}


	/**
	 * Disable Row style of document
	 *
	 * Setzt eine einzige Spalte mit 0 Margin
	 */
	public function unsetRows() {
		$this->rowsPadding = [[0,0,0,0]];
	}

	/**
	 * Gets index of currently processed row
	 *
	 * @return int
	 */
	private function getCurrentRow() {
		return $this->alreadyPublishedRows % count($this->rowsPadding);
	}

	/**
	 * Liest die Breite der Spalten INKL! ihres Paddings
	 *
	 * @return float
	 */
	private function getColumnWidth() {
		return ($this->GetPageWidth() - $this->pageMarginsBefore[1] - $this->pageMarginsBefore[3]) / count ($this->rowsPadding);
	}

	/**
	 * Gets X Position of the corner of a specific column
	 *
	 * @param int $cornerIndex Index of corner (0=topleft, 1=topright, 2=botomright, 3=bottomleft)
	 * @param int $colNum Number of column (0 means left column, ColumnCount-1 means right column)
	 * 					  This is optional and will default to the current row.
	 *
	 * @return float
	 */
	private function columnCornerX($cornerIndex, $colNum = false) {
		if ($colNum === false) {$colNum = $this->getCurrentRow();}
		
		$beginLeft = $this->pageMarginsBefore[3] + $this->getColumnWidth() * $colNum;
		$endRight = $this->GetPageWidth() - $this->pageMarginsBefore[1] - ((count($this->rowsPadding)-1-$colNum) * $this->getColumnWidth());

		switch($cornerIndex) {
			case 0:		//top left
			case 3:		//bottom left
				return $beginLeft + $this->rowsPadding[$colNum][3];
			case 1:		//top right
			case 2: 	//bottom right
				return $endRight - $this->rowsPadding[$colNum][1];
		}
	}

	/**
	 * Gets Y Position of the corner of a specific column
	 *
	 * @param int $cornerIndex Index of corner (0=topleft, 1=topright, 2=botomright, 3=bottomleft)
	 * @param int $colNum Number of column (0 means left column, ColumnCount-1 means right column)
	 * 					  This is optional and will default to the current row.
	 *
	 * @return float
	 */
	private function columnCornerY($cornerIndex, $colNum = false) {
		if ($colNum === false) {$colNum = $this->getCurrentRow();}
		
		$beginTop = $this->pageMarginsBefore[3] + $this->getColumnWidth() * $colNum;
		$endRight = $this->GetPageWidth() - $this->pageMarginsBefore[1] - ((count($this->rowsPadding)-1-$colNum) * $this->getColumnWidth());

		switch($cornerIndex) {
			case 0:		//top left
			case 1:		//top left
				return $this->pageMarginsBefore[0] + $this->rowsPadding[$colNum][0];
			case 2:		//bottom right
			case 3: 	//bottom left
				return $this->GetPageHeight() - $this->pageMarginsBefore[2] - $this->rowsPadding[$colNum][2];
		}
	}

	/**
	 * Liest die innere Breite der aktuellen Spalte (EXKL(!) des Paddings
	 *
	 * @return float
	 */
	public function getWidthOfCurrentColumn() {
		$row = $this->getCurrentRow();
		return $this->getColumnWidth() - $this->rowsPadding[$row][1] - $this->rowsPadding[$row][3];
	}

	/**
	 * Liest die innere Höhe der aktuellen Spalte (EXKL(!) des Paddings
	 *
	 * @return float
	 */
	public function getHeightOfCurrentColumn() {
		$row = $this->getCurrentRow();
		return $this->GetPageHeight() - $this->rowsPadding[$row][0] - $this->rowsPadding[$row][2];
	}

	public function getOffsetTopOfCurrentColumn() {
		$row = $this->getCurrentRow();
		return $this->GetY() - $this->rowsPadding[$row][0];
	}

	/**
	 * Increase indent - e.g. for UL's left padding at page break
	 *
	 * @param int $indent 
	 */
	public function increaseIndent($indent) {
		$this->indent += $indent;
	}

	/**
	 * Decrease indent - e.g. for UL's left padding at page break
	 *
	 * @param int $indent 
	 */
	public function decreaseIndent($indent) {
		$this->indent -= $indent;
	}
}
