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
            border-bottom: 2px solid #9333ea;
            padding-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: #9333ea;
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

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            padding: 15px;
            background-color: #f0f4ff;
            border-left: 4px solid #9333ea;
            border-radius: 5px;
        }

        .stat-card h4 {
            margin: 0 0 10px 0;
            color: #9333ea;
            font-size: 14px;
        }

        .stat-card p {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #9333ea;
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

        .genre-list {
            margin-bottom: 20px;
        }

        .genre-item {
            padding: 8px;
            margin-bottom: 5px;
            background-color: #f0f4ff;
            border-radius: 3px;
            display: flex;
            justify-content: space-between;
        }

        .summary {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .summary h3 {
            margin-top: 0;
            color: #9333ea;
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

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>📖 Reading History Report</h1>
        <p>Personal Reading & Purchase Records - GDPR Compliant</p>
    </div>

    <div class="user-info">
        <p><strong>Reader Name:</strong> {{ $user->name }}</p>
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>Export Date:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <!-- Statistics Section -->
    <div class="stats-grid">
        <div class="stat-card">
            <h4>Total Books Owned</h4>
            <p>{{ $statistics['total_books_owned'] }}</p>
        </div>
        <div class="stat-card">
            <h4>Total Spent</h4>
            <p>${{ number_format($statistics['total_amount_spent'], 2) }}</p>
        </div>
        <div class="stat-card">
            <h4>Avg Price/Book</h4>
            <p>${{ number_format($statistics['average_price_per_book'], 2) }}</p>
        </div>
        <div class="stat-card">
            <h4>Reading Span</h4>
            <p>{{ $statistics['reading_span_months'] }} months</p>
        </div>
    </div>

    <!-- Reading History Table -->
    <h3 style="color: #9333ea; border-bottom: 2px solid #9333ea; padding-bottom: 10px;">Complete Reading History</h3>

    @if(count($readingHistory) > 0)
        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Genre</th>
                    <th>ISBN</th>
                    <th>Purchase Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($readingHistory as $item)
                    <tr>
                        <td>{{ $item['book_title'] }}</td>
                        <td>{{ $item['author'] }}</td>
                        <td>{{ $item['genre'] }}</td>
                        <td>{{ $item['isbn'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($item['purchase_date'])->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; color: #999; margin: 20px 0;">No reading history found.</p>
    @endif

    <!-- Summary Statistics -->
    <div class="summary">
        <h3>Reading Insights & Statistics</h3>
        <p><strong>Total Books in Collection:</strong> {{ $statistics['total_books_owned'] }}</p>
        <p><strong>Total Investment:</strong> ${{ number_format($statistics['total_amount_spent'], 2) }}</p>
        <p><strong>Average Cost Per Book:</strong> ${{ number_format($statistics['average_price_per_book'], 2) }}</p>
        <p><strong>Reading Timeline:</strong> {{ $statistics['reading_span_months'] }} months of active reading</p>
    </div>

    <div class="footer">
        <p>This report is provided for your records in accordance with GDPR Article 20 (Data Portability).</p>
        <p>Document generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        <p>© {{ now()->year }} PageTurner Bookstore. All rights reserved.</p>
    </div>
</body>

</html>