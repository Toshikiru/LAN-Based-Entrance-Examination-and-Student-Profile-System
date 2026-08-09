<?php

namespace App\Enums;

enum ExamCategory: string
{
    case Entrance = 'entrance';
    case Aptitude = 'aptitude';
    case Personality = 'personality';
    case Custom = 'custom';
}
