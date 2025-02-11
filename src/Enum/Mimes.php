<?php

namespace Akyos\UXFileManager\Enum;

enum Mimes: string
{
    case PNG = 'image/png';

    case JPG = 'image/jpeg';

    case GIF = 'image/gif';

    case SVG = 'image/svg+xml';

    case PDF = 'application/pdf';

    case DOC = 'application/msword';

    case DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    case XLS = 'application/vnd.ms-excel';

    case XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    case PPT = 'application/vnd.ms-powerpoint';

    case PPTX = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';

    case ZIP = 'application/zip';

    case TAR = 'application/x-tar';

    case GZIP = 'application/gzip';

    case BZIP = 'application/x-bzip';

    case BZIP2 = 'application/x-bzip2';

    case RAR = 'application/x-rar-compressed';

    case SEVENZIP = 'application/x-7z-compressed';

    case TEXT = 'text/plain';

    case CSV = 'text/csv';

    case JSON = 'application/json';

    case XML = 'text/xml';

    case HTML = 'text/html';

    case CSS = 'text/css';

    case JAVASCRIPT = 'application/javascript';

    case TYPESCRIPT = 'application/typescript';

    case PHP = 'text/x-php';

    case JAVA = 'text/x-java-source';

    case WEBP = 'image/webp';

    public function getIcon(): string
    {
        return match ($this) {
            self::PNG, self::JPG, self::GIF, self::SVG, self::WEBP => 'catppuccin:image',
            self::PDF => 'catppuccin:pdf',
            self::DOC, self::DOCX => 'catppuccin:ms-word',
            self::XLS, self::XLSX => 'catppuccin:ms-excel',
            self::PPT, self::PPTX => 'catppuccin:ms-powerpoint',
            self::ZIP, self::TAR, self::GZIP, self::BZIP, self::BZIP2, self::RAR, self::SEVENZIP => 'catppuccin:file',
            self::TEXT, self::CSV, self::JSON, self::XML, self::HTML, self::CSS, self::JAVASCRIPT, self::TYPESCRIPT, self::PHP, self::JAVA => 'catppuccin:vscode',
            default => 'catppuccin:file',
        };
    }
}
