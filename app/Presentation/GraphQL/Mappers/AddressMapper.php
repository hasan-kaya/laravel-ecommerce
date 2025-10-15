<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mappers;

use App\Application\Address\AddressData;

final class AddressMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(AddressData $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'full_name' => $address->fullName,
            'phone' => $address->phone,
            'city' => $address->city,
            'district' => $address->district,
            'neighborhood' => $address->neighborhood,
            'address' => $address->address,
            'type' => $address->type,
            'is_default' => $address->isDefault,
            'created_at' => $address->createdAt,
            'updated_at' => $address->updatedAt,
        ];
    }
}
