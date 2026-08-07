<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
    case ReadOnlyAdmin = 'read-only-admin';
}
