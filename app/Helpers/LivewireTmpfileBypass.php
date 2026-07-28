<?php
namespace Livewire\Features\SupportFileUploads;

if (!function_exists('Livewire\Features\SupportFileUploads\tmpfile')) {
    function tmpfile() {
        // Coba di storage internal dulu
        $temp_dir = \storage_path('app/tmp');
        if (!is_dir($temp_dir)) {
            @mkdir($temp_dir, 0777, true);
        }
        
        // Hindari tempnam() karena kadang diblokir / bermasalah di hosting gratis
        $temp_file = $temp_dir . DIRECTORY_SEPARATOR . 'lwt_' . uniqid('', true) . '.tmp';
        $handle = @\fopen($temp_file, 'w+');
        
        if ($handle === false) {
            // Fallback: Gunakan folder public/storage yang sudah PASTI writable (berdasarkan perbaikan sebelumnya)
            $fallback_dir = \public_path('storage/tmp');
            if (!is_dir($fallback_dir)) {
                @mkdir($fallback_dir, 0777, true);
            }
            $temp_file = $fallback_dir . DIRECTORY_SEPARATOR . 'lwt_fb_' . uniqid('', true) . '.tmp';
            $handle = @\fopen($temp_file, 'w+');
            
            if ($handle === false) {
                // Daripada mengembalikan false dan bikin error aneh, lempar exception jelas
                throw new \Exception("Gagal membuat file sementara (tmp) Livewire di " . $temp_file . ". Pastikan folder writable!");
            }
        }
        
        \register_shutdown_function(function() use ($temp_file) {
            @unlink($temp_file);
        });
        
        return $handle;
    }
}
