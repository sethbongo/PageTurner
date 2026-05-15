<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Books Export</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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
    </style>
</head>

<body>
    <h1>Books Export</h1>
    <table>
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ strtoupper(str_replace('_', ' ', $column)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($books as $book)
                <tr>
                    @foreach($columns as $column)
                        <td>
                            @if($column === 'category')
                                {{ optional($book->category)->name }}
                            @elseif($column === 'stock')
                                {{ $book->stock_quantity }}
                            @elseif($column === 'created_at')
                                {{ optional($book->created_at)->format('Y-m-d H:i') }}
                            @else
                                {{ $book->{$column} ?? '' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>