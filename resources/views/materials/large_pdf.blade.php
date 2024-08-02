<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Large Materials PDF</title>
    <style>
        body {
            font-family: 'Tahoma', sans-serif;
        }
        .material-table {
            width: 100%;
            border-collapse: collapse;
        }
        .material-table th, .material-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .material-table th {
            background-color: #f2f2f2;
            text-align: left;
        }
        .material-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
<h1>Large Materials List</h1>
<table class="material-table">
    <thead>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Quantity</th>
        <th>Unit</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($materials as $material)
        <tr>
            <td>{{ $material->id }}</td>
            <td>{{ $material->name }}</td>
            <td>{{ $material->quantity }}</td>
            <td>{{ $material->unit }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
