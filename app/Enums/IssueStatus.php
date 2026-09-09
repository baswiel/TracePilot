<?php

namespace App\Enums;

enum IssueStatus: string
{
    case Open = 'open';
    case Handling = 'handling';
    case Completed = 'completed';
}
