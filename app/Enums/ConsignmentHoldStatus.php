<?php

namespace App\Enums;

/**
 * 寄倉單狀態。
 */
enum ConsignmentHoldStatus: string
{
    case Holding = 'holding';
    case PickedUp = 'picked_up';
    case Overdue = 'overdue';
}
