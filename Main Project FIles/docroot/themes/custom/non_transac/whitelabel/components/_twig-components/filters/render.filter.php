<?php

/**
 * @file
 * Filter to support dummy render in the Pattern Lab.
 */

$filter = new Twig_SimpleFilter('render', fn($string) => $string);
