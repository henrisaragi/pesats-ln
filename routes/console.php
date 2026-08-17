<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lihat claude/status-cek-task.md - dijalankan tiap hari supaya status Komoditas Entitas yang
// sudah lewat tanggal_berakhir-nya konsisten di seluruh app (badge, filter status, dst),
// terlepas dari pengecekan tanggal_berakhir real-time yang sudah ada di KuotaPerusahaanService.
Schedule::command('komoditas-entitas:mark-expired')->daily();
