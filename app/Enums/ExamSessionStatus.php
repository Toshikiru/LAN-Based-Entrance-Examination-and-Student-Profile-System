<?php

namespace App\Enums;

enum ExamSessionStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Flagged = 'flagged';
    case Terminated = 'terminated';
}
