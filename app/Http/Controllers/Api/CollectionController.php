public function getCollection($type) {
    $products = Product::where('category', $type)->get();
    
    $metadata = [
        'Busuuti' => ['title' => 'Busuuti Heritage', 'subtitle' => 'The Ugandan Atelier'],
        'Gomesi' => ['title' => 'Heritage Elegance', 'subtitle' => 'The Collection'],
        'Kanzu' => ['title' => 'The Kanzu Collection', 'subtitle' => 'Ceremonial Attire'],
        'ChangingDresses' => ['title' => 'Changing Dresses', 'subtitle' => 'The Second Act'],
    ];

    return response()->json([
        'header' => $metadata[$type] ?? null,
        'products' => $products
    ]);
}