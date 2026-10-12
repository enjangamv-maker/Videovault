<?php
declare(strict_types=1);
namespace App\Exceptions;

final class NotFoundException extends \RuntimeException
{
    public function __construct(string $msg = 'Data tidak ditemukan')
    {
        parent::__construct($msg);
    }
}
