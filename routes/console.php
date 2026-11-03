<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('vault:about', function (): void {
    $this->comment('Memory Vault menyimpan kenangan secara privat per akun.');
})->purpose('Display information about Memory Vault');
