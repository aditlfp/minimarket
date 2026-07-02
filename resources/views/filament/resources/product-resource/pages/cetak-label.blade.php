<x-filament-panels::page>
    {{ $this->form }}
    <div class="mt-6">
        <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white rounded print:hidden">🖨️ Print Label</button>
    </div>
    @php
        use Picqer\Barcode\BarcodeGeneratorPNG;
        $generator = new BarcodeGeneratorPNG();
        $products = $this->getProducts();
    @endphp
    @if(!empty($products))
        <div class="flex flex-wrap gap-1 mt-6">
            @foreach($products as $item)
                @for($i = 0; $i < $item['qty']; $i++)
                    <div class="label-item" style="width:58mm;padding:3mm;border:1px solid #333;margin:1mm;text-align:center;font-family:Arial;page-break-inside:avoid;">
                        <img src="data:image/png;base64,{{ base64_encode($generator->getBarcode($item['product']->barcode, $generator::TYPE_CODE_128)) }}" style="width:100%;max-height:18mm;" alt="barcode">
                        <div style="font-size:10pt;font-weight:bold;margin-top:2mm;">{{ $item['product']->nama }}</div>
                        <div style="font-size:9pt;margin-top:1mm;color:#d32f2f;">Rp {{ number_format($item['product']->harga_jual, 0, ',', '.') }}</div>
                    </div>
                @endfor
            @endforeach
        </div>
    @endif
    <style media="print">
        @page{margin:2mm;size:auto;}
        body{margin:0;padding:0;}
        .label-item{border:1px solid #000!important;}
        .print\:hidden{display:none!important;}
    </style>
</x-filament-panels::page>
