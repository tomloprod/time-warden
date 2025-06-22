<?php

declare(strict_types=1);

namespace Tomloprod\TimeWarden\Support\Console;

/**
 * @codeCoverageIgnore
 */
final class Table
{
    private const TABLE_SEPARATOR = '---SEPARATOR---';

    private const STYLES = [
        'default' => [
            'top_left' => '+',
            'top_mid' => '+',
            'top_right' => '+',
            'mid_left' => '+',
            'mid_mid' => '+',
            'mid_right' => '+',
            'bottom_left' => '+',
            'bottom_mid' => '+',
            'bottom_right' => '+',
            'horizontal' => '-',
            'vertical' => '|',
        ],
        'box-double' => [
            'top_left' => '╔',
            'top_mid' => '╦',
            'top_right' => '╗',
            'mid_left' => '╠',
            'mid_mid' => '╬',
            'mid_right' => '╣',
            'bottom_left' => '╚',
            'bottom_mid' => '╩',
            'bottom_right' => '╝',
            'horizontal' => '═',
            'vertical' => '║',
            'column_separator' => '║',
            'separator_left' => '╠',
            'separator_mid' => '╬',
            'separator_right' => '╣',
            'separator_horizontal' => '═',
        ],
    ];

    /**
     * @var array<string>
     */
    private array $headers = [];

    /**
     * @var array<mixed>
     */
    private array $rows = [];

    private string $style = 'default';

    private string $headerTitle = '';

    private string $footerTitle = '';

    public static function separator(): string
    {
        return self::TABLE_SEPARATOR;
    }

    /**
     * @param  array<string>  $headers
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param  array<mixed>  $rows
     */
    public function setRows(array $rows): self
    {
        $this->rows = $rows;

        return $this;
    }

    public function setStyle(string $style): self
    {
        $this->style = $style;

        return $this;
    }

    public function setHeaderTitle(string $title): self
    {
        $this->headerTitle = $title;

        return $this;
    }

    public function setFooterTitle(string $title): self
    {
        $this->footerTitle = $title;

        return $this;
    }

    public function render(): string
    {
        if ($this->headers === [] && $this->rows === []) {
            return '';
        }

        $output = [];

        $styleChars = self::STYLES[$this->style] ?? self::STYLES['default'];

        // Calculate column widths
        $columnWidths = $this->calculateColumnWidths();

        // Top border
        $output[] = $this->renderTopBorder($columnWidths, $styleChars);

        // Headers
        if ($this->headers !== []) {
            $output[] = $this->renderRow($this->headers, $columnWidths, $styleChars);
            $output[] = $this->renderHeaderSeparator($columnWidths, $styleChars);
        }

        // Data rows
        foreach ($this->rows as $row) {
            if ($row === self::TABLE_SEPARATOR) {
                $output[] = $this->renderSeparator($columnWidths, $styleChars);
            } elseif (is_array($row)) {
                $output[] = $this->renderRow($row, $columnWidths, $styleChars);
            }
        }

        // Bottom border
        $output[] = $this->renderBottomBorder($columnWidths, $styleChars);

        return implode(PHP_EOL, $output);
    }

    /**
     * @return array<int>
     */
    private function calculateColumnWidths(): array
    {
        $widths = [];

        // Initialize with headers
        foreach ($this->headers as $i => $header) {
            $widths[$i] = mb_strlen((string) $header);
        }

        // Consider row content
        foreach ($this->rows as $row) {
            if ($row !== self::TABLE_SEPARATOR && is_array($row)) {
                foreach ($row as $i => $cell) {
                    $cellLength = mb_strlen((string) $cell);
                    $widths[$i] = max($widths[$i] ?? 0, $cellLength);
                }
            }
        }

        return $widths;
    }

    /**
     * @param  array<int>  $columnWidths
     * @param  array<string, string>  $styleChars
     */
    private function renderTopBorder(array $columnWidths, array $styleChars): string
    {
        $line = '';

        if ($this->headerTitle !== '') {
            // Create line with vertical separators crossing the title
            $line .= $styleChars['top_left'];

            // Calculate separator positions
            $currentPos = 0;
            $separatorPositions = [];
            foreach ($columnWidths as $i => $width) {
                $currentPos += $width + 2; // +2 for spaces
                if ($i < count($columnWidths) - 1) {
                    $separatorPositions[] = $currentPos;
                    $currentPos += 1; // +1 for separator
                }
            }

            $totalWidth = $currentPos;
            $titleWithSpaces = ' '.$this->headerTitle.' ';
            $titleLength = mb_strlen($titleWithSpaces);

            if ($titleLength <= $totalWidth) {
                $remainingWidth = $totalWidth - $titleLength;
                $leftPadding = (int) ($remainingWidth / 2);
                $rightPadding = $remainingWidth - $leftPadding;

                // Build line character by character
                for ($pos = 0; $pos < $totalWidth; $pos++) {
                    if (in_array($pos, $separatorPositions)) {
                        $line .= $styleChars['top_mid'];
                    } elseif ($pos >= $leftPadding && $pos < $leftPadding + $titleLength) {
                        $titleIndex = $pos - $leftPadding;
                        $line .= $titleWithSpaces[$titleIndex] ?? $styleChars['horizontal'];
                    } else {
                        $line .= $styleChars['horizontal'];
                    }
                }
            } else {
                // If title is too long, use normal format
                foreach ($columnWidths as $i => $width) {
                    $line .= str_repeat($styleChars['horizontal'], $width + 2); // +2 for spaces
                    if ($i < count($columnWidths) - 1) {
                        $line .= $styleChars['top_mid'];
                    }
                }
            }

            $line .= $styleChars['top_right'];
        } else {
            $line .= $styleChars['top_left'];
            foreach ($columnWidths as $i => $width) {
                $line .= str_repeat($styleChars['horizontal'], $width + 2); // +2 for spaces
                if ($i < count($columnWidths) - 1) {
                    $line .= $styleChars['top_mid'];
                }
            }
            $line .= $styleChars['top_right'];
        }

        return $line;
    }

    /**
     * @param  array<int>  $columnWidths
     * @param  array<string, string>  $styleChars
     */
    private function renderHeaderSeparator(array $columnWidths, array $styleChars): string
    {
        $line = $styleChars['mid_left'];
        foreach ($columnWidths as $i => $width) {
            $line .= str_repeat($styleChars['horizontal'], $width + 2); // +2 for spaces
            if ($i < count($columnWidths) - 1) {
                $line .= $styleChars['mid_mid'];
            }
        }

        return $line.$styleChars['mid_right'];
    }

    /**
     * @param  array<int>  $columnWidths
     * @param  array<string, string>  $styleChars
     */
    private function renderSeparator(array $columnWidths, array $styleChars): string
    {
        $separatorLeft = $styleChars['separator_left'] ?? $styleChars['mid_left'];
        $separatorMid = $styleChars['separator_mid'] ?? $styleChars['mid_mid'];
        $separatorRight = $styleChars['separator_right'] ?? $styleChars['mid_right'];
        $separatorHorizontal = $styleChars['separator_horizontal'] ?? $styleChars['horizontal'];

        $line = $separatorLeft;
        foreach ($columnWidths as $i => $width) {
            $line .= str_repeat($separatorHorizontal, $width + 2); // +2 for spaces
            if ($i < count($columnWidths) - 1) {
                $line .= $separatorMid;
            }
        }

        return $line.$separatorRight;
    }

    /**
     * @param  array<mixed>  $row
     * @param  array<int>  $columnWidths
     * @param  array<string, string>  $styleChars
     */
    private function renderRow(array $row, array $columnWidths, array $styleChars): string
    {
        $columnSeparator = $styleChars['column_separator'] ?? $styleChars['vertical'];

        $line = $styleChars['vertical']; // Left border (double)
        foreach ($columnWidths as $i => $width) {
            $cellValue = $row[$i] ?? '';
            $cell = is_scalar($cellValue) ? (string) $cellValue : '';
            $cellPadding = $width - mb_strlen($cell);
            $line .= ' '.$cell.str_repeat(' ', $cellPadding);
            if ($i < count($columnWidths) - 1) {
                $line .= ' '.$columnSeparator; // Separator between columns
            }
        } // Right border (double)

        return $line.(' '.$styleChars['vertical']);
    }

    /**
     * @param  array<int>  $columnWidths
     * @param  array<string, string>  $styleChars
     */
    private function renderBottomBorder(array $columnWidths, array $styleChars): string
    {
        $line = '';

        if ($this->footerTitle !== '') {
            // Create line with vertical separators crossing the title
            $line .= $styleChars['bottom_left'];

            // Calculate separator positions
            $currentPos = 0;
            $separatorPositions = [];
            foreach ($columnWidths as $i => $width) {
                $currentPos += $width + 2; // +2 for spaces
                if ($i < count($columnWidths) - 1) {
                    $separatorPositions[] = $currentPos;
                    $currentPos += 1; // +1 for separator
                }
            }

            $totalWidth = $currentPos;
            $titleWithSpaces = ' '.$this->footerTitle.' ';
            $titleLength = mb_strlen($titleWithSpaces);

            if ($titleLength <= $totalWidth) {
                $remainingWidth = $totalWidth - $titleLength;
                $leftPadding = (int) ($remainingWidth / 2);
                $rightPadding = $remainingWidth - $leftPadding;

                // Build line character by character
                for ($pos = 0; $pos < $totalWidth; $pos++) {
                    if (in_array($pos, $separatorPositions)) {
                        $line .= $styleChars['bottom_mid'];
                    } elseif ($pos >= $leftPadding && $pos < $leftPadding + $titleLength) {
                        $titleIndex = $pos - $leftPadding;
                        $line .= $titleWithSpaces[$titleIndex] ?? $styleChars['horizontal'];
                    } else {
                        $line .= $styleChars['horizontal'];
                    }
                }
            } else {
                // If title is too long, use normal format
                foreach ($columnWidths as $i => $width) {
                    $line .= str_repeat($styleChars['horizontal'], $width + 2); // +2 for spaces
                    if ($i < count($columnWidths) - 1) {
                        $line .= $styleChars['bottom_mid'];
                    }
                }
            }

            $line .= $styleChars['bottom_right'];
        } else {
            $line .= $styleChars['bottom_left'];
            foreach ($columnWidths as $i => $width) {
                $line .= str_repeat($styleChars['horizontal'], $width + 2); // +2 for spaces
                if ($i < count($columnWidths) - 1) {
                    $line .= $styleChars['bottom_mid'];
                }
            }
            $line .= $styleChars['bottom_right'];
        }

        return $line;
    }
}
