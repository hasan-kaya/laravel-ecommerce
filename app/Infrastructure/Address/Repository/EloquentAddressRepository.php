<?php

declare(strict_types=1);

namespace App\Infrastructure\Address\Repository;

use App\Domain\Address\Entity\Address as DomainAddress;
use App\Domain\Address\Repository\AddressRepositoryInterface;
use App\Infrastructure\Eloquent\Address as EloquentAddress;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final readonly class EloquentAddressRepository implements AddressRepositoryInterface
{
    public function findById(int $id): ?DomainAddress
    {
        $address = EloquentAddress::find($id);

        return $address ? $this->toDomain($address) : null;
    }

    public function findByUserId(int $userId): array
    {
        $addresses = EloquentAddress::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return $addresses->map(fn($address) => $this->toDomain($address))->all();
    }

    public function create(
        int $userId,
        string $label,
        string $fullName,
        string $phone,
        string $city,
        string $district,
        string $neighborhood,
        string $address,
        string $type,
        bool $isDefault,
    ): DomainAddress {
        // If setting as default, unset other defaults
        if ($isDefault) {
            $this->unsetDefaultsForType($userId, $type);
        }

        $eloquentAddress = EloquentAddress::create([
            'user_id' => $userId,
            'label' => $label,
            'full_name' => $fullName,
            'phone' => $phone,
            'city' => $city,
            'district' => $district,
            'neighborhood' => $neighborhood,
            'address' => $address,
            'type' => $type,
            'is_default' => $isDefault,
        ]);

        return $this->toDomain($eloquentAddress);
    }

    public function update(
        int $id,
        string $label,
        string $fullName,
        string $phone,
        string $city,
        string $district,
        string $neighborhood,
        string $address,
        string $type,
        bool $isDefault,
    ): DomainAddress {
        $eloquentAddress = EloquentAddress::findOrFail($id);

        // If setting as default, unset other defaults
        if ($isDefault) {
            $this->unsetDefaultsForType($eloquentAddress->user_id, $type);
        }

        $eloquentAddress->update([
            'label' => $label,
            'full_name' => $fullName,
            'phone' => $phone,
            'city' => $city,
            'district' => $district,
            'neighborhood' => $neighborhood,
            'address' => $address,
            'type' => $type,
            'is_default' => $isDefault,
        ]);

        return $this->toDomain($eloquentAddress->fresh());
    }

    public function delete(int $id): bool
    {
        return EloquentAddress::destroy($id) > 0;
    }

    public function setAsDefault(int $id, int $userId): void
    {
        $address = EloquentAddress::findOrFail($id);

        DB::transaction(function () use ($address, $userId) {
            // Unset other defaults for this type
            EloquentAddress::where('user_id', $userId)
                ->where('type', $address->type)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);

            // Set this as default
            $address->update(['is_default' => true]);
        });
    }

    public function findDefaultByUserIdAndType(int $userId, string $type): ?DomainAddress
    {
        $address = EloquentAddress::where('user_id', $userId)
            ->where('type', $type)
            ->where('is_default', true)
            ->first();

        return $address ? $this->toDomain($address) : null;
    }

    private function unsetDefaultsForType(int $userId, string $type): void
    {
        EloquentAddress::where('user_id', $userId)
            ->where('type', $type)
            ->update(['is_default' => false]);
    }

    private function toDomain(EloquentAddress $eloquentAddress): DomainAddress
    {
        return new DomainAddress(
            id: $eloquentAddress->id,
            userId: $eloquentAddress->user_id,
            label: $eloquentAddress->label,
            fullName: $eloquentAddress->full_name,
            phone: $eloquentAddress->phone,
            city: $eloquentAddress->city,
            district: $eloquentAddress->district,
            neighborhood: $eloquentAddress->neighborhood,
            address: $eloquentAddress->address,
            type: $eloquentAddress->type,
            isDefault: $eloquentAddress->is_default,
            createdAt: $eloquentAddress->created_at
                ? DateTimeImmutable::createFromMutable($eloquentAddress->created_at)
                : null,
            updatedAt: $eloquentAddress->updated_at
                ? DateTimeImmutable::createFromMutable($eloquentAddress->updated_at)
                : null,
        );
    }
}
