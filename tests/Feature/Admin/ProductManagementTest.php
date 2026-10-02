<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_product_list_and_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = $this->createCategory();
        $product = $this->createProduct($category);
        $inactiveProduct = $this->createProduct($category, [
            'name' => 'Galon Nonaktif',
            'slug' => 'galon-nonaktif',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Kelola Produk')
            ->assertSee($product->name)
            ->assertSee($inactiveProduct->name)
            ->assertSee(route('admin.products.create'), false);

        $this->get(route('admin.products.index', ['search' => 'Air Galon']))
            ->assertOk()
            ->assertSee($product->name);

        $this->get(route('admin.products.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee($inactiveProduct->name)
            ->assertDontSee($product->name);
    }

    public function test_admin_can_create_product_and_upload_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = $this->createCategory();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'Air Mineral Baru',
                'description' => 'Air mineral untuk keluarga.',
                'price' => 17000,
                'stock' => 12,
                'unit' => 'botol',
                'is_active' => '1',
                'image' => UploadedFile::fake()->createWithContent(
                    'air-mineral.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=')
                ),
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Produk berhasil ditambahkan.');

        $product = Product::sole();
        $this->assertSame($category->id, $product->category_id);
        $this->assertSame(17000, $product->price);
        $this->assertTrue($product->is_active);
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_admin_can_update_product_fields_and_preserve_existing_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = $this->createCategory();
        $product = $this->createProduct($category, ['image_path' => 'images/products/existing.svg']);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'category_id' => $category->id,
                'name' => 'Nama Produk Diperbarui',
                'description' => 'Deskripsi baru.',
                'price' => 24000,
                'stock' => 5,
                'unit' => 'galon',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', 'Produk berhasil diperbarui.');

        $product->refresh();
        $this->assertSame('Nama Produk Diperbarui', $product->name);
        $this->assertSame(24000, $product->price);
        $this->assertSame(5, $product->stock);
        $this->assertSame('images/products/existing.svg', $product->image_path);
    }

    public function test_admin_can_toggle_product_availability(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($this->createCategory());

        $this->actingAs($admin)
            ->patch(route('admin.products.toggle-active', $product))
            ->assertRedirect()
            ->assertSessionHas('success', 'Produk berhasil dinonaktifkan.');

        $this->assertFalse($product->fresh()->is_active);

        $this->patch(route('admin.products.toggle-active', $product))
            ->assertRedirect()
            ->assertSessionHas('success', 'Produk berhasil diaktifkan.');

        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_admin_cannot_delete_product_with_order_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct($this->createCategory());
        $customer = User::factory()->create();
        $address = $customer->addresses()->create([
            'label' => 'Rumah',
            'full_address' => 'Jl. Gunung Anyar No. 10, Surabaya',
        ]);
        $order = Order::create([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'status' => 'pending',
            'total_price' => 21000,
            'ordered_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price_snapshot' => 21000,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect()
            ->assertSessionHas('error', 'Produk tidak bisa dihapus karena sudah memiliki riwayat pesanan.');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_non_admin_cannot_manage_products(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    private function createCategory(): Category
    {
        return Category::create([
            'name' => 'Air Galon',
            'slug' => 'air-galon',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(Category $category, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Galon Aqua',
            'description' => 'Air minum untuk kebutuhan keluarga.',
            'slug' => 'galon-aqua',
            'price' => 21000,
            'stock' => 10,
            'unit' => 'galon',
            'is_active' => true,
        ], $attributes));
    }
}
