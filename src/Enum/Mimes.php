<?php

namespace Akyos\UXFileManager\Enum;

enum Mimes: string
{
    case PNG = 'image/png';

    case JPG = 'image/jpeg';

    case GIF = 'image/gif';

    case SVG = 'image/svg+xml';

    case BMP = 'image/bmp';

    case TIFF = 'image/tiff';

    case AVIF = 'image/avif';

    case HEIC = 'image/heif';

    case WEBP = 'image/webp';

    case ICO = 'image/vnd.microsoft.icon';

    case PDF = 'application/pdf';

    case DOC = 'application/msword';

    case DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    case ODT = 'application/vnd.oasis.opendocument.text';

    case RTF = 'application/rtf';

    case XLS = 'application/vnd.ms-excel';

    case XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    case ODS = 'application/vnd.oasis.opendocument.spreadsheet';

    case PPT = 'application/vnd.ms-powerpoint';

    case PPTX = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';

    case ODP = 'application/vnd.oasis.opendocument.presentation';

    case ZIP = 'application/zip';

    case TAR = 'application/x-tar';

    case GZIP = 'application/gzip';

    case BZIP = 'application/x-bzip';

    case BZIP2 = 'application/x-bzip2';

    case RAR = 'application/x-rar-compressed';

    case SEVENZIP = 'application/x-7z-compressed';

    case MP4 = 'video/mp4';

    case WEBM = 'video/webm';

    case MOV = 'video/quicktime';

    case MP3 = 'audio/mpeg';

    case WAV = 'audio/wav';

    case OGG = 'audio/ogg';

    case TEXT = 'text/plain';

    case CSV = 'text/csv';

    case MARKDOWN = 'text/markdown';

    case JSON = 'application/json';

    case XML = 'text/xml';

    case HTML = 'text/html';

    case CSS = 'text/css';

    case JAVASCRIPT = 'application/javascript';

    case TYPESCRIPT = 'application/typescript';

    case YAML = 'text/yaml';

    case PHP = 'text/x-php';

    case JAVA = 'text/x-java-source';

    case BINARY = 'application/octet-stream';

    private const ALIASES = [
        'image/x-png' => self::PNG,
        'image/pjpeg' => self::JPG,
        'image/jpg' => self::JPG,
        'image/x-ms-bmp' => self::BMP,
        'image/x-bmp' => self::BMP,
        'image/tif' => self::TIFF,
        'image/x-tiff' => self::TIFF,
        'image/heic' => self::HEIC,
        'image/x-icon' => self::ICO,
        'image/vnd-ms.icon' => self::ICO,
        'application/x-zip-compressed' => self::ZIP,
        'application/x-rar' => self::RAR,
        'application/vnd.rar' => self::RAR,
        'application/x-gzip' => self::GZIP,
        'application/x-7z-compressed' => self::SEVENZIP,
        'application/x-tar' => self::TAR,
        'application/vnd.ms-office' => self::DOC,
        'application/CDFV2' => self::DOC,
        'application/vnd.ms-excel.sheet.macroenabled.12' => self::XLSX,
        'application/vnd.ms-excel.sheet.macroEnabled.12' => self::XLSX,
        'application/vnd.ms-powerpoint.presentation.macroenabled.12' => self::PPTX,
        'application/vnd.ms-powerpoint.presentation.macroEnabled.12' => self::PPTX,
        'video/x-msvideo' => self::MP4,
        'video/x-matroska' => self::WEBM,
        'audio/mp3' => self::MP3,
        'audio/x-wav' => self::WAV,
        'audio/x-m4a' => self::MP3,
        'audio/mp4' => self::MP3,
        'text/javascript' => self::JAVASCRIPT,
        'application/x-javascript' => self::JAVASCRIPT,
        'text/x-javascript' => self::JAVASCRIPT,
        'application/x-httpd-php' => self::PHP,
        'application/xml' => self::XML,
        'text/x-markdown' => self::MARKDOWN,
        'text/x-yaml' => self::YAML,
        'application/x-yaml' => self::YAML,
        'application/yaml' => self::YAML,
        'inode/x-empty' => self::TEXT,
    ];

    public static function fromPath(string $path): self
    {
        if (!is_file($path)) {
            return self::fromExtension(strtolower(pathinfo($path, PATHINFO_EXTENSION)));
        }

        $detected = @mime_content_type($path);

        return self::coerce(is_string($detected) ? $detected : null, $path);
    }

    public static function coerce(?string $mime = null, ?string $path = null): self
    {
        $resolved = self::fromDetected($mime);
        if ($resolved !== null) {
            return $resolved;
        }

        if ($path !== null) {
            return self::fromExtension(strtolower(pathinfo($path, PATHINFO_EXTENSION)));
        }

        return self::BINARY;
    }

    public static function fromDetected(?string $mime): ?self
    {
        if ($mime === null || $mime === '') {
            return null;
        }

        $mime = strtolower($mime);

        return self::tryFrom($mime)
            ?? self::ALIASES[$mime]
            ?? self::fromPrefix($mime);
    }

    private static function fromPrefix(string $mime): ?self
    {
        // ponytail: repli générique par famille MIME ; upgrade path = cas dédiés + alias
        return match (true) {
            str_starts_with($mime, 'image/') => self::PNG,
            str_starts_with($mime, 'video/') => self::MP4,
            str_starts_with($mime, 'audio/') => self::MP3,
            str_starts_with($mime, 'text/') => self::TEXT,
            str_starts_with($mime, 'font/') => self::BINARY,
            default => null,
        };
    }

    private static function fromExtension(string $extension): self
    {
        return match ($extension) {
            'png' => self::PNG,
            'jpg', 'jpeg', 'jpe' => self::JPG,
            'gif' => self::GIF,
            'svg', 'svgz' => self::SVG,
            'bmp', 'dib' => self::BMP,
            'tif', 'tiff' => self::TIFF,
            'avif' => self::AVIF,
            'heic', 'heif', 'hif' => self::HEIC,
            'webp' => self::WEBP,
            'ico', 'cur' => self::ICO,
            'pdf' => self::PDF,
            'doc' => self::DOC,
            'docx' => self::DOCX,
            'odt' => self::ODT,
            'rtf' => self::RTF,
            'xls' => self::XLS,
            'xlsx', 'xlsm' => self::XLSX,
            'ods' => self::ODS,
            'ppt' => self::PPT,
            'pptx', 'pptm' => self::PPTX,
            'odp' => self::ODP,
            'zip' => self::ZIP,
            'tar' => self::TAR,
            'gz', 'tgz' => self::GZIP,
            'bz', 'bz2' => self::BZIP2,
            'rar' => self::RAR,
            '7z' => self::SEVENZIP,
            'mp4', 'm4v' => self::MP4,
            'webm' => self::WEBM,
            'mov', 'qt' => self::MOV,
            'avi' => self::MP4,
            'mkv' => self::WEBM,
            'mp3' => self::MP3,
            'wav' => self::WAV,
            'ogg', 'oga' => self::OGG,
            'txt', 'log', 'ini', 'env' => self::TEXT,
            'csv' => self::CSV,
            'md', 'markdown' => self::MARKDOWN,
            'json' => self::JSON,
            'xml', 'xsl', 'xslt' => self::XML,
            'html', 'htm' => self::HTML,
            'css' => self::CSS,
            'js', 'mjs', 'cjs' => self::JAVASCRIPT,
            'ts', 'tsx' => self::TYPESCRIPT,
            'yaml', 'yml' => self::YAML,
            'php' => self::PHP,
            'java' => self::JAVA,
            default => self::BINARY,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PNG, self::JPG, self::GIF, self::SVG, self::WEBP, self::ICO,
            self::BMP, self::TIFF, self::AVIF, self::HEIC => 'catppuccin:image',
            self::PDF => 'catppuccin:pdf',
            self::DOC, self::DOCX, self::ODT, self::RTF => 'catppuccin:ms-word',
            self::XLS, self::XLSX, self::ODS => 'catppuccin:ms-excel',
            self::PPT, self::PPTX, self::ODP => 'catppuccin:ms-powerpoint',
            self::ZIP, self::TAR, self::GZIP, self::BZIP, self::BZIP2, self::RAR, self::SEVENZIP => 'ph:file-zip',
            self::TEXT, self::CSV, self::MARKDOWN, self::JSON, self::XML, self::HTML, self::CSS,
            self::JAVASCRIPT, self::TYPESCRIPT, self::YAML, self::PHP, self::JAVA => 'catppuccin:vscode',
            default => 'catppuccin:file',
        };
    }

    static public function isEmbed(?self $mime): bool
    {
        if ($mime === null) {
            return false;
        }

        return in_array($mime, [
            self::PDF,
            self::DOC,
            self::DOCX,
            self::ODT,
            self::RTF,
            self::XLS,
            self::XLSX,
            self::ODS,
            self::PPT,
            self::PPTX,
            self::ODP,
            self::ZIP,
            self::TAR,
            self::GZIP,
            self::BZIP,
            self::BZIP2,
            self::RAR,
            self::SEVENZIP,
        ]);
    }

    static public function isRenderIco(?self $mime): bool
    {
        if ($mime === null) {
            return false;
        }

        return in_array($mime, [
            self::ZIP,
            self::TAR,
            self::GZIP,
            self::BZIP,
            self::BZIP2,
            self::RAR,
            self::SEVENZIP,
        ]);
    }
}
