<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case Confirmed = 'confirmed';
    case Debunked = 'debunked';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In afwachting',
            self::InReview => 'In onderzoek',
            self::Confirmed => 'Bevestigd',
            self::Debunked => 'Ontkracht',
            self::Spam => 'Spam',
        };
    }
}
