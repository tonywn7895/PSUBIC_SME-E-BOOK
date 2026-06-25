<?php

namespace App\Console\Commands;

use App\Models\Ebook;
use App\Models\CarouselImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Illuminate\Support\Str;

class OptimizeExistingImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert existing JPG/PNG cover and carousel images to optimized WebP format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting image optimization...');

        $manager = new ImageManager(new Driver());
        $count = 0;

        // 1. Optimize Ebook Covers
        $ebooks = Ebook::whereNotNull('cover_path')
            ->where('cover_path', 'not like', '%.webp')
            ->get();

        foreach ($ebooks as $ebook) {
            if ($this->optimizeFile($manager, $ebook->cover_path, 'cover_path', $ebook)) {
                $count++;
            }
        }

        // 2. Optimize Carousel Images
        $carousels = CarouselImage::whereNotNull('image_path')
            ->where('image_path', 'not like', '%.webp')
            ->get();

        foreach ($carousels as $carousel) {
            if ($this->optimizeFile($manager, $carousel->image_path, 'image_path', $carousel)) {
                $count++;
            }
        }

        $this->info("Optimization complete! Total files processed: {$count}");
    }

    /**
     * Helper to optimize a single file and update the model
     */
    protected function optimizeFile($manager, $path, $attribute, $model)
    {
        if (!Storage::disk('public')->exists($path)) {
            $this->warn("File not found: {$path}");
            return false;
        }

        try {
            $this->line("Optimizing: {$path}");

            // Read the file from disk
            $fileData = Storage::disk('public')->get($path);
            $image = $manager->decode($fileData);

            // Resize (scale down to 800px max width for carousel, 600px for covers)
            $maxWidth = ($model instanceof CarouselImage) ? 1200 : 600;
            $image->scale(width: $maxWidth);

            // Create new path
            $directory = Str::beforeLast($path, '/');
            $filename = Str::beforeLast(Str::afterLast($path, '/'), '.');
            $newPath = $directory . '/' . $filename . '_' . time() . '.webp';

            // Encode and Save
            $encoded = $image->encode(new WebpEncoder(quality: 80));
            Storage::disk('public')->put($newPath, $encoded);

            // Update Database
            $oldPath = $path;
            $model->update([$attribute => $newPath]);

            // Delete Old File
            Storage::disk('public')->delete($oldPath);

            return true;
        } catch (\Exception $e) {
            $this->error("Failed to optimize {$path}: " . $e->getMessage());
            return false;
        }
    }
}
