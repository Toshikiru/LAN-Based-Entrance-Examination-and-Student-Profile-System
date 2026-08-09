<?php

namespace App\Enums;

enum CounselingNoteCategory: string
{
    case Academic = 'academic';
    case Behavioral = 'behavioral';
    case Career = 'career';
    case Personal = 'personal';

    public function label(): string
    {
        return match ($this) {
            self::Academic => 'Academic',
            self::Behavioral => 'Behavioral',
            self::Career => 'Career',
            self::Personal => 'Personal',
        };
    }
}
