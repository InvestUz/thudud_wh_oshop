<?php

namespace App\Services;

use App\Enums\ApplicationStage;
use App\Enums\TransitionAction;
use App\Models\Application;
use App\Models\ApplicationSurvey;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use ZipArchive;

/**
 * Ariza ma'lumotlaridan shartnoma loyihasini (.docx) yaratadi.
 *
 * Shablon — oddiy .txt fayl (resources/contract-templates/shartnoma-namuna.txt),
 * ichida {placeholder}'lar bo'ladi. "# " bilan boshlangan satr — markazlashgan
 * qalin sarlavha; "[IMZO]" — imzo bloki. Docx tashqi kutubxonasiz, ZipArchive
 * bilan WordprocessingML (Office Open XML) sifatida quriladi.
 *
 * Rahbar tasdiqlagach — hujjat tagida rahbar (ijaraga beruvchi) e-imzo bloki
 * (raqam/sana + QR + F.I.O. + lavozim) chiqadi; tadbirkor imzolagach — ijaraga
 * oluvchi tomonida uning ism-familiyasidan generatsiya qilingan QR paydo bo'ladi.
 */
class ContractDraftService
{
    public const TEMPLATE_PATH = 'contract-templates/shartnoma-namuna.txt';

    private const FONT = 'Times New Roman';

    /** QR rasm o'lchami (DOCX uchun, EMU; 914400 EMU = 1 dyuym ≈ 2.54 sm). */
    private const QR_EMU = 990000;

    /** O'zbekcha (kiril) oy nomlari. */
    private const MONTHS = [
        1 => 'январ', 2 => 'феврал', 3 => 'март', 4 => 'апрел', 5 => 'май', 6 => 'июн',
        7 => 'июл', 8 => 'август', 9 => 'сентябр', 10 => 'октябр', 11 => 'ноябр', 12 => 'декабр',
    ];

    /** DOCX ichiga joylanadigan rasmlar: [['target'=>..,'rid'=>..,'bytes'=>..], ...]. */
    private array $images = [];

    private int $drawingSeq = 0;

    /**
     * Loyihani yaratadi va public/uploads ichidagi nisbiy yo'lni qaytaradi.
     */
    public function generate(Application $application): string
    {
        $this->loadRelations($application);

        $data = $this->buildData($application);
        $ctx = $this->signatureContext($application);
        $filled = strtr($this->loadTemplate(), $data);

        $relative = 'uploads/contracts/draft-'.$application->id.'.docx';
        $absolute = public_path($relative);

        $dir = dirname($absolute);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $this->writeDocx($absolute, $filled, $data, $ctx);

        return $relative;
    }

    /**
     * Loyihani brauzerda (yuklab olmasdan) ko'rsatish uchun HTML sifatida render qiladi.
     * DOCX bilan bir xil shablon + ma'lumotlardan foydalanadi — fayl saqlanmaydi.
     */
    public function html(Application $application): string
    {
        $this->loadRelations($application);

        $data = $this->buildData($application);
        $ctx = $this->signatureContext($application);
        $filled = strtr($this->loadTemplate(), $data);

        return $this->documentHtml($filled, $data, $ctx);
    }

    /** Render uchun kerakli bog'lanishларни (transitions imzо holati учун ҳам) юклайди. */
    private function loadRelations(Application $application): void
    {
        $application->loadMissing([
            'object.district', 'object.region', 'object.mahalla',
            'applicant', 'latestSurvey', 'adjacentAreas', 'region', 'district', 'contract',
        ]);
        // Imzo holati transitions'дан аниқланади — ҳар доим янги ҳолатда юклаймиз.
        $application->load(['transitions' => fn ($q) => $q->with('performer')->orderBy('id')]);
    }

    /**
     * Imzо контексти — босқичга қараб раҳбар/тадбиркор имзоси ва QR матнлари.
     *
     * @return array<string, mixed>
     */
    private function signatureContext(Application $application): array
    {
        $stage = $application->current_stage;
        $headSigned = in_array($stage, [ApplicationStage::AwaitingSignature, ApplicationStage::Approved], true);
        $applicantSigned = $stage === ApplicationStage::Approved;

        $transitions = $application->relationLoaded('transitions') ? $application->transitions : collect();
        $approveTr = $transitions->first(fn ($t) => $t->action === TransitionAction::Approve);
        $signTr = $transitions->first(fn ($t) => $t->action === TransitionAction::Sign);

        $headDate = $approveTr?->created_at ?? $application->finished_at ?? $application->updated_at ?? now();
        $applicantDate = $signTr?->created_at ?? $application->finished_at ?? $application->updated_at ?? now();

        $headFullName = $approveTr?->performer?->displayName() ?: 'Шакиров Бахтиёр Анварович';
        $applicantName = $application->applicant?->displayName()
            ?: ($application->object?->director_name ?: 'Тадбиркор');

        $regNumber = (string) $application->id;
        $headDateStr = $this->formatDate($headDate);
        $applicantDateStr = $this->formatDate($applicantDate);
        $pinfl = (string) ($application->applicant?->pinfl ?: $application->object?->tin_pinfl ?: '');

        return [
            'head_signed' => $headSigned,
            'applicant_signed' => $applicantSigned,
            'doc_number' => $regNumber,
            'head_name' => $this->initials($headFullName),
            'head_title' => 'Бошқарув раиси',
            'head_date' => $headDateStr,
            'head_qr' => 'Тошкент шаҳар ҳокимияти | '.$regNumber.'-сон | '.$headDateStr.' | Имзоловчи: '.$headFullName,
            'applicant_name' => $applicantName,
            'applicant_date' => $applicantDateStr,
            'applicant_qr' => 'Ижарага олувчи: '.$applicantName.' | ЖШШИР/СТИР: '.$pinfl.' | '.$regNumber.'-сон | '.$applicantDateStr,
        ];
    }

    /** "Каримов Шавкат" -> "Ш. Каримов"; нуқтали бўлса ўзгартирмайди. */
    private function initials(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'Б. А. Шакиров';
        }
        if (str_contains($name, '.')) {
            return $name;
        }
        $parts = preg_split('/\s+/', $name) ?: [$name];
        if (count($parts) >= 2) {
            $surname = array_shift($parts);
            $ini = implode(' ', array_map(fn ($p) => mb_substr($p, 0, 1).'.', $parts));

            return $ini.' '.$surname;
        }

        return $name;
    }

    private function formatDate($date): string
    {
        $d = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $d->year.'-йил '.$d->day.'-'.(self::MONTHS[$d->month] ?? '');
    }

    /** Shablon matnini o'qiydi (bo'lmasa — minimal zaxira matn). */
    private function loadTemplate(): string
    {
        $path = resource_path(self::TEMPLATE_PATH);

        if (is_file($path)) {
            return (string) file_get_contents($path);
        }

        return "# ШАРТНОМА ЛОЙИҲАСИ\n\nАриза: {ariza_raqami}\nТадбиркор: {tadbirkor}\nМайдон: {maydon} кв.м\nЙиллик тўлов: {yillik} сўм\n\n[IMZO]";
    }

    /** @return array<string, string> placeholder => qiymat */
    private function buildData(Application $application): array
    {
        /** @var ApplicationSurvey|null $survey */
        $survey = $application->latestSurvey;
        $object = $application->object;

        $area = (float) ($survey?->total_area
            ?: $survey?->calculated_area
            ?: $application->adjacentAreas->sum('area_m2'));

        $monthly = $area * ContractService::MONTHLY_RATE_PER_M2;
        $yearly = $monthly * ContractService::TERM_MONTHS;

        $fmt = fn ($n) => number_format((float) $n, 0, '.', ' ');

        $now = now();
        $sana = $now->year.' йил '.$now->day.'-'.(self::MONTHS[$now->month] ?? '');

        $address = $object?->fullAddress()
            ?: trim(($application->region?->name ?? '').', '.($application->district?->name ?? ''), ', ');

        return [
            '{sana}' => $sana,
            '{ariza_raqami}' => (string) $application->application_number,
            '{ijaraga_beruvchi}' => 'Тошкент шаҳар ҳокимияти',
            '{shahar}' => (string) ($application->region?->name ?: 'Тошкент'),
            '{viloyat}' => (string) ($application->region?->name ?: '—'),
            '{tuman}' => (string) ($application->district?->name ?: '—'),
            '{tadbirkor}' => $application->applicant?->displayName() ?: ($object?->director_name ?: '—'),
            '{pinfl}' => (string) ($application->applicant?->pinfl ?: $object?->tin_pinfl ?: '—'),
            '{firma}' => (string) ($object?->company_name ?: '—'),
            '{obyekt}' => (string) ($object?->company_name ?: 'тижорат объекти'),
            '{kadastr}' => (string) ($object?->cadastre_number ?: '—'),
            '{manzil}' => $address !== '' ? $address : '—',
            '{faoliyat}' => (string) ($survey?->activity_type ?: optional($application->adjacentAreas->first())->activity ?: '—'),
            '{kocha_turi}' => (string) ($survey?->street_type ?: '—'),
            '{maqsad}' => (string) ($survey?->usage_purpose ?: '—'),
            '{maydon}' => $fmt($area),
            '{oylik}' => $fmt($monthly),
            '{yillik}' => $fmt($yearly),
            '{muddat}' => (string) ContractService::TERM_MONTHS,
        ];
    }

    // ===================================================================
    //  DOCX
    // ===================================================================

    /** Minimal, lekin to'g'ri .docx (zip: content types + rels + document.xml + media). */
    private function writeDocx(string $path, string $text, array $data, array $ctx): void
    {
        @unlink($path);

        $this->images = [];
        $this->drawingSeq = 0;

        // Avval body'ni quramiz — bu jarayonda QR rasmlari $this->images'ga yig'iladi.
        $documentXml = $this->documentXml($text, $data, $ctx);

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return;
        }

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Default Extension="png" ContentType="image/png"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>'
        );

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>'
        );

        $zip->addFromString('word/document.xml', $documentXml);

        if ($this->images !== []) {
            $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
            foreach ($this->images as $img) {
                $rels .= '<Relationship Id="'.$img['rid'].'" '
                    .'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" '
                    .'Target="'.$img['target'].'"/>';
                $zip->addFromString('word/'.$img['target'], $img['bytes']);
            }
            $rels .= '</Relationships>';
            $zip->addFromString('word/_rels/document.xml.rels', $rels);
        }

        $zip->close();
    }

    private function documentXml(string $text, array $data, array $ctx): string
    {
        $body = '';

        foreach (preg_split("/\r\n|\n|\r/", $text) as $raw) {
            $line = rtrim((string) $raw);

            if ($line === '[IMZO]') {
                $body .= $this->signatureTableDocx($data, $ctx).'<w:p/>';
                continue;
            }

            if ($line === '') {
                $body .= '<w:p/>';
                continue;
            }

            if (str_starts_with($line, '# ')) {
                $body .= $this->para(substr($line, 2), true, 'center');
                continue;
            }

            // "## " — qalin, chapdan, abzassiz (sarlavha-sana satri uchun).
            if (str_starts_with($line, '## ')) {
                $body .= $this->para(substr($line, 3), true, 'both', false);
                continue;
            }

            $body .= $this->para($line, false, 'both');
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document '
            .'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            .'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">'
            .'<w:body>'.$body
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/>'
            .'<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr>'
            .'</w:body></w:document>';
    }

    /** Imzolar uchun chegarasiz 2 ustunli jadval. Ikkala tomon ham imzolansa — pastda QR. */
    private function signatureTableDocx(array $data, array $ctx): string
    {
        $cellP = function (string $text, bool $bold = false) {
            return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="20"/></w:pPr>'
                .'<w:r>'.$this->runProps($bold)
                .'<w:t xml:space="preserve">'.$this->esc($text).'</w:t></w:r></w:p>';
        };

        $left = $cellP('Ижарага берувчи', true)
            .$cellP($data['{ijaraga_beruvchi}'])
            .$cellP('(номи)')
            .$cellP('Манзил: ____________________')
            .$cellP('СТИР: ____________________')
            .$cellP('ҳ/р: ____________________')
            .$cellP(' ');
        if (! empty($ctx['head_signed'])) {
            $left .= $this->imageDrawingXml($this->qrPng($ctx['head_qr']), 760000)
                .$cellP('Е-ИМЗО: '.$ctx['head_name'])
                .$cellP($ctx['head_title'])
                .$cellP($ctx['doc_number'].'-сон, '.$ctx['head_date']);
        } else {
            $left .= $cellP('Имзо: ____________  М.Ў.');
        }

        $right = $cellP('Ижарага олувчи', true)
            .$cellP($data['{firma}'])
            .$cellP('ФИО: '.$data['{tadbirkor}'])
            .$cellP('Манзил: '.$data['{manzil}'])
            .$cellP('СТИР (ЖШШИР): '.$data['{pinfl}'])
            .$cellP('ҳ/р: ____________________')
            .$cellP(' ');
        if (! empty($ctx['applicant_signed'])) {
            $right .= $this->imageDrawingXml($this->qrPng($ctx['applicant_qr']), 760000)
                .$cellP('Е-ИМЗО: '.$ctx['applicant_name'])
                .$cellP($ctx['applicant_date']);
        } else {
            $right .= $cellP('Имзо: ____________');
        }

        $cell = fn (string $inner) => '<w:tc><w:tcPr><w:tcW w:w="4500" w:type="dxa"/>'
            .'<w:tcMar><w:left w:w="108" w:type="dxa"/><w:right w:w="108" w:type="dxa"/></w:tcMar>'
            .'</w:tcPr>'.$inner.'</w:tc>';

        return '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/>'
            .'<w:tblBorders>'.$this->noBorders().'</w:tblBorders></w:tblPr>'
            .'<w:tr>'.$cell($left).$cell($right).'</w:tr>'
            .'</w:tbl>';
    }

    private function noBorders(): string
    {
        return '<w:top w:val="none" w:sz="0" w:space="0"/><w:left w:val="none" w:sz="0" w:space="0"/>'
            .'<w:bottom w:val="none" w:sz="0" w:space="0"/><w:right w:val="none" w:sz="0" w:space="0"/>'
            .'<w:insideH w:val="none" w:sz="0" w:space="0"/><w:insideV w:val="none" w:sz="0" w:space="0"/>';
    }

    /** PNG rasmni hujjatga ro'yxatdan o'tkazadi va inline <w:drawing> qaytaradi. */
    private function imageDrawingXml(string $png, int $emu = self::QR_EMU): string
    {
        $this->drawingSeq++;
        $n = $this->drawingSeq;
        $rid = 'rIdImg'.$n;
        $this->images[] = ['target' => 'media/qr'.$n.'.png', 'rid' => $rid, 'bytes' => $png];
        $name = 'QR'.$n;

        return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0"/></w:pPr><w:r><w:drawing>'
            .'<wp:inline distT="0" distB="0" distL="0" distR="0">'
            .'<wp:extent cx="'.$emu.'" cy="'.$emu.'"/>'
            .'<wp:effectExtent l="0" t="0" r="0" b="0"/>'
            .'<wp:docPr id="'.$n.'" name="'.$name.'"/>'
            .'<wp:cNvGraphicFramePr/>'
            .'<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            .'<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<pic:nvPicPr><pic:cNvPr id="'.$n.'" name="'.$name.'"/><pic:cNvPicPr/></pic:nvPicPr>'
            .'<pic:blipFill><a:blip r:embed="'.$rid.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$emu.'" cy="'.$emu.'"/></a:xfrm>'
            .'<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            .'</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
    }

    /** Bitta paragraf: $align = center|both|left, $bold — qalin, $indent — abzas. */
    private function para(string $text, bool $bold, string $align, bool $indent = true): string
    {
        $ppr = '<w:pPr>';
        if ($align === 'center') {
            $ppr .= '<w:spacing w:before="120" w:after="120" w:line="240" w:lineRule="auto"/><w:jc w:val="center"/>';
        } else {
            $ppr .= '<w:spacing w:after="0" w:line="240" w:lineRule="auto"/>';
            if ($indent) {
                $ppr .= '<w:ind w:firstLine="851"/>';
            }
            $ppr .= '<w:jc w:val="'.$align.'"/>';
        }
        $ppr .= '</w:pPr>';

        return '<w:p>'.$ppr.'<w:r>'.$this->runProps($bold)
            .'<w:t xml:space="preserve">'.$this->esc($text).'</w:t></w:r></w:p>';
    }

    private function runProps(bool $bold): string
    {
        return '<w:rPr><w:rFonts w:ascii="'.self::FONT.'" w:hAnsi="'.self::FONT.'" w:cs="'.self::FONT.'"/>'
            .($bold ? '<w:b/><w:bCs/>' : '')
            .'<w:sz w:val="28"/><w:szCs w:val="28"/><w:lang w:val="uz-Cyrl-UZ"/></w:rPr>';
    }

    // ===================================================================
    //  HTML (brauzerda ko'rish)
    // ===================================================================

    private function documentHtml(string $text, array $data, array $ctx): string
    {
        $body = '';

        foreach (preg_split("/\r\n|\n|\r/", $text) as $raw) {
            $line = rtrim((string) $raw);

            if ($line === '[IMZO]') {
                $body .= $this->signatureHtml($data, $ctx);
                continue;
            }

            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '# ')) {
                $body .= '<h2 class="cd-h">'.$this->esc(substr($line, 2)).'</h2>';
                continue;
            }

            if (str_starts_with($line, '## ')) {
                $body .= '<p class="cd-meta">'.$this->esc(substr($line, 3)).'</p>';
                continue;
            }

            $body .= '<p class="cd-p">'.$this->esc($line).'</p>';
        }

        return $body;
    }

    /** Imzolar jadvali — HTML. Ikkala tomon ҳам имзоланса — пастда QR. */
    private function signatureHtml(array $d, array $ctx): string
    {
        $col = function (array $rows) {
            $html = '';
            foreach ($rows as $i => $row) {
                $cls = $i === 0 ? ' cd-sig-title' : '';
                $html .= '<div class="cd-sig-row'.$cls.'">'.($row === '' ? '&nbsp;' : $this->esc($row)).'</div>';
            }

            return $html;
        };

        $leftRows = ['Ижарага берувчи', $d['{ijaraga_beruvchi}'], '(номи)',
            'Манзил: ____________________', 'СТИР: ____________________', 'ҳ/р: ____________________', ' '];

        $leftExtra = '';
        if (! empty($ctx['head_signed'])) {
            $leftExtra = '<div class="cd-sig-qr">'.$this->qrSvg($ctx['head_qr']).'</div>'
                .'<div class="cd-sig-row">Е-ИМЗО: '.$this->esc($ctx['head_name']).'</div>'
                .'<div class="cd-sig-row">'.$this->esc($ctx['head_title']).'</div>'
                .'<div class="cd-sig-row">'.$this->esc($ctx['doc_number']).'-сон, '.$this->esc($ctx['head_date']).'</div>';
        } else {
            $leftExtra = '<div class="cd-sig-row">Имзо: ____________  М.Ў.</div>';
        }

        $rightRows = ['Ижарага олувчи', $d['{firma}'], 'ФИО: '.$d['{tadbirkor}'],
            'Манзил: '.$d['{manzil}'], 'СТИР (ЖШШИР): '.$d['{pinfl}'], 'ҳ/р: ____________________', ' '];

        $rightExtra = '';
        if (! empty($ctx['applicant_signed'])) {
            $rightExtra = '<div class="cd-sig-qr">'.$this->qrSvg($ctx['applicant_qr']).'</div>'
                .'<div class="cd-sig-row">Е-ИМЗО: '.$this->esc($ctx['applicant_name']).'</div>'
                .'<div class="cd-sig-row">'.$this->esc($ctx['applicant_date']).'</div>';
        } else {
            $rightExtra = '<div class="cd-sig-row">Имзо: ____________</div>';
        }

        return '<div class="cd-sig">'
            .'<div class="cd-sig-col">'.$col($leftRows).$leftExtra.'</div>'
            .'<div class="cd-sig-col">'.$col($rightRows).$rightExtra.'</div>'
            .'</div>';
    }

    private function esc(string $s): string
    {
        return str_replace(
            ['&', '<', '>', '"'],
            ['&amp;', '&lt;', '&gt;', '&quot;'],
            $s
        );
    }

    // ===================================================================
    //  QR
    // ===================================================================

    /** QR rasmni PNG (binar) sifatida — DOCX uchun. */
    private function qrPng(string $text): string
    {
        $qr = new QrCode(data: $text, size: 240, margin: 8);

        return (new PngWriter())->write($qr)->getString();
    }

    /** QR rasmni inline SVG sifatida — HTML uchun (XML prologsiz). */
    private function qrSvg(string $text): string
    {
        $qr = new QrCode(data: $text, size: 200, margin: 6);
        $svg = (new SvgWriter())->write($qr)->getString();

        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg) ?? $svg;
    }
}
