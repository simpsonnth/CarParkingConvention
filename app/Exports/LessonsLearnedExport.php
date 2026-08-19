<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\LessonLearned;
use App\Models\LessonLearnedAttachment;
use App\Support\ConventionDay;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class LessonsLearnedExport implements FromCollection, WithHeadings, WithMapping, WithDrawings, WithEvents, ShouldAutoSize
{
    private const IMAGE_COLUMN = 'I';

    private const IMAGE_LINKS_COLUMN = 'J';

    private const VOICE_COLUMN = 'K';

    private const OTHER_FILES_COLUMN = 'L';

    /** @var Collection<int, LessonLearned> */
    private Collection $lessons;

    /** @var list<string> */
    private array $tempFiles = [];

    /** @var list<Drawing> */
    private array $drawings = [];

    /**
     * Excel row (1-based data rows start at 2) => list of image link payloads.
     *
     * @var array<int, list<array{url: string, label: string}>>
     */
    private array $imageLinksByRow = [];

    /**
     * @var array<int, list<array{url: string, label: string}>>
     */
    private array $voiceLinksByRow = [];

    /**
     * @var array<int, list<array{url: string, label: string}>>
     */
    private array $otherFileLinksByRow = [];

    /** @var array<int, int> lesson id => excel row */
    private array $excelRowByLessonId = [];

    public function __construct()
    {
        $this->lessons = LessonLearned::query()
            ->with('attachments')
            ->orderByDesc('created_at')
            ->get();

        $excelRow = 2;
        foreach ($this->lessons as $lesson) {
            $this->excelRowByLessonId[(int) $lesson->id] = $excelRow;
            $imageOffset = 0;

            foreach ($lesson->attachments as $attachment) {
                if ($attachment->isVoiceNote()) {
                    $this->voiceLinksByRow[$excelRow][] = [
                        'url' => $this->attachmentUrl($attachment, listen: true),
                        'label' => 'Listen: '.$attachment->original_name,
                    ];

                    continue;
                }

                if ($this->isImage($attachment)) {
                    $this->imageLinksByRow[$excelRow][] = [
                        'url' => $this->attachmentUrl($attachment, listen: false),
                        'label' => $attachment->original_name,
                    ];

                    $drawing = $this->makeImageDrawing($attachment, $excelRow, $imageOffset);
                    if ($drawing !== null) {
                        $this->drawings[] = $drawing;
                        $imageOffset++;
                    }

                    continue;
                }

                $this->otherFileLinksByRow[$excelRow][] = [
                    'url' => $this->attachmentUrl($attachment, listen: false),
                    'label' => $attachment->original_name,
                ];
            }

            $excelRow++;
        }
    }

    public function collection(): Collection
    {
        return $this->lessons;
    }

    public function headings(): array
    {
        return [
            'Submitted',
            'Source',
            'Category',
            'Convention day',
            'Title',
            'Reporter',
            'What worked well',
            'What did not work well',
            'Images',
            'Image links',
            'Voice notes',
            'Other files',
        ];
    }

    /**
     * @param  LessonLearned  $row
     */
    public function map($row): array
    {
        $excelRow = $this->excelRowByLessonId[(int) $row->id] ?? 0;

        return [
            $row->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '',
            $row->source === LessonLearned::SOURCE_ADMIN ? 'Admin' : 'Public',
            ucfirst((string) $row->category),
            ConventionDay::label($row->convention_day ?? ConventionDay::ALL_DAYS),
            $row->title ?? '',
            $row->reporter_name,
            $row->worked_well ?? '',
            $row->didnt_work_well ?? '',
            $this->hasImagesOnRow($excelRow) ? 'See thumbnail(s) →' : '',
            $this->labelsForRow($this->imageLinksByRow[$excelRow] ?? []),
            $this->labelsForRow($this->voiceLinksByRow[$excelRow] ?? []),
            $this->labelsForRow($this->otherFileLinksByRow[$excelRow] ?? []),
        ];
    }

    public function drawings(): array
    {
        return $this->drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->imageLinksByRow as $excelRow => $links) {
                    $this->applyLinksToCell($sheet, self::IMAGE_LINKS_COLUMN.$excelRow, $links);
                    if ($links !== []) {
                        $sheet->getRowDimension($excelRow)->setRowHeight(90);
                    }
                }

                foreach ($this->voiceLinksByRow as $excelRow => $links) {
                    $this->applyLinksToCell($sheet, self::VOICE_COLUMN.$excelRow, $links);
                }

                foreach ($this->otherFileLinksByRow as $excelRow => $links) {
                    $this->applyLinksToCell($sheet, self::OTHER_FILES_COLUMN.$excelRow, $links);
                }

                $sheet->getColumnDimension(self::IMAGE_COLUMN)->setWidth(18);
                $sheet->getColumnDimension(self::IMAGE_LINKS_COLUMN)->setWidth(36);
                $sheet->getColumnDimension(self::VOICE_COLUMN)->setWidth(36);
                $sheet->getColumnDimension(self::OTHER_FILES_COLUMN)->setWidth(36);
            },
        ];
    }

    public function __destruct()
    {
        $this->cleanupTempFiles();
    }

    /**
     * @param  list<array{url: string, label: string}>  $links
     */
    private function labelsForRow(array $links): string
    {
        if ($links === []) {
            return '';
        }

        return implode("\n", array_map(
            static fn (array $link): string => $link['label'],
            $links,
        ));
    }

    private function hasImagesOnRow(int $excelRow): bool
    {
        return ($this->imageLinksByRow[$excelRow] ?? []) !== [];
    }

    /**
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet  $sheet
     * @param  list<array{url: string, label: string}>  $links
     */
    private function applyLinksToCell($sheet, string $coordinate, array $links): void
    {
        if ($links === []) {
            return;
        }

        // One hyperlink per cell: first link is clickable; remaining URLs are listed for copy/open.
        $first = $links[0];
        $lines = array_map(
            static fn (array $link): string => $link['label']."\n".$link['url'],
            $links,
        );

        $cell = $sheet->getCell($coordinate);
        $cell->setValue(implode("\n\n", $lines));
        $cell->setHyperlink(new Hyperlink($first['url'], $first['label']));
        $sheet->getStyle($coordinate)->getFont()->getColor()->setRGB('0563C1');
        $sheet->getStyle($coordinate)->getFont()->setUnderline(true);
        $sheet->getStyle($coordinate)->getAlignment()->setWrapText(true);
    }

    private function isImage(LessonLearnedAttachment $attachment): bool
    {
        $mime = strtolower((string) $attachment->mime_type);
        if (str_starts_with($mime, 'image/')) {
            return true;
        }

        $name = strtolower((string) $attachment->original_name);

        return (bool) preg_match('/\.(jpe?g|png|gif|webp|bmp)$/', $name);
    }

    private function attachmentUrl(LessonLearnedAttachment $attachment, bool $listen): string
    {
        try {
            return Storage::disk($attachment->disk)->temporaryUrl(
                $attachment->path,
                now()->addDays(7),
            );
        } catch (\Throwable) {
            // Local disks (and some configs) cannot mint temporary URLs.
        }

        if ($listen && $attachment->isVoiceNote()) {
            return route('admin.lessons-learned.attachments.stream', $attachment);
        }

        return route('admin.lessons-learned.attachments.download', $attachment);
    }

    private function makeImageDrawing(LessonLearnedAttachment $attachment, int $excelRow, int $offsetIndex): ?Drawing
    {
        try {
            $contents = Storage::disk($attachment->disk)->get($attachment->path);
            if ($contents === null || $contents === '') {
                return null;
            }

            $extension = pathinfo((string) $attachment->original_name, PATHINFO_EXTENSION) ?: 'jpg';
            $tempPath = tempnam(sys_get_temp_dir(), 'll-img-');
            if ($tempPath === false) {
                return null;
            }

            $tempFile = $tempPath.'.'.$extension;
            rename($tempPath, $tempFile);
            file_put_contents($tempFile, $contents);
            $this->tempFiles[] = $tempFile;

            $drawing = new Drawing;
            $drawing->setName($attachment->original_name);
            $drawing->setDescription($attachment->original_name);
            $drawing->setPath($tempFile);
            $drawing->setHeight(80);
            $drawing->setCoordinates(self::IMAGE_COLUMN.$excelRow);
            $drawing->setOffsetX(5 + ($offsetIndex * 95));
            $drawing->setOffsetY(5);

            return $drawing;
        } catch (\Throwable) {
            return null;
        }
    }

    private function cleanupTempFiles(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->tempFiles = [];
    }
}
