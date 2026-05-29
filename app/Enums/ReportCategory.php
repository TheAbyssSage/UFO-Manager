<?php

namespace App\Enums;

enum ReportCategory: string
{
    case Drone = 'drone';
    case Lichtbal = 'lichtbal';
    case Cirkel = 'cirkel';
    case Driehoek = 'driehoek';
    case Sigaar = 'sigaar';
    case Humanoid = 'humanoid';
    case Ander = 'ander';

    public function label(): string
    {
        return match ($this) {
            self::Drone => 'Drone',
            self::Lichtbal => 'Lichtbal',
            self::Cirkel => 'Cirkel',
            self::Driehoek => 'Driehoek',
            self::Sigaar => 'Sigaar',
            self::Humanoid => 'Humanoid',
            self::Ander => 'Ander',
        };
    }
}
