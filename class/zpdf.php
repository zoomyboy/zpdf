<?php

namespace Zpdf;

class Zpdf {
	use ColorManager;

	private $pdf;
	public $hasFooter;
	public $hasHeader;
	private $lineHeight = 1;
	private $defaultAlign = 'L';
	private $ulMargin = 5;
	/**
	 * @var array Die aktuelle Schriftart - Das ist ein CSS-String, der mit parseCss() geparsed wurde, also eine Key => Value-Pair-Repräsentation von CSS-Values
	 */
	public $currentFont;

	public function __construct($orientation='P', $unit='mm', $size='A4') {
		$this->pdf = new FpdfWrapper($orientation, $unit, $size);
	}

	public function setFontStyle($style) {
		$this->pdf->SetFont('', $style);
	}

	public function setFontFamily($family) {
		$this->pdf->SetFont($family);
	}

	public function setFontSize($size) {
		$this->pdf->SetFont($this->pdf->FontFamily, $this->pdf->FontStyle, $size);
	}

	public function outputToBrowser($name='doc.pdf') {
		$this->pdf->Output('I', $name, false);
	}

	public function addPage() {
		$this->pdf->AddPage();
	}

	public function setFont($family, $style, $size) {
		$this->pdf->SetFont($family, $style, $size);
	}

	public function text($txt) {
		$this->pdf->Text($this->pdf->GetX(), $this->pdf->GetY(), $txt);
	}

	private function drawMarginTopOfCurrentFont() {
		if ($this->currentFont && array_key_exists('margin-top', $this->currentFont)) {
			//var_dump ($this->currentFont['margin-top']);
			$rawValue = floatVal($this->currentFont['margin-top']);
			$this->pdf->Ln($rawValue);
		}
	}

	public function cell($txt, $w=null, $h=null, $border='', $align='', $fill='') {
		$this->drawMarginTopOfCurrentFont();
		$this->cellNoMarginTop($txt, $w, $h, $border, $align, $fill);
	}

	public function cellNoMarginTop($txt, $w=null, $h=null, $border='', $align='', $fill='') {
		if (is_null($w)) {$w = $this->pdf->GetStringWidth($txt);}
		if ($h == null) {$h = $this->pdf->FontSize * $this->lineHeight;}
		$this->pdf->Cell($w, $h, $txt, $border, 0, $align, $fill);
	}

	public function setXY($x, $y) {
		$this->pdf->SetXY($x, $y);
	}

	public function getPageWidth() {
		return $this->pdf->GetPageWidth();
	}


	public function setHeaderCallback($cb) {
		$this->pdf->headerCallback = $cb;
	}

	public function setFooterCallback($cb) {
		$this->pdf->footerCallback = $cb;
	}

	public function setPageBreakCallback($cb) {
		$this->pageBreakCallback = $cb;
	}

	/**
	 * Draws an unordered list on the page
	 *
	 * @param array|string[] $data the data to display
	 * @param callable $decode Every value is filtered through that function - useful if value has to be converted to display properly
	 * @param float $w Width of the list
	 */
	public function drawUl($data, $decode, $w=null) {
		$this->pdf->increaseIndent ($this->ulMargin);
		$posXAnf = $this->pdf->GetX();
		$marginBefore = $this->pdf->lMargin;
		$this->pdf->lMargin += $this->ulMargin;
		$this->pdf->SetXY($this->pdf->GetX() + 5, $this->pdf->GetY());
		$circleRadius = $this->pdf->fontSizePt() * 0.5;
		foreach ($data as $val) {
			if ($decode !== false) {
				$val = call_user_func($decode, $val);
			}
	
			if ($this->checkIfMultiCellCausesPageBreak($val)) {
				//Reset X Position at begin of list, so that this fits to the new Row
				$this->pdf->nextRow();
				$posXAnf = $this->pdf->GetX();
			}

			$this->pdf->Circle($this->pdf->GetX() - 1.8, $this->pdf->GetY() + 2.2, 0.7, 'F');


			while ($val != '') {
				$val = $this->multiCell($val, null, null, 0, false, false);
			}
		}

		$this->pdf->SetXY($posXAnf, $this->pdf->GetY());
		$this->pdf->lMargin -= $this->ulMargin; 
		$this->pdf->decreaseIndent ($this->ulMargin);
	}
	
	public function checkIfMultiCellCausesPageBreak($txt) {
		$bottomOfPage = $this->pdf->getBottomPosOfCurrentColumn();
		$bottomOfTextRow = $this->pdf->GetY() + $this->getMultiCellHeight($txt);
		return $bottomOfTextRow > $bottomOfPage;
	}

	public function multiCell($txt, $w=null, $h=null, $border=0, $align=false, $fill=false, $maxline=0) {
		$this->drawMarginTopOfCurrentFont();

		if ($align === false) {
			$align = $this->defaultAlign;
		}
		if (is_null($w)) {$w = 0;}
		if ($h == null) {$h = $this->defaultHeightOfTextRow();}
		return $this->pdf->multiCellMaxline($this->pdf->GetX(), $this->pdf->GetY(), $w, $h, $txt, $border, $align, $fill, $maxline);
	}

	/**
 	 * Gets the height of one line of text
	 * The text height is assumed to be fontHeight * lineHeight - which would be the normal behaviour if you pass null to the MultiCell or Cell method.
	 *
	 * @return float
	 */
	public function defaultHeightOfTextRow() {
		return $this->pdf->FontSize * $this->lineHeight;
	}

	public function getMultiCellHeight($txt) {
		return $this->pdf->getMultiCellHeight($this->pdf->getWidthOfCurrentColumn(), $this->defaultHeightOfTextRow(), $txt, null, 'J');
	}

	/**
   	 * Gets number of lines in a Multicell that contains the given text
	 * The text height is assumed to be fontHeight * lineHeight - which would be the normal behaviour if you pass null to the MultiCell or Cell method.
 	 * 
	 * @return float
	 */
	public function getLineCount($txt) {
		return $this->pdf->GetLineCount($this->pdf->getWidthOfCurrentColumn(), $txt);
	}

	public function setMargins($top, $right, $bottom, $left) {
		$this->pdf->SetMargins($left, $top, $right);
		$this->pdf->SetAutoPageBreak(false, $bottom);
	}

	public function __set($name, $value) {
		$this->pdf->{$name} = $value;
	}	

	public function setLineHeight($lh) {
		$this->lineHeight = $lh;
	}

	public function ln() {
		$this->pdf->Ln();
	}

	public function image($file, $w=-300, $h=-300) {
		$this->pdf->Image($file, $this->pdf->GetX(), $this->pdf->GetY(), $w, $h);
	}

	public function imageCss($file, $css) {
		//Größe des Bildes auslesen
		$size = getimagesize($file);
		$svh = $size[0] / $size[1];

		//Vorige Position speichern
		$x = $this->pdf->GetX();
		$y = $this->pdf->GetY();

		//CSS auslesen
		$css = parseCss($css);

		//Standardwerte für CSS-values setzen
		if (array_key_exists('align', $css)) {
			$align = $css['align'];
		} else {
			$align = 'center';
		}
		if (array_key_exists('margin-top', $css)) {
			$marginTop = $css['margin-top'];
		} else {
			$marginTop = 0;
		}
		$this->pdf->SetY($this->pdf->GetY() + $marginTop);

		if (array_key_exists('width', $css)) {
			if (strpos($css['width'], '%') == strlen($css['width'])-1) {
				$width = str_replace ('%', '', $css['width']) / 100;
				$width = $width * $this->pdf->getWidthOfCurrentColumn();
			} else {
				$width = $css['width'];
			}
		} else {
			$width = $this->pdf->getWidthOfCurrentColumn();
		}
		$height = getImageHeight($width, $svh);

		//Prüfe, ob Bild nicht die Höhe der Spalte sprengt
		$offset = $this->pdf->getOffsetTopOfCurrentColumn();
		$heightBefore = $height;
		$height = min($height, $this->pdf->getHeightOfCurrentColumn() - $this->pdf->getOffsetTopOfCurrentColumn());
		if ($height != $heightBefore) {
			//Höhe hat sich geändert - Berechne Breite neu
			$width = getImageWidth($height, $svh);
		}

		switch($align) {
			case 'right':
				$this->pdf->SetX($x + $this->pdf->getWidthOfCurrentColumn() - $width);
				break;
			case 'center':
				$this->pdf->SetX($x + (($this->pdf->getWidthOfCurrentColumn() - $width) / 2));
				break;
		}

		//Setze Position, falls Bild nicht links ausgerichtet wird
		$this->pdf->Image($file, $this->pdf->GetX(), $this->pdf->GetY(), $width, $height);

		$this->pdf->SetXY($x, $y+$height+$marginTop);
	}

	public function __get($attr) {
		return $this->pdf->{$attr};
	}

	public function setX($x) {
		$this->pdf->SetX($x);
	}

	public function setY($y) {
		$this->pdf->SetY($y);
	}

	public function rect($w, $h, $style='F') {
		$this->pdf->Rect($this->pdf->GetX(), $this->pdf->GetY(), $w, $h, $style);
	}

	public function getX() {
		return $this->pdf->GetX();
	}

	public function getY() {
		return $this->pdf->GetY();
	}

	public function pageNo() {
		return $this->pdf->PageNo();
	}

	public function getPageHeight() {
		return $this->pdf->GetPageHeight();
	}

	public function setAutoPageBreak($val, $pb=0) {
		$this->pdf->SetAutoPageBreak($val, $pb);
	}

	public function font($font) {
		$ret = parseCss($font);
		$this->currentFont = $ret;

		if (array_key_exists('family', $ret)) {
			$this->setFontFamily($ret['family']);
		}
		if (array_key_exists('style', $ret)) {
			if ($ret['style'] == 'none') {$ret['style'] = '';}
			$this->setFontStyle($ret['style']);
		}
		if (array_key_exists('size', $ret)) {
			$this->setFontSize($ret['size']);
		}
		if (array_key_exists('color', $ret)) {
			$this->setTextColor($ret['color']);
		}
	}

	public function setRows($rows) {
		$this->pdf->setRows($rows);
	}

	public function unsetRows() {
		$this->pdf->unsetRows();
	}

	public function nextRow() {
		$this->pdf->nextRow();
	}

	public function setAlign($align) {
		$this->defaultAlign = $align;
	}
}
