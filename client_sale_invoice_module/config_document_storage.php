<?php

/*
 * PDF display normalizer for Indic text.
 *
 * TCPDF can embed Unicode text, but many older/local TCPDF builds do not
 * perform the OpenType shaping needed by Devanagari fonts. The result is
 * square-box glyphs even when the source UTF-8 data is correct.
 *
 * Transport documents therefore keep the database value unchanged and convert
 * Devanagari text to readable Latin/English only for PDF display.
 */
function transport_pdf_display_text($value): string
{
    $text = (string)$value;
    if ($text === '' || !preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
        return $text;
    }

    // Best result when PHP Intl is available.
    if (class_exists('Transliterator')) {
        static $tr = null;
        if ($tr === null) {
            $tr = @Transliterator::create('Devanagari-Latin; Latin-ASCII');
        }
        if ($tr instanceof Transliterator) {
            $out = $tr->transliterate($text);
            if (is_string($out) && $out !== '') {
                // Friendly replacements for common Indian address terms.
                $out = str_ireplace(
                    ['Chattisagarha','Chattisgarh','Bharata'],
                    ['Chhattisgarh','Chhattisgarh','India'],
                    $out
                );
                return $out;
            }
        }
    }

    /*
     * Fallback transliteration for installations without php_intl.
     * This is intentionally address-oriented and avoids returning box glyphs.
     */
    $special = [
        'छत्तीसगढ़' => 'Chhattisgarh',
        'छत्तीसगढ़' => 'Chhattisgarh',
        'भारत' => 'India',
        'मध्य प्रदेश' => 'Madhya Pradesh',
        'उत्तर प्रदेश' => 'Uttar Pradesh',
        'झारखंड' => 'Jharkhand',
        'बिहार' => 'Bihar',
        'ओडिशा' => 'Odisha',
        'महाराष्ट्र' => 'Maharashtra',
        'राजस्थान' => 'Rajasthan',
        'दिल्ली' => 'Delhi',
        'तहसील' => 'Tahsil',
        'जिला' => 'District',
        'ग्राम' => 'Village',
        'पोस्ट' => 'Post',
    ];
    $text = strtr($text, $special);

    // Basic Devanagari transliteration for remaining address/place-name text.
    $independent = [
        'अ'=>'a','आ'=>'aa','इ'=>'i','ई'=>'ee','उ'=>'u','ऊ'=>'oo','ऋ'=>'ri',
        'ए'=>'e','ऐ'=>'ai','ओ'=>'o','औ'=>'au'
    ];
    $consonants = [
        'क'=>'k','ख'=>'kh','ग'=>'g','घ'=>'gh','ङ'=>'ng',
        'च'=>'ch','छ'=>'chh','ज'=>'j','झ'=>'jh','ञ'=>'ny',
        'ट'=>'t','ठ'=>'th','ड'=>'d','ढ'=>'dh','ण'=>'n',
        'त'=>'t','थ'=>'th','द'=>'d','ध'=>'dh','न'=>'n',
        'प'=>'p','फ'=>'ph','ब'=>'b','भ'=>'bh','म'=>'m',
        'य'=>'y','र'=>'r','ल'=>'l','व'=>'v',
        'श'=>'sh','ष'=>'sh','स'=>'s','ह'=>'h',
        'क़'=>'q','ख़'=>'kh','ग़'=>'g','ज़'=>'z','ड़'=>'d','ढ़'=>'dh','फ़'=>'f','य़'=>'y'
    ];
    $matras = [
        'ा'=>'aa','ि'=>'i','ी'=>'ee','ु'=>'u','ू'=>'oo','ृ'=>'ri',
        'े'=>'e','ै'=>'ai','ो'=>'o','ौ'=>'au','ॅ'=>'e','ॉ'=>'o'
    ];

    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $out = '';
    $count = count($chars);
    for ($i=0; $i<$count; $i++) {
        $ch = $chars[$i];

        if (isset($independent[$ch])) {
            $out .= $independent[$ch];
            continue;
        }

        if (isset($consonants[$ch])) {
            $base = $consonants[$ch];
            $next = $chars[$i+1] ?? '';
            if ($next === '्') {
                $out .= $base;
                $i++;
                continue;
            }
            if (isset($matras[$next])) {
                $out .= $base.$matras[$next];
                $i++;
                continue;
            }
            $out .= $base.'a';
            continue;
        }

        if ($ch === 'ं' || $ch === 'ँ') { $out .= 'n'; continue; }
        if ($ch === 'ः') { $out .= 'h'; continue; }
        if ($ch === 'ऽ') { continue; }
        if (isset($matras[$ch]) || $ch === '्') { continue; }

        // Keep all non-Devanagari text exactly as stored.
        if (!preg_match('/[\x{0900}-\x{097F}]/u', $ch)) {
            $out .= $ch;
        }
    }

    // Cosmetic cleanup for document readability.
    $out = preg_replace('/\s{2,}/', ' ', $out);
    return trim((string)$out);
}

function transport_pdf_display_data($value)
{
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            $value[$k] = transport_pdf_display_data($v);
        }
        return $value;
    }
    if (is_string($value)) {
        return transport_pdf_display_text($value);
    }
    return $value;
}



/*
 * Step 5 PDF font resolver.
 *
 * Why:
 * Some TCPDF installations ship an invalid/corrupt freeserif.php definition.
 * Using that font directly causes:
 *   TCPDF ERROR: The font definition file has a bad format .../freeserif.php
 *
 * Strategy:
 * 1. Prefer a real Devanagari-capable Windows/local TTF if present.
 * 2. Register it at runtime with TCPDF_FONTS::addTTFfont().
 * 3. Fall back to the standard TCPDF 'dejavusans' font so PDF generation
 *    never fails merely because an optional Indic font is unavailable.
 *
 * No font file is bundled by this module.
 */
function transport_document_pdf_font(): string
{
    // PDF body is transliterated to Latin for maximum compatibility with
    // older TCPDF builds that cannot shape Devanagari reliably.
    return 'dejavusans';
}



if (!defined('TRANSPORT_DOCUMENT_APP_ROOT')) {
    define('TRANSPORT_DOCUMENT_APP_ROOT', realpath(__DIR__ . '/..') ?: dirname(__DIR__));
}
if (!defined('TRANSPORT_DOCUMENT_RELATIVE_DIR')) {
    define('TRANSPORT_DOCUMENT_RELATIVE_DIR', 'attachementfile/transport_documents');
}
if (!defined('TRANSPORT_DOCUMENT_STORAGE_ROOT')) {
    define('TRANSPORT_DOCUMENT_STORAGE_ROOT', rtrim(TRANSPORT_DOCUMENT_APP_ROOT, '/\\'));
}
if (!defined('TRANSPORT_DOCUMENT_DELETE_PASSWORD')) {
    define('TRANSPORT_DOCUMENT_DELETE_PASSWORD', 'CHANGE_THIS_PASSWORD');
}
function transport_document_brand(): array
{
    return [
        'company' => 'KARJEE FARMS',
        'address' => 'Vill. Karjee Darima, Ambikapur, Chhattisgarh, India',
        'gstin' => '22BWJPG1381G1ZU',
        'mobile' => '6261832409',
        'logo_path' => TRANSPORT_DOCUMENT_APP_ROOT . '/img/logo/logo.png',
    ];
}
