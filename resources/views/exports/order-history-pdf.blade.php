<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: #007bff;
        }

        .user-info {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .user-info p {
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .order-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .order-header {
            background-color: #e9ecef;
            padding: 10px;
            font-weight: bold;
            margin-bottom: 10px;
            border-left: 4px solid #007bff;
        }

        .summary {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .summary h3 {
            margin-top: 0;
            color: #007bff;
        }

        .summary p {
            margin: 5px 0;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>📚 Order History Report</h1>
        <p>Personal Order Records - GDPR Compliant</p>
    </div>

    <div class="user-info">
        <p><strong>Customer Name:</strong> {{ $user->name }}</p>
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>Export Date:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
        <p><strong>Total Orders:</strong> {{ count($orders) }}</p>
    </div>

    @php
        $totalAmount = 0;
        $totalItems = 0;
    @endphp

    @foreach($orders as $order)
        @php
            $totalAmount += $order->total_amount;
            $totalItems += $order->orderItems->count();
        @endphp
        <div class="order-section">
            <div class="order-header">
                Order #{{ $order->id }} - {{ $order->created_at->format('Y-m-d H:i:s') }}
                <span style="float: right; color: #007bff;">Status: {{ $order->status }}</span>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderItems as $item)
                        <tr>
                            <td>{{ $item->book->title }}</td>
                            <td>{{ $item->book->author }}</td>
                            <td>{{ $item->book->isbn }}</td>
                            <td style="text-align: center;">{{ $item->quantity }}</td>
                            <td style="text-align: right;">${{ number_format($item->price, 2) }}</td>
                            <td style="text-align: right; font-weight: bold;">
                                ${{ number_format($item->quantity * $item->price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="text-align: right; margin-bottom: 20px;">
                <p style="margin: 0;"><strong>Order Total: ${{ number_format($order->total_amount, 2) }}</strong></p>
            </div>
        </div>
    @endforeach

    @if(count($orders) > 0)
        <div class="summary">
            <h3>Order Summary Statistics</h3>
            <p><strong>Total Orders:</strong> {{ count($orders) }}</p>
            <p><strong>Total Items Purchased:</strong> {{ $totalItems }}</p>
            <p><strong>Total Amount Spent:</strong> ${{ number_format($totalAmount, 2) }}</p>
            <p><strong>Average Order Value:</strong> ${{ number_format($totalAmount / count($orders), 2) }}</p>
            <p><strong>Average Items per Order:</strong> {{ number_format($totalItems / count($orders), 2) }}</p>
        </div>
    @else
        <p style="text-align: center; color: #999; margin-top: 30px;">No orders found.</p>
    @endif

    <div class="footer">
        <p>This report is provided for your records in accordance with GDPR Article 20 (Data Portability).</p>
        <p>Document generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>

</html>