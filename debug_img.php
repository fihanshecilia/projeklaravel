<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$p = \App\Models\Product::first();
$path = $p->image_path;
$disk = \Illuminate\Support\Facades\Storage::disk('public');

echo "Path: " . $path . PHP_EOL;
echo "Exists: " . ($disk->exists($path) ? 'YES' : 'NO') . PHP_EOL;
echo "URL: " . $disk->url($path) . PHP_EOL;
echo "Path storage: " . $disk->path($path) . PHP_EOL;
echo "File exists at path: " . (file_exists($disk->path($path)) ? 'YES' : 'NO') . PHP_EOL;
echo "asset() URL: " . asset('storage/' . $path) . PHP_EOL;