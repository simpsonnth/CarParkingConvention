<?php

declare(strict_types=1);

namespace App\Actions\CarParks;

use App\Models\CarPark;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class CarParkMapImageStorage
{
    /**
     * Store a new map image and return the public web path (/storage/...).
     */
    public function store(UploadedFile $file): string
    {
        $path = $file->store('car-park-maps', 'public');

        if ($path === false) {
            throw new RuntimeException('Failed to store car park map image.');
        }

        return '/storage/'.$path;
    }

    /**
     * Store a new map, persist it on the car park, then delete the previous file.
     * If persistence fails, the newly stored file is cleaned up.
     */
    public function replace(CarPark $carPark, UploadedFile $file): string
    {
        $previousPath = $carPark->map_image_path;
        $newPublicPath = $this->store($file);
        $newRelativePath = $this->toRelativePath($newPublicPath);

        try {
            $carPark->forceFill(['map_image_path' => $newPublicPath])->save();
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newRelativePath);

            throw $exception;
        }

        if ($previousPath && $previousPath !== $newPublicPath) {
            $this->delete($previousPath);
        }

        return $newPublicPath;
    }

    public function delete(?string $mapImagePath): void
    {
        if ($mapImagePath === null || $mapImagePath === '') {
            return;
        }

        $relativePath = $this->toRelativePath($mapImagePath);

        if ($relativePath === '') {
            return;
        }

        Storage::disk('public')->delete($relativePath);
    }

    public function toRelativePath(string $mapImagePath): string
    {
        $path = parse_url($mapImagePath, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            $path = $mapImagePath;
        }

        return ltrim(str_replace('/storage/', '', $path), '/');
    }
}
