<?php

namespace App\Enums;

enum DutySlotStatus: string
{
    case Open = 'open';
    case Aktif = 'aktif';
    case Tutup = 'tutup';
    case Penuh = 'penuh';
}
