<?php

namespace Zpdf;

trait ColorManager {
	/**
	 * Sets draw color from an array
	 *
	 * @param string $color Color in HEX-Format
	 */
	public function setDrawColor($color) {
		$color = hex2rgb($color);
		$this->pdf->SetDrawColor($color[0], $color[1], $color[2]);
	}

	/**
	 * Sets Text color from an array
	 *
	 * @param string $color Color in HEX-Format
	 */
	public function setTextColor($color) {
		$color = hex2rgb($color);
		$this->pdf->SetTextColor($color[0], $color[1], $color[2]);
	}

	/**
	 * Sets fill color from an array
	 *
	 * @param string $color Color in HEX-Format
	 */
	public function setFillColor($color) {
		$color = hex2rgb($color);
		$this->pdf->SetFillColor($color[0], $color[1], $color[2]);
	}
}
