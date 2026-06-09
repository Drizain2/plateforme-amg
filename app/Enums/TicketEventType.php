<?php

namespace App\Enums;

enum TicketEventType: string
{
    case StatusChanged  = 'status_changed';
    case NoteAdded      = 'note_added';
    case PartConsumed   = 'part_consumed';
    case DiagnosisSet   = 'diagnosis_set';
    case TechAssigned   = 'tech_assigned';
}
