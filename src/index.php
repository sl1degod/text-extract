<?php

require '../vendor/autoload.php';

use Spatie\PdfToText\Pdf;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

function extractTextFromDocx($filePath) {
    try {
        $phpWord = WordIOFactory::load($filePath);
        $text = '';


        foreach ($phpWord->getSections() as $section) {

            foreach ($section->getElements() as $element) {

                if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                    foreach ($element->getRows() as $row) {
                        foreach ($row->getCells() as $cell) {

                            $cellText = '';
                            foreach ($cell->getElements() as $cellElement) {
                                if (method_exists($cellElement, 'getText')) {
                                    $cellText .= $cellElement->getText() . ' ';
                                }
                            }
                            $text .= trim($cellText) . "\t";
                        }
                        $text .= "\n";
                    }
                    $text .= "\n";
                }
                elseif (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                }
            }
        }

        return $text;
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

function extractTextFromPdf($filePath)
{
    try {
        $binPath = 'C:/Program Files/poppler-24.02.0/Library/bin/pdftotext.exe';
        $text = (new Pdf($binPath))
            ->setPdf($filePath)
            ->text();

        if ($text == null) {
            $tempDir = sys_get_temp_dir();
            try {

                $files = glob($tempDir . '/page*.jpg');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                $command = "pdftoppm -r 300 -jpeg " . escapeshellarg($filePath) . " " . escapeshellarg($tempDir . '/page');

                shell_exec($command);

                $text = '';

                $pageCount = 1;
                while (true) {
                    $jpgFile = $tempDir . "/page-{$pageCount}.jpg";
                    if (!file_exists($jpgFile)) {
                        break;
                    }
                    $language = 'rus + eng';
                    $output = shell_exec("tesseract " . escapeshellarg($jpgFile) . " stdout -l " . $language);

                    $text .= $output . "\n\n";

                    unlink($jpgFile);

                    $pageCount++;
                }
                return $text;
            } catch (Exception $e) {
                return "Error: " . $e->getMessage();
            }
        }
    }
    catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
    return $text;
}

function extractTextFromImage($filePath, $language = 'rus+eng')
{
    $output = shell_exec("tesseract " . escapeshellarg($filePath) . " stdout -l " . $language);
    return $output;
}

function handleUpload($file)
{
    $filePath = $file['tmp_name'];
    $fileName = $file['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    switch ($fileExtension) {
        case 'pdf':
            return extractTextFromPdf($filePath);
        case 'docx':
            return extractTextFromDocx($filePath);
        case 'jpg' || 'jpeg':
            return extractTextFromImage($filePath);
        default:
            return "Unsupported file format.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $text = handleUpload($_FILES['file']);
    echo $text;
} else {
    echo 'Please upload a document.';
}