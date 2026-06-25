<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Schema::table('ebooks', function (Blueprint $table) {
    $table->dropColumn(['category', 'province', 'fiscal_year']);
});
echo "Done dropping columns\n";
