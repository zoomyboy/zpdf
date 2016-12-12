<?php

function hu($txt) {
	return iconv('UTF-8', 'windows-1252', $txt);
}
