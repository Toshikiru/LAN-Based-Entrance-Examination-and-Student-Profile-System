<?php

namespace App\Services;

use RuntimeException;

/**
 * Extracts raw text from an uploaded question document.
 *
 * - .txt  : read natively (no dependencies)
 * - .docx : requires phpoffice/phpword   (composer require phpoffice/phpword)
 * - .pdf  : requires smalot/pdfparser     (composer require smalot/pdfparser)
 */
class QuestionDocumentExtractor
{
    public function extract(string $path, string $extension): string
    {
        return match (strtolower($extension)) {
            'txt' => (string) file_get_contents($path),
            'docx' => $this->fromDocx($path),
            'pdf' => $this->fromPdf($path),
            default => throw new RuntimeException("Unsupported file type: .{$extension}"),
        };
    }

    protected function fromDocx(string $path): string
    {
        if (! class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
            throw new RuntimeException('DOCX import needs the phpoffice/phpword package. Run: composer require phpoffice/phpword');
        }

        $doc = \PhpOffice\PhpWord\IOFactory::load($path);
        $text = '';

        foreach ($doc->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= $this->readElement($element) . "\n";
            }
        }

        return $text;
    }

    /**
     * Recursively pull text out of a PhpWord element tree, preserving line breaks.
     */
    protected function readElement($element): string
    {
        if (method_exists($element, 'getText')) {
            $value = $element->getText();
            if (is_string($value)) {
                return $value;
            }
        }

        $buffer = '';
        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $buffer .= $this->readElement($child);
                if ($child instanceof \PhpOffice\PhpWord\Element\TextBreak) {
                    $buffer .= "\n";
                }
            }
        }

        return $buffer;
    }

    protected function fromPdf(string $path): string
    {
        if (! class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new RuntimeException('PDF import needs the smalot/pdfparser package. Run: composer require smalot/pdfparser');
        }

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($path);

        return $pdf->getText();
    }
}
