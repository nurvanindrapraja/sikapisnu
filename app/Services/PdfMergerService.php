<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

class PdfMergerService
{
    /**
     * Gabungkan beberapa file PDF menjadi satu file PDF tunggal.
     *
     * Menggunakan urutan engine terbaik yang tersedia di server:
     * 1. Native CLI (qpdf, pdfunite, gs)
     * 2. Python3 (pypdf / PyPDF2 / PyMuPDF)
     * 3. Pure PHP PDF Merger (mendukung PDF 1.0–1.7 termasuk XRef & ObjStm streams)
     *
     * @param  array<int, string>  $inputPaths
     */
    public function merge(array $inputPaths, string $outputPath): string
    {
        $validPaths = array_values(array_filter(
            $inputPaths,
            static fn (string $path): bool => $path !== '' && is_file($path) && is_readable($path) && filesize($path) > 0
        ));

        if ($validPaths === []) {
            throw new RuntimeException('Tidak ada berkas PDF yang valid untuk digabungkan.');
        }

        $outputDir = dirname($outputPath);
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        if (count($validPaths) === 1) {
            if (! copy($validPaths[0], $outputPath)) {
                throw new RuntimeException('Gagal menyalin berkas PDF.');
            }

            return $outputPath;
        }

        if ($this->tryMergeWithNativeTools($validPaths, $outputPath)) {
            return $outputPath;
        }

        if ($this->tryMergeWithPython($validPaths, $outputPath)) {
            return $outputPath;
        }

        return $this->mergeWithPurePhp($validPaths, $outputPath);
    }

    /**
     * Coba gabungkan PDF dengan utilitas sistem native (qpdf / pdfunite / gs).
     *
     * @param  array<int, string>  $inputPaths
     */
    protected function tryMergeWithNativeTools(array $inputPaths, string $outputPath): bool
    {
        if (! function_exists('proc_open')) {
            return false;
        }

        $candidates = [
            array_merge(['qpdf', '--empty', '--pages'], $inputPaths, ['--', $outputPath]),
            array_merge(['pdfunite'], $inputPaths, [$outputPath]),
            array_merge(['gs', '-dBATCH', '-dNOPAUSE', '-q', '-sDEVICE=pdfwrite', '-sOutputFile='.$outputPath], $inputPaths),
        ];

        foreach ($candidates as $command) {
            try {
                $process = new Process($command);
                $process->setTimeout(60);
                $process->run();

                if ($process->isSuccessful() && $this->isValidPdfFile($outputPath)) {
                    return true;
                }
            } catch (\Throwable $e) {
                Log::debug('PDF native merge attempt skipped: '.$e->getMessage());
            }

            if (is_file($outputPath)) {
                @unlink($outputPath);
            }
        }

        return false;
    }

    /**
     * Coba gabungkan PDF dengan Python3 (pypdf / PyPDF2 / PyMuPDF).
     *
     * @param  array<int, string>  $inputPaths
     */
    protected function tryMergeWithPython(array $inputPaths, string $outputPath): bool
    {
        if (! function_exists('proc_open')) {
            return false;
        }

        $pythonScript = <<<'PY'
import sys

out_path = sys.argv[1]
in_paths = sys.argv[2:]

try:
    from pypdf import PdfReader, PdfWriter
    writer = PdfWriter()
    for p in in_paths:
        reader = PdfReader(p)
        if reader.is_encrypted:
            reader.decrypt("")
        writer.append(reader)
    with open(out_path, "wb") as fh:
        writer.write(fh)
    sys.exit(0)
except ImportError:
    pass

try:
    import fitz
    doc = fitz.open()
    for p in in_paths:
        with fitz.open(p) as src:
            if src.is_encrypted:
                src.authenticate("")
            doc.insert_pdf(src)
    doc.save(out_path)
    doc.close()
    sys.exit(0)
except ImportError:
    pass

try:
    from PyPDF2 import PdfReader, PdfWriter
    writer = PdfWriter()
    for p in in_paths:
        reader = PdfReader(p)
        if reader.is_encrypted:
            reader.decrypt("")
        for page in reader.pages:
            writer.add_page(page)
    with open(out_path, "wb") as fh:
        writer.write(fh)
    sys.exit(0)
except ImportError:
    sys.exit(127)
PY;

        try {
            $process = new Process(array_merge(['python3', '-c', $pythonScript, $outputPath], $inputPaths));
            $process->setTimeout(60);
            $process->run();

            if ($process->isSuccessful() && $this->isValidPdfFile($outputPath)) {
                return true;
            }
        } catch (\Throwable $e) {
            Log::debug('PDF Python merge attempt failed: '.$e->getMessage());
        }

        if (is_file($outputPath)) {
            @unlink($outputPath);
        }

        return false;
    }

    /**
     * Gabungkan beberapa PDF secara murni dengan PHP (tanpa binary/ekstensi eksternal).
     * Mendukung PDF 1.0–1.7 termasuk classic xref table, incremental updates,
     * Cross-Reference Streams (/Type /XRef), dan Object Streams (/Type /ObjStm).
     *
     * @param  array<int, string>  $inputPaths
     */
    public function mergeWithPurePhp(array $inputPaths, string $outputPath): string
    {
        $nextObjNum = 3; // 1 = Catalog, 2 = Pages root
        $mergedPageObjNums = [];
        $mergedObjects = [];

        foreach ($inputPaths as $path) {
            $raw = file_get_contents($path);
            if ($raw === false || ! str_contains(substr($raw, 0, 1024), '%PDF-')) {
                throw new RuntimeException("Berkas bukan PDF yang valid: {$path}");
            }

            $parsed = $this->parsePdfDocument($raw);
            $objects = $parsed['objects'];
            $rootObjNum = $parsed['root'];

            if ($rootObjNum === null || ! isset($objects[$rootObjNum])) {
                throw new RuntimeException("Struktur Catalog (/Root) tidak ditemukan pada berkas PDF: {$path}");
            }

            $rootDict = $this->extractTopDictionary($objects[$rootObjNum]);
            $pagesObjNum = $this->extractIndirectRef($rootDict, 'Pages');
            if ($pagesObjNum === null || ! isset($objects[$pagesObjNum])) {
                throw new RuntimeException("Struktur /Pages tidak ditemukan pada berkas PDF: {$path}");
            }

            $pageTreeNodes = [];
            $leafPages = [];
            $this->collectLeafPages($objects, $pagesObjNum, [], $leafPages, $pageTreeNodes);

            if ($leafPages === []) {
                throw new RuntimeException("Tidak ada halaman yang dapat dibaca pada berkas PDF: {$path}");
            }

            // Siapkan body leaf page dengan atribut terwarisi (Resources, MediaBox, CropBox, Rotate)
            $preparedBodies = [];
            $leafPageSet = [];
            foreach ($leafPages as $leaf) {
                $pageNum = $leaf['objNum'];
                $leafPageSet[$pageNum] = true;
                $preparedBodies[$pageNum] = $this->injectInheritedPageAttributes(
                    $objects[$pageNum],
                    $leaf['inherited']
                );
            }

            // Kumpulkan seluruh objek yang dapat dijangkau dari halaman-halaman leaf (BFS)
            $reachable = [];
            $queue = array_keys($leafPageSet);
            foreach ($queue as $qNum) {
                $reachable[$qNum] = true;
            }

            while ($queue !== []) {
                $current = array_shift($queue);
                $body = $preparedBodies[$current] ?? ($objects[$current] ?? null);
                if ($body === null) {
                    continue;
                }

                $nonStream = $this->splitObjectStream($body)['prefix'];
                if (isset($leafPageSet[$current])) {
                    $nonStream = $this->stripKeyReference($nonStream, 'Parent');
                }
                $nonStream = $this->stripKeyReference($nonStream, 'StructParents');

                foreach ($this->findAllIndirectRefs($nonStream) as $refObjNum) {
                    if ($refObjNum === $rootObjNum || isset($pageTreeNodes[$refObjNum])) {
                        continue;
                    }
                    if (! isset($reachable[$refObjNum]) && isset($objects[$refObjNum])) {
                        $reachable[$refObjNum] = true;
                        $queue[] = $refObjNum;
                    }
                }
            }

            // Buat pemetaan nomor objek lama -> baru
            $objMap = [];
            foreach (array_keys($reachable) as $oldObjNum) {
                $objMap[$oldObjNum] = $nextObjNum++;
            }

            foreach ($leafPages as $leaf) {
                $mergedPageObjNums[] = $objMap[$leaf['objNum']];
            }

            // Tulis ulang seluruh referensi objek tidak langsung & simpan objek hasil
            foreach ($objMap as $oldObjNum => $newObjNum) {
                $body = $preparedBodies[$oldObjNum] ?? $objects[$oldObjNum];
                $parts = $this->splitObjectStream($body);
                $prefix = $parts['prefix'];

                if (isset($leafPageSet[$oldObjNum])) {
                    $prefix = $this->replaceParentReference($prefix, 2);
                }

                $prefix = $this->rewriteIndirectReferences($prefix, $objMap);
                $mergedObjects[$newObjNum] = $prefix.$parts['streamPart'];
            }
        }

        $kidsRefs = implode(' ', array_map(static fn (int $n): string => "{$n} 0 R", $mergedPageObjNums));
        $pageCount = count($mergedPageObjNums);

        $allObjects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => "<< /Type /Pages /Kids [ {$kidsRefs} ] /Count {$pageCount} >>",
        ];

        foreach ($mergedObjects as $num => $content) {
            $allObjects[$num] = $content;
        }

        ksort($allObjects);

        $pdf = "%PDF-1.7\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];
        $maxObjNum = max(array_keys($allObjects));

        for ($i = 1; $i <= $maxObjNum; $i++) {
            if (! isset($allObjects[$i])) {
                continue;
            }
            $offsets[$i] = strlen($pdf);
            $pdf .= "{$i} 0 obj\n".trim($allObjects[$i])."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $size = $maxObjNum + 1;
        $pdf .= "xref\n0 {$size}\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= $maxObjNum; $i++) {
            if (isset($offsets[$i])) {
                $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
            } else {
                $pdf .= "0000000000 65535 f \n";
            }
        }

        $pdf .= "trailer\n<< /Size {$size} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF\n";

        if (file_put_contents($outputPath, $pdf) === false) {
            throw new RuntimeException("Gagal menulis berkas PDF gabungan ke: {$outputPath}");
        }

        return $outputPath;
    }

    protected function isValidPdfFile(string $path): bool
    {
        if (! is_file($path) || filesize($path) < 100) {
            return false;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 16);
        fclose($handle);

        return is_string($header) && str_contains($header, '%PDF-');
    }

    /**
     * @return array{objects: array<int, string>, root: ?int}
     */
    protected function parsePdfDocument(string $data): array
    {
        $xrefEntries = []; // objNum => ['type' => 1|2, 'field1' => int, 'field2' => int]
        $rootObjNum = null;

        $startXrefPos = strrpos($data, 'startxref');
        if ($startXrefPos !== false && preg_match('/startxref\s+(\d+)/', $data, $m, 0, $startXrefPos)) {
            $xrefOffset = (int) $m[1];
            $visited = [];
            $this->parseXrefChain($data, $xrefOffset, $xrefEntries, $rootObjNum, $visited);
        }

        $objects = [];

        // 1. Ekstrak objek normal dari offset xref
        foreach ($xrefEntries as $objNum => $entry) {
            if ($entry['type'] === 1 && $entry['field1'] > 0) {
                $extracted = $this->extractIndirectObjectAtOffset($data, $entry['field1']);
                if ($extracted !== null) {
                    $objects[$objNum] = $extracted['body'];
                }
            }
        }

        // 2. Ekstrak objek terkompresi dari Object Streams (/ObjStm)
        $objStmCache = [];
        foreach ($xrefEntries as $objNum => $entry) {
            if ($entry['type'] === 2) {
                $stmObjNum = $entry['field1'];
                if (! isset($objStmCache[$stmObjNum])) {
                    $stmBody = $objects[$stmObjNum] ?? null;
                    if ($stmBody === null && isset($xrefEntries[$stmObjNum]) && $xrefEntries[$stmObjNum]['type'] === 1) {
                        $ext = $this->extractIndirectObjectAtOffset($data, $xrefEntries[$stmObjNum]['field1']);
                        $stmBody = $ext['body'] ?? null;
                    }
                    $objStmCache[$stmObjNum] = $stmBody !== null ? $this->extractObjectsFromObjStm($stmBody) : [];
                }

                if (isset($objStmCache[$stmObjNum][$objNum])) {
                    $objects[$objNum] = $objStmCache[$stmObjNum][$objNum];
                }
            }
        }

        // 3. Fallback scan langsung apabila dokumen memiliki tabel xref tidak standar
        if ($objects === [] || $rootObjNum === null) {
            $scanned = $this->scanAllTopLevelObjects($data);
            foreach ($scanned as $objNum => $body) {
                if (! isset($objects[$objNum])) {
                    $objects[$objNum] = $body;
                }
            }

            if ($rootObjNum === null) {
                foreach ($objects as $objNum => $body) {
                    $prefix = $this->splitObjectStream($body)['prefix'];
                    if (preg_match('/\/Type\s*\/Catalog\b/', $prefix)) {
                        $rootObjNum = $objNum;
                        break;
                    }
                }
            }
        }

        return [
            'objects' => $objects,
            'root' => $rootObjNum,
        ];
    }

    /**
     * @param  array<int, array{type: int, field1: int, field2: int}>  $xrefEntries
     * @param  array<int, bool>  $visited
     */
    protected function parseXrefChain(
        string $data,
        int $offset,
        array &$xrefEntries,
        ?int &$rootObjNum,
        array &$visited
    ): void {
        if ($offset <= 0 || $offset >= strlen($data) || isset($visited[$offset])) {
            return;
        }
        $visited[$offset] = true;

        $chunk = ltrim(substr($data, $offset, 32));
        if (str_starts_with($chunk, 'xref')) {
            $this->parseClassicXrefTable($data, $offset, $xrefEntries, $rootObjNum, $visited);

            return;
        }

        // PDF 1.5+ Cross-Reference Stream object
        $extracted = $this->extractIndirectObjectAtOffset($data, $offset);
        if ($extracted === null) {
            return;
        }

        $body = $extracted['body'];
        $dict = $this->extractTopDictionary($body);
        if (! preg_match('/\/Type\s*\/XRef\b/', $dict)) {
            return;
        }

        if ($rootObjNum === null) {
            $rootObjNum = $this->extractIndirectRef($dict, 'Root');
        }

        $w = $this->extractIntArray($dict, 'W');
        $size = $this->extractIntValue($dict, 'Size') ?? 0;
        $index = $this->extractIntArray($dict, 'Index');
        if ($index === []) {
            $index = [0, $size];
        }

        $decodedStream = $this->decodeObjectStream($body, $dict);
        if ($decodedStream !== null && count($w) === 3) {
            $w0 = $w[0];
            $w1 = $w[1];
            $w2 = $w[2];
            $entryLen = $w0 + $w1 + $w2;
            $pos = 0;
            $streamLen = strlen($decodedStream);

            for ($i = 0; $i + 1 < count($index); $i += 2) {
                $startObj = $index[$i];
                $count = $index[$i + 1];

                for ($j = 0; $j < $count; $j++) {
                    if ($pos + $entryLen > $streamLen) {
                        break 2;
                    }

                    $type = $w0 === 0 ? 1 : $this->readBigEndianInt($decodedStream, $pos, $w0);
                    $field1 = $w1 === 0 ? 0 : $this->readBigEndianInt($decodedStream, $pos + $w0, $w1);
                    $field2 = $w2 === 0 ? 0 : $this->readBigEndianInt($decodedStream, $pos + $w0 + $w1, $w2);
                    $pos += $entryLen;

                    $objNum = $startObj + $j;
                    if (! isset($xrefEntries[$objNum]) && ($type === 1 || $type === 2)) {
                        $xrefEntries[$objNum] = [
                            'type' => $type,
                            'field1' => $field1,
                            'field2' => $field2,
                        ];
                    }
                }
            }
        }

        $prevOffset = $this->extractIntValue($dict, 'Prev');
        if ($prevOffset !== null) {
            $this->parseXrefChain($data, $prevOffset, $xrefEntries, $rootObjNum, $visited);
        }
    }

    /**
     * @param  array<int, array{type: int, field1: int, field2: int}>  $xrefEntries
     * @param  array<int, bool>  $visited
     */
    protected function parseClassicXrefTable(
        string $data,
        int $offset,
        array &$xrefEntries,
        ?int &$rootObjNum,
        array &$visited
    ): void {
        $trailerPos = strpos($data, 'trailer', $offset);
        if ($trailerPos === false) {
            return;
        }

        $xrefText = substr($data, $offset, $trailerPos - $offset);
        $lines = preg_split('/\r\n|\r|\n/', trim($xrefText)) ?: [];
        $lineCount = count($lines);
        $i = 0;

        if ($i < $lineCount && trim($lines[$i]) === 'xref') {
            $i++;
        }

        while ($i < $lineCount) {
            $line = trim($lines[$i]);
            if ($line === '') {
                $i++;

                continue;
            }

            if (preg_match('/^(\d+)\s+(\d+)$/', $line, $sub)) {
                $startObj = (int) $sub[1];
                $count = (int) $sub[2];
                $i++;

                for ($j = 0; $j < $count && $i < $lineCount; $j++, $i++) {
                    $entryLine = trim($lines[$i]);
                    if (preg_match('/^(\d{10})\s+(\d{5})\s+([nf])/', $entryLine, $em)) {
                        $objNum = $startObj + $j;
                        if ($em[3] === 'n' && ! isset($xrefEntries[$objNum])) {
                            $xrefEntries[$objNum] = [
                                'type' => 1,
                                'field1' => (int) $em[1],
                                'field2' => (int) $em[2],
                            ];
                        }
                    }
                }
            } else {
                $i++;
            }
        }

        $trailerSlice = substr($data, $trailerPos, 4096);
        $trailerDict = $this->extractTopDictionary($trailerSlice);

        if ($rootObjNum === null) {
            $rootObjNum = $this->extractIndirectRef($trailerDict, 'Root');
        }

        $xrefStmOffset = $this->extractIntValue($trailerDict, 'XRefStm');
        if ($xrefStmOffset !== null) {
            $this->parseXrefChain($data, $xrefStmOffset, $xrefEntries, $rootObjNum, $visited);
        }

        $prevOffset = $this->extractIntValue($trailerDict, 'Prev');
        if ($prevOffset !== null) {
            $this->parseXrefChain($data, $prevOffset, $xrefEntries, $rootObjNum, $visited);
        }
    }

    /**
     * @return ?array{objNum: int, body: string}
     */
    protected function extractIndirectObjectAtOffset(string $data, int $offset): ?array
    {
        $len = strlen($data);
        if ($offset < 0 || $offset >= $len) {
            return null;
        }

        $headerSlice = substr($data, $offset, 64);
        if (! preg_match('/^\s*(\d+)\s+(\d+)\s+obj\b/', $headerSlice, $m)) {
            return null;
        }

        $objNum = (int) $m[1];
        $bodyStart = $offset + strlen($m[0]);
        $endPos = $this->findEndObjOffset($data, $bodyStart);
        if ($endPos === null) {
            return null;
        }

        return [
            'objNum' => $objNum,
            'body' => trim(substr($data, $bodyStart, $endPos - $bodyStart)),
        ];
    }

    protected function findEndObjOffset(string $data, int $bodyStart): ?int
    {
        $len = strlen($data);
        $nextEndObj = strpos($data, 'endobj', $bodyStart);
        if ($nextEndObj === false) {
            return null;
        }

        // Cek apakah ada kata kunci "stream" sebelum "endobj"
        $streamPos = strpos($data, 'stream', $bodyStart);
        if ($streamPos !== false && $streamPos < $nextEndObj) {
            // Pastikan "stream" diikuti EOL (\r\n atau \n) dan berada setelah ">>"
            $dictBeforeStream = substr($data, $bodyStart, $streamPos - $bodyStart);
            if (str_contains($dictBeforeStream, '>>')) {
                $afterStream = $streamPos + 6;
                if ($afterStream < $len && $data[$afterStream] === "\r" && ($afterStream + 1) < $len && $data[$afterStream + 1] === "\n") {
                    $streamDataStart = $afterStream + 2;
                } elseif ($afterStream < $len && ($data[$afterStream] === "\n" || $data[$afterStream] === "\r")) {
                    $streamDataStart = $afterStream + 1;
                } else {
                    $streamDataStart = null;
                }

                if ($streamDataStart !== null) {
                    $declaredLength = $this->extractDirectIntValue($dictBeforeStream, 'Length');
                    if ($declaredLength !== null && $declaredLength >= 0 && ($streamDataStart + $declaredLength) < $len) {
                        $endStreamPos = strpos($data, 'endstream', $streamDataStart + $declaredLength);
                        if ($endStreamPos !== false) {
                            $afterEndStream = strpos($data, 'endobj', $endStreamPos + 9);
                            if ($afterEndStream !== false) {
                                return $afterEndStream;
                            }
                        }
                    }

                    $endStreamPos = strpos($data, 'endstream', $streamDataStart);
                    if ($endStreamPos !== false) {
                        $afterEndStream = strpos($data, 'endobj', $endStreamPos + 9);
                        if ($afterEndStream !== false) {
                            return $afterEndStream;
                        }
                    }
                }
            }
        }

        return $nextEndObj;
    }

    /**
     * @return array<int, string>
     */
    protected function scanAllTopLevelObjects(string $data): array
    {
        $objects = [];
        $offset = 0;
        $len = strlen($data);

        while ($offset < $len && preg_match('/(?:^|[\r\n])\s*(\d+)\s+(\d+)\s+obj\b/', $data, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $objNum = (int) $m[1][0];
            $bodyStart = $m[0][1] + strlen($m[0][0]);
            $endPos = $this->findEndObjOffset($data, $bodyStart);
            if ($endPos === null) {
                break;
            }

            $objects[$objNum] = trim(substr($data, $bodyStart, $endPos - $bodyStart));
            $offset = $endPos + 6;
        }

        return $objects;
    }

    /**
     * @return array<int, string>
     */
    protected function extractObjectsFromObjStm(string $stmBody): array
    {
        $dict = $this->extractTopDictionary($stmBody);
        $n = $this->extractIntValue($dict, 'N');
        $first = $this->extractIntValue($dict, 'First');

        if ($n === null || $first === null || $n <= 0) {
            return [];
        }

        $decoded = $this->decodeObjectStream($stmBody, $dict);
        if ($decoded === null || strlen($decoded) < $first) {
            return [];
        }

        $header = trim(substr($decoded, 0, $first));
        $tokens = preg_split('/\s+/', $header) ?: [];
        if (count($tokens) < $n * 2) {
            return [];
        }

        $entries = [];
        for ($i = 0; $i < $n; $i++) {
            $entries[] = [
                'objNum' => (int) $tokens[$i * 2],
                'offset' => $first + (int) $tokens[$i * 2 + 1],
            ];
        }

        $result = [];
        $totalLen = strlen($decoded);
        for ($i = 0; $i < $n; $i++) {
            $start = $entries[$i]['offset'];
            $end = ($i + 1 < $n) ? $entries[$i + 1]['offset'] : $totalLen;
            if ($start <= $totalLen && $end >= $start) {
                $result[$entries[$i]['objNum']] = trim(substr($decoded, $start, $end - $start));
            }
        }

        return $result;
    }

    protected function decodeObjectStream(string $body, string $dict): ?string
    {
        $parts = $this->splitObjectStream($body);
        $streamPart = $parts['streamPart'];
        if ($streamPart === '') {
            return null;
        }

        $startPos = strpos($streamPart, 'stream');
        $endPos = strrpos($streamPart, 'endstream');
        if ($startPos === false || $endPos === false || $endPos <= $startPos) {
            return null;
        }

        $dataStart = $startPos + 6;
        if (isset($streamPart[$dataStart]) && $streamPart[$dataStart] === "\r" && isset($streamPart[$dataStart + 1]) && $streamPart[$dataStart + 1] === "\n") {
            $dataStart += 2;
        } elseif (isset($streamPart[$dataStart]) && ($streamPart[$dataStart] === "\n" || $streamPart[$dataStart] === "\r")) {
            $dataStart += 1;
        }

        $rawStream = substr($streamPart, $dataStart, $endPos - $dataStart);
        $rawStream = rtrim($rawStream, "\r\n");

        if (preg_match('/\/Filter\s*(?:\[\s*)?\/FlateDecode\b/', $dict)) {
            $inflated = @gzuncompress($rawStream);
            if ($inflated === false) {
                $inflated = @gzinflate(substr($rawStream, 2));
            }
            if ($inflated === false) {
                return null;
            }
            $rawStream = $inflated;
        }

        $predictor = $this->extractIntValue($dict, 'Predictor');
        if ($predictor !== null && $predictor >= 10) {
            $columns = $this->extractIntValue($dict, 'Columns') ?? 1;
            $rawStream = $this->unapplyPngPredictor($rawStream, $columns);
        }

        return $rawStream;
    }

    protected function unapplyPngPredictor(string $data, int $columns): string
    {
        $rowSize = $columns + 1;
        $len = strlen($data);
        $output = '';
        $prevRow = array_fill(0, $columns, 0);

        for ($offset = 0; $offset + $rowSize <= $len; $offset += $rowSize) {
            $filter = ord($data[$offset]);
            $currRow = [];

            for ($col = 0; $col < $columns; $col++) {
                $byte = ord($data[$offset + 1 + $col]);
                $left = $col > 0 ? $currRow[$col - 1] : 0;
                $up = $prevRow[$col];
                $upLeft = $col > 0 ? $prevRow[$col - 1] : 0;

                $val = match ($filter) {
                    0 => $byte,
                    1 => ($byte + $left) & 0xFF,
                    2 => ($byte + $up) & 0xFF,
                    3 => ($byte + intdiv($left + $up, 2)) & 0xFF,
                    4 => ($byte + $this->paethPredictor($left, $up, $upLeft)) & 0xFF,
                    default => $byte,
                };

                $currRow[$col] = $val;
                $output .= chr($val);
            }

            $prevRow = $currRow;
        }

        return $output;
    }

    protected function paethPredictor(int $a, int $b, int $c): int
    {
        $p = $a + $b - $c;
        $pa = abs($p - $a);
        $pb = abs($p - $b);
        $pc = abs($p - $c);

        if ($pa <= $pb && $pa <= $pc) {
            return $a;
        }
        if ($pb <= $pc) {
            return $b;
        }

        return $c;
    }

    protected function readBigEndianInt(string $bytes, int $offset, int $length): int
    {
        $val = 0;
        for ($i = 0; $i < $length; $i++) {
            $val = ($val << 8) | ord($bytes[$offset + $i]);
        }

        return $val;
    }

    /**
     * @return array{prefix: string, streamPart: string}
     */
    protected function splitObjectStream(string $body): array
    {
        $len = strlen($body);
        $depth = 0;
        $inString = 0;
        $dictEnd = null;

        for ($i = 0; $i < $len; $i++) {
            $ch = $body[$i];

            if ($inString > 0) {
                if ($ch === '\\') {
                    $i++;
                } elseif ($ch === '(') {
                    $inString++;
                } elseif ($ch === ')') {
                    $inString--;
                }

                continue;
            }

            if ($ch === '(') {
                $inString = 1;
            } elseif ($ch === '<' && ($i + 1) < $len && $body[$i + 1] === '<') {
                $depth++;
                $i++;
            } elseif ($ch === '>' && ($i + 1) < $len && $body[$i + 1] === '>') {
                $depth--;
                $i++;
                if ($depth === 0) {
                    $dictEnd = $i + 1;
                    break;
                }
            }
        }

        if ($dictEnd !== null) {
            $rest = substr($body, $dictEnd);
            if (preg_match('/^\s*stream(?:\r\n|\n|\r)/', $rest)) {
                return [
                    'prefix' => substr($body, 0, $dictEnd),
                    'streamPart' => $rest,
                ];
            }
        }

        return [
            'prefix' => $body,
            'streamPart' => '',
        ];
    }

    protected function extractTopDictionary(string $body): string
    {
        $prefix = $this->splitObjectStream($body)['prefix'];
        $start = strpos($prefix, '<<');
        if ($start === false) {
            return '';
        }

        $len = strlen($prefix);
        $depth = 0;
        $inString = 0;

        for ($i = $start; $i < $len; $i++) {
            $ch = $prefix[$i];

            if ($inString > 0) {
                if ($ch === '\\') {
                    $i++;
                } elseif ($ch === '(') {
                    $inString++;
                } elseif ($ch === ')') {
                    $inString--;
                }

                continue;
            }

            if ($ch === '(') {
                $inString = 1;
            } elseif ($ch === '<' && ($i + 1) < $len && $prefix[$i + 1] === '<') {
                $depth++;
                $i++;
            } elseif ($ch === '>' && ($i + 1) < $len && $prefix[$i + 1] === '>') {
                $depth--;
                $i++;
                if ($depth === 0) {
                    return substr($prefix, $start, $i - $start + 1);
                }
            }
        }

        return $prefix;
    }

    protected function extractIndirectRef(string $dict, string $key): ?int
    {
        if (preg_match('/\/'.preg_quote($key, '/').'\s+(\d+)\s+\d+\s+R\b/', $dict, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    protected function extractIntValue(string $dict, string $key): ?int
    {
        if (preg_match('/\/'.preg_quote($key, '/').'\s+(\d+)(?!\s+\d+\s+R\b)/', $dict, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    protected function extractDirectIntValue(string $dict, string $key): ?int
    {
        return $this->extractIntValue($dict, $key);
    }

    /**
     * @return array<int, int>
     */
    protected function extractIntArray(string $dict, string $key): array
    {
        if (! preg_match('/\/'.preg_quote($key, '/').'\s*\[([^\]]*)\]/', $dict, $m)) {
            return [];
        }

        preg_match_all('/\d+/', $m[1], $nums);

        return array_map(static fn (string $v): int => (int) $v, $nums[0]);
    }

    /**
     * @param  array<int, string>  $objects
     * @param  array<string, string>  $inherited
     * @param  array<int, array{objNum: int, inherited: array<string, string>}>  $leafPages
     * @param  array<int, bool>  $pageTreeNodes
     */
    protected function collectLeafPages(
        array $objects,
        int $nodeObjNum,
        array $inherited,
        array &$leafPages,
        array &$pageTreeNodes
    ): void {
        if (! isset($objects[$nodeObjNum])) {
            return;
        }

        $dict = $this->extractTopDictionary($objects[$nodeObjNum]);

        foreach (['Resources', 'MediaBox', 'CropBox', 'Rotate'] as $attr) {
            $value = $this->extractDictionaryEntryValue($dict, $attr);
            if ($value !== null) {
                $inherited[$attr] = $value;
            }
        }

        if (preg_match('/\/Type\s*\/Pages\b/', $dict)) {
            $pageTreeNodes[$nodeObjNum] = true;
            $kidsRaw = $this->extractDictionaryEntryValue($dict, 'Kids');
            if ($kidsRaw !== null) {
                // Jika /Kids berupa referensi tak langsung ke array
                if (preg_match('/^\s*(\d+)\s+\d+\s+R\s*$/', $kidsRaw, $km)) {
                    $kidsRaw = $objects[(int) $km[1]] ?? '';
                }
                if (preg_match_all('/(\d+)\s+\d+\s+R\b/', $kidsRaw, $kidMatches)) {
                    foreach ($kidMatches[1] as $kidObjNum) {
                        $this->collectLeafPages($objects, (int) $kidObjNum, $inherited, $leafPages, $pageTreeNodes);
                    }
                }
            }

            return;
        }

        $leafPages[] = [
            'objNum' => $nodeObjNum,
            'inherited' => $inherited,
        ];
    }

    /**
     * Ekstrak nilai entri kamus tingkat teratas untuk kunci tertentu (mendukung dict bersarang, array, referensi, angka).
     */
    protected function extractDictionaryEntryValue(string $dict, string $key): ?string
    {
        if (! str_starts_with($dict, '<<') || ! str_ends_with($dict, '>>')) {
            return null;
        }

        $inner = substr($dict, 2, -2);
        $len = strlen($inner);
        $depthDict = 0;
        $depthArr = 0;
        $inString = 0;
        $target = '/'.$key;
        $targetLen = strlen($target);

        for ($i = 0; $i < $len; $i++) {
            $ch = $inner[$i];

            if ($inString > 0) {
                if ($ch === '\\') {
                    $i++;
                } elseif ($ch === '(') {
                    $inString++;
                } elseif ($ch === ')') {
                    $inString--;
                }

                continue;
            }

            if ($ch === '(') {
                $inString = 1;

                continue;
            }

            if ($ch === '<' && ($i + 1) < $len && $inner[$i + 1] === '<') {
                $depthDict++;
                $i++;

                continue;
            }

            if ($ch === '>' && ($i + 1) < $len && $inner[$i + 1] === '>') {
                $depthDict--;
                $i++;

                continue;
            }

            if ($ch === '[') {
                $depthArr++;

                continue;
            }

            if ($ch === ']') {
                $depthArr--;

                continue;
            }

            if ($depthDict === 0 && $depthArr === 0 && substr($inner, $i, $targetLen) === $target) {
                $afterKey = $i + $targetLen;
                if ($afterKey < $len && ! preg_match('/[\s\[<\(\/]/', $inner[$afterKey])) {
                    continue;
                }

                // Lewati spasi setelah kunci
                $valStart = $afterKey;
                while ($valStart < $len && ctype_space($inner[$valStart])) {
                    $valStart++;
                }

                if ($valStart >= $len) {
                    return null;
                }

                return $this->readPdfValueToken($inner, $valStart);
            }
        }

        return null;
    }

    protected function readPdfValueToken(string $text, int $start): string
    {
        $len = strlen($text);
        $ch = $text[$start];

        // Nested dictionary << ... >>
        if ($ch === '<' && ($start + 1) < $len && $text[$start + 1] === '<') {
            $depth = 0;
            $inStr = 0;
            for ($i = $start; $i < $len; $i++) {
                $c = $text[$i];
                if ($inStr > 0) {
                    if ($c === '\\') {
                        $i++;
                    } elseif ($c === '(') {
                        $inStr++;
                    } elseif ($c === ')') {
                        $inStr--;
                    }

                    continue;
                }
                if ($c === '(') {
                    $inStr = 1;
                } elseif ($c === '<' && ($i + 1) < $len && $text[$i + 1] === '<') {
                    $depth++;
                    $i++;
                } elseif ($c === '>' && ($i + 1) < $len && $text[$i + 1] === '>') {
                    $depth--;
                    $i++;
                    if ($depth === 0) {
                        return substr($text, $start, $i - $start + 1);
                    }
                }
            }
        }

        // Array [ ... ]
        if ($ch === '[') {
            $depth = 0;
            $inStr = 0;
            for ($i = $start; $i < $len; $i++) {
                $c = $text[$i];
                if ($inStr > 0) {
                    if ($c === '\\') {
                        $i++;
                    } elseif ($c === '(') {
                        $inStr++;
                    } elseif ($c === ')') {
                        $inStr--;
                    }

                    continue;
                }
                if ($c === '(') {
                    $inStr = 1;
                } elseif ($c === '[') {
                    $depth++;
                } elseif ($c === ']') {
                    $depth--;
                    if ($depth === 0) {
                        return substr($text, $start, $i - $start + 1);
                    }
                }
            }
        }

        // Indirect reference N G R
        if (preg_match('/^\d+\s+\d+\s+R\b/', substr($text, $start, 40), $m)) {
            return $m[0];
        }

        // Simple token (number, name, bool)
        if (preg_match('/^[^\s\/\[\]<>]+/', substr($text, $start, 64), $m)) {
            return $m[0];
        }

        return '';
    }

    /**
     * @param  array<string, string>  $inherited
     */
    protected function injectInheritedPageAttributes(string $pageBody, array $inherited): string
    {
        $parts = $this->splitObjectStream($pageBody);
        $dict = $this->extractTopDictionary($parts['prefix']);
        if ($dict === '') {
            return $pageBody;
        }

        $additions = '';
        foreach (['Resources', 'MediaBox', 'CropBox', 'Rotate'] as $attr) {
            if (isset($inherited[$attr]) && $this->extractDictionaryEntryValue($dict, $attr) === null) {
                $additions .= " /{$attr} {$inherited[$attr]}";
            }
        }

        if ($additions === '') {
            return $pageBody;
        }

        $updatedDict = substr($dict, 0, -2).$additions.' >>';
        $pos = strpos($parts['prefix'], $dict);
        if ($pos === false) {
            return $pageBody;
        }

        $newPrefix = substr_replace($parts['prefix'], $updatedDict, $pos, strlen($dict));

        return $newPrefix.$parts['streamPart'];
    }

    protected function stripKeyReference(string $text, string $key): string
    {
        return (string) preg_replace('/\/'.preg_quote($key, '/').'\s+\d+\s+\d+\s+R\b/', '', $text);
    }

    protected function replaceParentReference(string $prefix, int $newParentObjNum): string
    {
        if (preg_match('/\/Parent\s+\d+\s+\d+\s+R\b/', $prefix)) {
            return (string) preg_replace('/\/Parent\s+\d+\s+\d+\s+R\b/', "/Parent {$newParentObjNum} 0 R", $prefix, 1);
        }

        $pos = strrpos($prefix, '>>');
        if ($pos !== false) {
            return substr_replace($prefix, " /Parent {$newParentObjNum} 0 R >>", $pos, 2);
        }

        return $prefix;
    }

    /**
     * Temukan seluruh referensi objek tak langsung (N G R) di luar string literal/hex dan komentar.
     *
     * @return array<int, int>
     */
    protected function findAllIndirectRefs(string $nonStream): array
    {
        $masked = $this->maskStringsAndComments($nonStream);
        if (! preg_match_all('/\b(\d+)\s+\d+\s+R\b/', $masked, $matches)) {
            return [];
        }

        return array_values(array_unique(array_map(static fn (string $v): int => (int) $v, $matches[1])));
    }

    /**
     * Tulis ulang seluruh referensi (N G R) di luar string literal/hex menggunakan peta nomor baru.
     *
     * @param  array<int, int>  $objMap
     */
    protected function rewriteIndirectReferences(string $nonStream, array $objMap): string
    {
        $len = strlen($nonStream);
        $result = '';
        $segmentStart = 0;
        $i = 0;

        while ($i < $len) {
            $ch = $nonStream[$i];

            // Literal string (...)
            if ($ch === '(') {
                $result .= $this->rewriteCodeSegment(substr($nonStream, $segmentStart, $i - $segmentStart), $objMap);
                $strStart = $i;
                $depth = 1;
                $i++;
                while ($i < $len && $depth > 0) {
                    if ($nonStream[$i] === '\\') {
                        $i += 2;

                        continue;
                    }
                    if ($nonStream[$i] === '(') {
                        $depth++;
                    } elseif ($nonStream[$i] === ')') {
                        $depth--;
                    }
                    $i++;
                }
                $result .= substr($nonStream, $strStart, $i - $strStart);
                $segmentStart = $i;

                continue;
            }

            // Dictionary delimiter << ... >> (bukan hex string)
            if ($ch === '<' && ($i + 1) < $len && $nonStream[$i + 1] === '<') {
                $i += 2;

                continue;
            }

            if ($ch === '>' && ($i + 1) < $len && $nonStream[$i + 1] === '>') {
                $i += 2;

                continue;
            }

            // Hex string <...> (bukan dictionary <<...>>)
            if ($ch === '<') {
                $result .= $this->rewriteCodeSegment(substr($nonStream, $segmentStart, $i - $segmentStart), $objMap);
                $hexStart = $i;
                $close = strpos($nonStream, '>', $i + 1);
                $i = $close === false ? $len : ($close + 1);
                $result .= substr($nonStream, $hexStart, $i - $hexStart);
                $segmentStart = $i;

                continue;
            }

            // Comment % ... EOL
            if ($ch === '%') {
                $result .= $this->rewriteCodeSegment(substr($nonStream, $segmentStart, $i - $segmentStart), $objMap);
                $cmtStart = $i;
                while ($i < $len && $nonStream[$i] !== "\n" && $nonStream[$i] !== "\r") {
                    $i++;
                }
                $result .= substr($nonStream, $cmtStart, $i - $cmtStart);
                $segmentStart = $i;

                continue;
            }

            $i++;
        }

        if ($segmentStart < $len) {
            $result .= $this->rewriteCodeSegment(substr($nonStream, $segmentStart), $objMap);
        }

        return $result;
    }

    /**
     * @param  array<int, int>  $objMap
     */
    protected function rewriteCodeSegment(string $segment, array $objMap): string
    {
        if ($segment === '') {
            return '';
        }

        return (string) preg_replace_callback(
            '/\b(\d+)\s+(\d+)\s+R\b/',
            static function (array $m) use ($objMap): string {
                $oldNum = (int) $m[1];
                if (isset($objMap[$oldNum])) {
                    return $objMap[$oldNum].' 0 R';
                }

                return $m[0];
            },
            $segment
        );
    }

    protected function maskStringsAndComments(string $nonStream): string
    {
        $len = strlen($nonStream);
        $out = '';
        $i = 0;

        while ($i < $len) {
            $ch = $nonStream[$i];

            if ($ch === '(') {
                $depth = 1;
                $i++;
                $out .= ' ';
                while ($i < $len && $depth > 0) {
                    if ($nonStream[$i] === '\\') {
                        $i += 2;

                        continue;
                    }
                    if ($nonStream[$i] === '(') {
                        $depth++;
                    } elseif ($nonStream[$i] === ')') {
                        $depth--;
                    }
                    $i++;
                }

                continue;
            }

            if ($ch === '<' && ($i + 1) < $len && $nonStream[$i + 1] === '<') {
                $out .= '<<';
                $i += 2;

                continue;
            }

            if ($ch === '>' && ($i + 1) < $len && $nonStream[$i + 1] === '>') {
                $out .= '>>';
                $i += 2;

                continue;
            }

            if ($ch === '<') {
                $close = strpos($nonStream, '>', $i + 1);
                $i = $close === false ? $len : ($close + 1);
                $out .= ' ';

                continue;
            }

            if ($ch === '%') {
                while ($i < $len && $nonStream[$i] !== "\n" && $nonStream[$i] !== "\r") {
                    $i++;
                }
                $out .= ' ';

                continue;
            }

            $out .= $ch;
            $i++;
        }

        return $out;
    }
}
