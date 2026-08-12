<?php

namespace App\Services\Export;

/**
 * Wraps a cell value with a background/text color so XlsxWriter can render
 * it as a colored status chip instead of plain text — used to mirror the
 * on-screen attendance badges (Present/Absent/Late/etc.) in the Excel export.
 */
class ColoredCell
{
    /**
     * @param  string  $background  6-digit hex, no '#' (e.g. 'E7F6ED')
     * @param  string  $color  6-digit hex, no '#' (e.g. '0F7D45')
     */
    public function __construct(
        public mixed $value,
        public string $background,
        public string $color,
    ) {
    }
}
