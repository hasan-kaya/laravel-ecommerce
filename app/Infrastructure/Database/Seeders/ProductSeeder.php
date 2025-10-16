<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Seeders;

use App\Infrastructure\Eloquent\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Elektronik',
            'Giyim',
            'Ev & Yaşam',
            'Spor & Outdoor',
            'Kitap & Hobi',
            'Kozmetik & Kişisel Bakım',
            'Oyuncak',
            'Mobilya',
        ];

        $brands = [
            'Elektronik' => ['Apple', 'Samsung', 'Xiaomi', 'LG', 'Sony', 'Huawei', 'Oppo', 'Lenovo'],
            'Giyim' => ['Nike', 'Adidas', 'Puma', 'Zara', 'H&M', 'LC Waikiki', 'Mavi', 'Koton'],
            'Ev & Yaşam' => ['Karaca', 'Tefal', 'Philips', 'Bosch', 'Arçelik', 'Beko', 'Dyson'],
            'Spor & Outdoor' => ['Decathlon', 'Nike', 'Adidas', 'Under Armour', 'Columbia', 'The North Face'],
            'Kitap & Hobi' => ['Monopoly', 'Lego', 'Hasbro', 'Mattel', 'Ravensburger'],
            'Kozmetik & Kişisel Bakım' => ['Nivea', 'L\'Oréal', 'Dove', 'Garnier', 'Maybelline'],
            'Oyuncak' => ['Lego', 'Barbie', 'Hot Wheels', 'Fisher-Price', 'Playmobil'],
            'Mobilya' => ['IKEA', 'Bellona', 'Alfemo', 'Kelebek', 'İstikbal'],
        ];

        for ($i = 0; $i < 500; $i++) {
            $category = fake()->randomElement($categories);
            $brandList = $brands[$category] ?? ['Generic'];
            $brand = fake()->randomElement($brandList);

            Product::create([
                'name' => $this->generateProductName($category),
                'description' => fake()->sentence(12),
                'category' => $category,
                'brand' => $brand,
                'price' => fake()->randomFloat(2, 99, 99999),
                'stock' => 10000,
            ]);
        }

        $this->command->info(Product::count() . ' products created successfully.');
    }

    private function generateProductName(string $category): string
    {
        $prefixes = [
            'Elektronik' => ['Premium', 'Pro', 'Ultra', 'Smart', 'Digital', 'HD', '4K'],
            'Giyim' => ['Slim Fit', 'Oversize', 'Comfort', 'Premium', 'Classic', 'Sport'],
            'Ev & Yaşam' => ['Pratik', 'Akıllı', 'Dijital', 'Otomatik', 'Premium'],
            'Spor & Outdoor' => ['Pro', 'Ultra', 'Outdoor', 'Sport', 'Active'],
            'Kitap & Hobi' => ['Deluxe', 'Premium', 'Özel', 'Koleksiyonluk'],
            'Kozmetik & Kişisel Bakım' => ['Natural', 'Organik', 'Profesyonel', 'Premium'],
            'Oyuncak' => ['Eğitici', 'Eğlenceli', 'Akıllı', 'Interaktif'],
            'Mobilya' => ['Modern', 'Klasik', 'Ahşap', 'Metal', 'Lüks'],
        ];

        $products = [
            'Elektronik' => ['Telefon', 'Tablet', 'Laptop', 'Kulaklık', 'Hoparlör', 'Akıllı Saat', 'Powerbank', 'Kamera'],
            'Giyim' => ['Tişört', 'Pantolon', 'Mont', 'Ayakkabı', 'Çanta', 'Kemer', 'Şapka', 'Elbise'],
            'Ev & Yaşam' => ['Tencere Seti', 'Çaydanlık', 'Kahve Makinesi', 'Süpürge', 'Masa Örtüsü', 'Perde'],
            'Spor & Outdoor' => ['Çadır', 'Uyku Tulumu', 'Spor Ayakkabı', 'Yoga Mat', 'Dambıl Seti'],
            'Kitap & Hobi' => ['Bulmaca', 'Boyama Kitabı', 'Defter Seti', 'Kalem Seti'],
            'Kozmetik & Kişisel Bakım' => ['Şampuan', 'Krem', 'Parfüm', 'Diş Fırçası', 'Makyaj Seti'],
            'Oyuncak' => ['Yapboz', 'Araba', 'Bebek', 'Lego Seti', 'Oyun Hamuru'],
            'Mobilya' => ['Koltuk', 'Masa', 'Sandalye', 'Dolap', 'Raf', 'Kitaplık'],
        ];

        $prefix = fake()->randomElement($prefixes[$category] ?? ['Premium']);
        $product = fake()->randomElement($products[$category] ?? ['Ürün']);

        return $prefix . ' ' . $product;
    }
}
