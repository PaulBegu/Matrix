<?php
declare(strict_types=1);

final class SkillStatusService {

    // Keep allowlists here so UI + API stay consistent.
    public const ACTIONS = [
        'Start_Training',
        'Final_Training',
        'Validat',
        'Aprobat',
        'Devalidat',
        'Nevalidat',
        'Generare_Pdf',
        'Semnare',
        'Modificare',
    ];

    public const STATUSES = [
        'In_Training',
        'Finalizat',
        'Valid',
        'Aprobat',
        'Invalid',
        'Nevalidat',
        'Pdf_Generat',
        'Semnat',
    ];

    public static function normalizeAction(string $a): ?string {
        $a = trim($a);
        return in_array($a, self::ACTIONS, true) ? $a : null;
    }

    public static function normalizeStatus(string $s): ?string {
        $s = trim($s);
        return in_array($s, self::STATUSES, true) ? $s : null;
    }

    public static function labelForStatus(?string $s): string {
        $s = $s ? trim($s) : '';
        return match ($s) {
            'Valid', 'Aprobat' => 'OK',
            'In_Training' => 'Training',
            'Finalizat' => 'Finalizat',
            'Invalid', 'Nevalidat' => 'NU',
            'Pdf_Generat' => 'PDF',
            'Semnat' => 'Semnat',
            default => '-',
        };
    }

    public static function cssClassForStatus(?string $s): string {
        $s = $s ? trim($s) : '';
        return match ($s) {
            'Valid', 'Aprobat' => 'badge bg-success',
            'In_Training' => 'badge bg-warning text-dark',
            'Finalizat' => 'badge bg-info text-dark',
            'Invalid', 'Nevalidat' => 'badge bg-danger',
            'Pdf_Generat' => 'badge bg-secondary',
            'Semnat' => 'badge bg-dark',
            default => 'badge bg-light text-dark',
        };
    }
}
