<?php

namespace App\Services;

use Tinify\Exception as TinifyException;

class ImagemCompressorService
{
    public function __construct()
    {
        \Tinify\setKey(config('services.tinify.key'));
    }

    public function compactarOuReverter(string $arquivoCaminho, string $destinatarioCaminho): bool
    {
        try {
            $source = \Tinify\fromFile($arquivoCaminho);
            $source->toFile($destinatarioCaminho);

            return true;
        } catch (TinifyException $e) {

            copy($arquivoCaminho, $destinatarioCaminho);

            \Log::warning("Tinify falhou: " . $e->getMessage());

            return false;
        }
    }
}
