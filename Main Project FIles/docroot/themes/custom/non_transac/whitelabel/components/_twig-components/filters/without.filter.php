<?php

/**
 * @file
 * Filter to support dummy without in the Pattern Lab.
 */

$filter = new Twig_SimpleFilter('without', fn($string) => $string);
