<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductVariant;
use App\Models\Product;
use App\Models\ProductVariantGroup;
use App\Models\ProductVariantGroupItem;
use Illuminate\Support\Facades\DB;

/**
 * Class ProductVariantsController
 *
 * @package App\Http\Controllers
 */
class ProductVariantsController extends AuthenticatedController
{
    public function index()
    {
        return view('productVariants.index', ['productVariants' => ProductVariantGroup::paginate()]);
    }

    public function create()
    {
        return view('productVariants.create');
    }

    public function store(StoreProductVariant $request)
    {
        DB::transaction(function () use ($request) {
            $newVariant       = new ProductVariantGroup();
            $newVariant->name = $request->get('name');
            $newVariant->saveOrFail();

            foreach ($request->get('products') as $productId => $productData) {
                $product = Product::findOrFail($productId);

                $newVariantItem                           = new ProductVariantGroupItem();
                $newVariantItem->product_variant_group_id = $newVariant->id;
                $newVariantItem->product_id               = $product->id;
                $newVariantItem->saveOrFail();
            }
        });

        return redirect(route('product-variants.index'))->with('flashes.success', 'Product variant added');
    }

    public function edit($variantId)
    {
        $variant = ProductVariantGroup::find($variantId);

        if (!$variant) {
            return redirect()->back()->with('flashes.error', 'Product variant not found');
        }

        return view('productVariants.edit', ['variant' => $variant]);
    }

    public function update(StoreProductVariant $request, $variantId)
    {
        $variant = ProductVariantGroup::find($variantId);

        if (!$variant) {
            return redirect()->back()->with('flashes.error', 'Product variant not found');
        }

        DB::transaction(function () use ($variant, $request) {
            $variant->name = $request->get('name');
            $variant->saveOrFail();

            ProductVariantGroupItem::where('product_variant_group_id', '=', $variant->id)->delete();

            foreach ($request->get('products') as $productId => $productData) {
                $product = Product::findOrFail($productId);

                $newVariantItem                           = new ProductVariantGroupItem();
                $newVariantItem->product_variant_group_id = $variant->id;
                $newVariantItem->product_id               = $product->id;
                $newVariantItem->saveOrFail();
            }
        });

        return redirect(route('product-variants.index'))->with('flashes.success', 'Product variant edited');
    }

    public function destroy($variantId)
    {
        $variant = ProductVariantGroup::find($variantId);

        if (!$variant) {
            return redirect()->back()->with('flashes.error', 'Product variant not found');
        }

        DB::transaction(function () use ($variant) {
            foreach ($variant->products as $product) {
                $product->product_variant_group_id = null;
                $product->saveOrFail();
            }

            $variant->delete();
        });

        return redirect(route('product-variants.index'))->with('flashes.success', 'Product variant deleted');
    }
}