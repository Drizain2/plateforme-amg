{{-- resources/views/pdf/invoice.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8"/>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: sans-serif; font-size: 13px; color: #111; padding: 40px; }
  .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
  .shop-name { font-size: 22px; font-weight: bold; color: #4f46e5; }
  .invoice-meta { text-align: right; }
  .invoice-meta h2 { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
  .section { margin-bottom: 28px; }
  .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase;
    color: #6b7280; letter-spacing: 0.05em; margin-bottom: 8px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  thead th { background: #f3f4f6; padding: 8px 12px; text-align: left;
    font-size: 11px; text-transform: uppercase; color: #6b7280; }
  tbody td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
  .text-right { text-align: right; }
  .totals { width: 280px; margin-left: auto; }
  .totals td { padding: 5px 0; }
  .total-ttc { font-size: 15px; font-weight: bold; border-top: 2px solid #111;
    padding-top: 8px !important; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px;
    font-size: 11px; font-weight: bold; }
  .badge-paid { background: #dcfce7; color: #15803d; }
  .badge-sent { background: #dbeafe; color: #1d4ed8; }
  .badge-draft { background: #f3f4f6; color: #374151; }
  .notes { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;
    padding: 12px; font-size: 12px; color: #374151; }
</style>
</head>
<body>

<div class="header">
  <div style="display:flex; align-items:center; gap:14px;">
    @if($logo = $shop->logoBase64())
      <img src="{{ $logo }}" style="height:56px; max-width:120px; object-fit:contain;">
    @endif
    <div>
      <div class="shop-name">{{ $shop->name }}</div>
      <div style="color:#6b7280; margin-top:4px; font-size:12px;">
        {{ $shop->address }}<br>
        {{ $shop->email }} · {{ $shop->phone }}
      </div>
    </div>
  </div>
  <div class="invoice-meta">
    <h2>FACTURE</h2>
    <div style="font-size:15px; font-weight:bold;">{{ $invoice->number }}</div>
    <div style="color:#6b7280; font-size:12px; margin-top:4px;">
      Émise le {{ $invoice->issued_at->format('d/m/Y') }}<br>
      @if($invoice->due_at)
        Échéance : {{ $invoice->due_at->format('d/m/Y') }}
      @endif
    </div>
    <div style="margin-top:8px;">
      <span class="badge badge-{{ $invoice->status->value }}">
        {{ $invoice->status->label() }}
      </span>
    </div>
  </div>
</div>

<div class="section">
  <div class="section-title">Facturer à</div>
  <strong>{{ $invoice->customer->name }}</strong><br>
  @if($invoice->customer->email)
    {{ $invoice->customer->email }}<br>
  @endif
  @if($invoice->customer->phone)
    {{ $invoice->customer->phone }}
  @endif
</div>

@if($invoice->ticket)
<div class="section">
  <div class="section-title">Référence ticket</div>
  {{ $invoice->ticket->reference }}
</div>
@endif

<table>
  <thead>
    <tr>
      <th>Désignation</th>
      <th>Type</th>
      <th class="text-right">Qté</th>
      <th class="text-right">Prix unit. HT</th>
      <th class="text-right">Total HT</th>
    </tr>
  </thead>
  <tbody>
    @foreach($invoice->lines as $line)
    <tr>
      <td>{{ $line->label }}</td>
      <td>{{ $line->type === 'service' ? 'Main d\'œuvre' : 'Pièce' }}</td>
      <td class="text-right">{{ $line->quantity }}</td>
      <td class="text-right">{{ number_format($line->unit_price, 0, ',', ' ') }} FCFA</td>
      <td class="text-right">{{ number_format($line->total, 0, ',', ' ') }} FCFA</td>
    </tr>
    @endforeach
  </tbody>
</table>

<table class="totals">
  <tr>
    <td>Total HT</td>
    <td class="text-right">{{ number_format($invoice->total_ht, 0, ',', ' ') }} FCFA</td>
  </tr>
  <tr>
    <td>TVA ({{ $invoice->tax_rate }}%)</td>
    <td class="text-right">{{ number_format($invoice->tax_amount, 0, ',', ' ') }} FCFA</td>
  </tr>
  <tr class="total-ttc">
    <td><strong>Total TTC</strong></td>
    <td class="text-right"><strong>{{ number_format($invoice->total_ttc, 0, ',', ' ') }} FCFA</strong></td>
  </tr>
</table>

@if($invoice->notes)
<div class="section">
  <div class="section-title">Notes</div>
  <div class="notes">{{ $invoice->notes }}</div>
</div>
@endif

</body>
</html>