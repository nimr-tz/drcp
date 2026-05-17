<?php

namespace App\Enums;

enum UserRole: string
{
    case Director = 'director';
    case Secretary = 'secretary';
    case HeadOfSection = 'head_of_section';

    public function label(): string
    {
        return match($this) {
            self::Director      => 'Director',
            self::Secretary     => 'Secretary',
            self::HeadOfSection => 'Head of Section',
        };
    }
}
