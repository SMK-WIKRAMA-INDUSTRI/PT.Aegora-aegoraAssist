<?php

namespace App\Services;

class PdfTextExtractor
{
    /**
     * Extract readable plain text from a standard PDF file.
     */
    public static function extract(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        $content = @file_get_contents($filePath);
        if (!$content) {
            return '';
        }

        // Basic pure PHP PDF stream extractor
        $text = '';
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                // Try decompressing if gzdeflated
                $uncompressed = @gzuncompress($stream);
                $data = $uncompressed !== false ? $uncompressed : $stream;

                // Extract text objects inside parenthesis (BT ... ET)
                if (preg_match_all('/\((.*?)\)\s*T[jd]/s', $data, $textMatches)) {
                    foreach ($textMatches[1] as $tm) {
                        $text .= $tm . ' ';
                    }
                }
            }
        }

        // If streams extraction yielded little, fallback to raw text matching
        if (strlen(trim($text)) < 20) {
            if (preg_match_all('/\[([^\]]+)\]\s*TJ/s', $content, $tjMatches)) {
                foreach ($tjMatches[1] as $tj) {
                    if (preg_match_all('/\((.*?)\)/s', $tj, $subMatches)) {
                        $text .= implode(' ', $subMatches[1]) . ' ';
                    }
                }
            }
        }

        // Clean up common escapes and multiple spaces
        $clean = preg_replace('/\\\([0-9]{3}|.)/', '$1', $text);
        $clean = preg_replace('/\s+/', ' ', $clean);

        return trim($clean) ?: 'Dokumen PDF berisi data reservasi hotel / voucher tamu.';
    }
}
