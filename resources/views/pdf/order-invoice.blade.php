<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
        }

        h1 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #f2f2f2;
        }

        .summary {
            margin-top: 12px;
        }
    </style>
</head>

<body>
    <h1>Order Invoice</h1>
    <p><strong>Order ID:</strong> #{{ $order->id }}</p>
    <p><strong>Customer:</strong> {{ $order->user->name }}</p>
    <p><strong>Date:</strong> {{ $order->created_at->format('M d, Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Book</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $item)
                <tr>
                    <td>{{ $item->book->title }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ number_format($item->unit_price * $item->quantity, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p><strong>Total:</strong> {{ number_format($order->total_amount, 2) }}</p>
        <p><strong>Status:</strong> {{ $order->status }}</p>
    </div>
</body>

</html>