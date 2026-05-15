<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Users Export</title>
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
    <h1>Users Export</h1>
    <table>
        <thead>
            <tr>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Suffix</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    @if($redact)
                        @php
                            $mask = function ($value) {
                                if (!$value) {
                                    return '';
                                }
                                $length = strlen($value);
                                if ($length <= 2) {
                                    return str_repeat('*', $length);
                                }
                                return substr($value, 0, 1) . str_repeat('*', $length - 2) . substr($value, -1);
                            };
                        @endphp
                        <td>{{ $mask($user->first_name) }}</td>
                        <td>{{ $mask($user->middle_name) }}</td>
                        <td>{{ $mask($user->last_name) }}</td>
                        <td>{{ $mask($user->suffix) }}</td>
                        <td>{{ $mask(strtok($user->email, '@')) }}@{{ substr(strstr($user->email, '@'), 1) }}</td>
                    @else
                        <td>{{ $user->first_name }}</td>
                        <td>{{ $user->middle_name }}</td>
                        <td>{{ $user->last_name }}</td>
                        <td>{{ $user->suffix }}</td>
                        <td>{{ $user->email }}</td>
                    @endif
                    <td>{{ $user->role }}</td>
                    <td>{{ optional($user->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>