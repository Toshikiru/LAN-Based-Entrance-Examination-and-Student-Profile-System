<?php

namespace App\Enums;

enum ViolationType: string
{
    case TabSwitch = 'tab_switch';
    case FullscreenExit = 'fullscreen_exit';
    case CopyPaste = 'copy_paste';
}
