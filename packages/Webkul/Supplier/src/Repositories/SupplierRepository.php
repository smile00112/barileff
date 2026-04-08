<?php

namespace Webkul\Supplier\Repositories;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\Repository;

class SupplierRepository extends Repository
{
    public function model(): string
    {
        return 'Webkul\Supplier\Contracts\Supplier';
    }

    /**
     * Create supplier with image upload handling
     */
    public function create(array $data)
    {
        if (isset($data['image']) && $data['image']) {
            $data['image'] = $this->uploadImage($data['image']);
        }

        return parent::create($data);
    }

    /**
     * Update supplier with image replacement handling
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $supplier = $this->findOrFail($id);
        $oldImage = null;

        // Handle image removal checkbox
        if (isset($data['remove_image']) && $data['remove_image']) {
            $oldImage = $supplier->image;
            $data['image'] = null;
        }

        // Handle new image upload
        if (isset($data['image']) && $data['image']) {
            $oldImage = $supplier->image;
            $data['image'] = $this->uploadImage($data['image']);
        }

        // Remove non-model attributes
        unset($data['remove_image']);

        $result = parent::update($data, $id, $attribute);

        // Delete old image only after successful DB update
        if ($oldImage) {
            $this->deleteImage($oldImage);
        }

        return $result;
    }

    /**
     * Delete supplier with image cleanup and product check
     */
    public function delete($id)
    {
        $supplier = $this->findOrFail($id);

        // Prevent deletion if supplier has products
        $productsCount = $supplier->products()->count();
        if ($productsCount > 0) {
            throw new \Exception(
                trans('supplier::app.admin.delete-failed', ['count' => $productsCount])
            );
        }

        $imagePath = $supplier->image;

        $result = parent::delete($id);

        // Delete image only after successful DB deletion
        $this->deleteImage($imagePath);

        return $result;
    }

    /**
     * Upload supplier image to storage
     */
    protected function uploadImage(\Illuminate\Http\UploadedFile $image): string
    {
        try {
            return $image->store('supplier_images', 'public');
        } catch (\Exception $e) {
            Log::error('Supplier image upload failed', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception(trans('supplier::app.admin.image-upload-failed'));
        }
    }

    /**
     * Delete supplier image from storage
     */
    protected function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        // Only delete files within supplier_images directory
        if (str_starts_with($path, 'supplier_images/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
