<?php

namespace App\Enums;

enum QuestionStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
