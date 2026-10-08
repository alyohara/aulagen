<?php

namespace App\Enums;

enum DocumentType: string
{
    case Pdf = 'pdf';
    case Docx = 'docx';
    case Pptx = 'pptx';
    case Xlsx = 'xlsx';
    case Txt = 'txt';
    case Markdown = 'markdown';
    case Image = 'image';
    case Video = 'video';
    case Link = 'link';
    case Text = 'text';

    public static function fromMime(string $mime, string $extension): self
    {
        $extension = strtolower($extension);

        return match (true) {
            $mime === 'application/pdf', $extension === 'pdf' => self::Pdf,
            in_array($extension, ['doc', 'docx'], true) => self::Docx,
            in_array($extension, ['ppt', 'pptx'], true) => self::Pptx,
            in_array($extension, ['xls', 'xlsx'], true) => self::Xlsx,
            $extension === 'md', $extension === 'markdown' => self::Markdown,
            in_array($mime, ['text/plain', 'text/markdown'], true),
            in_array($extension, ['txt', 'csv', 'log'], true) => self::Txt,
            str_starts_with($mime, 'image/') => self::Image,
            default => self::Txt,
        };
    }

    public function label(): string
    {
        return strtoupper($this->value);
    }

    public function allowsUpload(): bool
    {
        return in_array($this, [self::Pdf, self::Docx, self::Pptx, self::Xlsx, self::Txt, self::Markdown, self::Image], true);
    }
}
