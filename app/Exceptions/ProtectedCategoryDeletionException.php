<?php

namespace App\Exceptions;

use Exception;

class ProtectedCategoryDeletionException extends Exception
{
    public function __construct(string $message = 'Kategori tidak dapat dihapus.', int $code = 409, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}