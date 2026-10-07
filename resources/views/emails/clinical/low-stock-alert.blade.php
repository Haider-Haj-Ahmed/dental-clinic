@extends('emails.layouts.base')
@section('content')
    <h1>Low Stock Alert</h1>
    <p>The following inventory items are at or below their reorder level and need to be restocked:</p>

    <table class="detail-table">
        <tr>
            <td><strong>Item</strong></td>
            <td><strong>Current Stock</strong></td>
            <td><strong>Reorder Level</strong></td>
        </tr>
        @foreach($items as $item)
        <tr>
            <td>{{ $item->name }}</td>
            <td style="color: #991b1b; font-weight: bold;">{{ $item->current_stock }} {{ $item->unit }}(s)</td>
            <td>{{ $item->reorder_level }} {{ $item->unit }}(s)</td>
        </tr>
        @endforeach
    </table>

    <div class="alert-box">
        <p>Please reorder these items to avoid running out during clinic hours.</p>
    </div>
@endsection
