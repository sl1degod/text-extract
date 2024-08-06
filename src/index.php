<?php

require '../vendor/autoload.php';

use Spatie\PdfToText\Pdf;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

// die('я тут бро');

function extractTextFromDocx($filePath)
{
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
                } elseif (method_exists($element, 'getText')) {
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
    $binPath = 'C:\Program Files\poppler-24.07.0\Library\bin\pdftotext.exe';
    $tempDir = sys_get_temp_dir();

    try {
        $pdfToText = new Pdf($binPath);
        var_dump($filePath);
        die($filePath);
        $text = $pdfToText->setPdf($filePath)->text();
        $command = "pdftoppm -r 300 -jpeg " . escapeshellarg($filePath) . " " . escapeshellarg($tempDir . '/page');
        shell_exec($command);

        $pageCount = 1;
        while (true) {
            $jpgFile = $tempDir . "/page-{$pageCount}.jpg";
            if (!file_exists($jpgFile)) {
                break;
            }

            $language = 'rus+eng';
            $output = shell_exec("tesseract " . escapeshellarg($jpgFile) . " stdout -l " . $language);

            $text .= "\n\n" . $output;

            unlink($jpgFile);
            $pageCount++;
        }

        return $text;
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
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
    var_dump($_FILES['file']);
    exit;
    $text = handleUpload($_FILES['file']);
    echo $text;
} else {
    echo 'Please upload a document.';
}
