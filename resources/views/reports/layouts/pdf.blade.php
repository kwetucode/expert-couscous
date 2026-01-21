<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title ?? 'Rapport' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }

        .container {
            padding: 20px;
        }

        /* Header */
        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
        }

        .header-right {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
        }

        .report-title {
            font-size: 18px;
            color: #333;
            margin-top: 5px;
        }

        .report-date {
            font-size: 11px;
            color: #666;
        }

        /* Filters */
        .filters {
            background-color: #f3f4f6;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .filters-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #4f46e5;
        }

        .filter-item {
            display: inline-block;
            margin-right: 20px;
        }

        .filter-label {
            font-weight: bold;
            color: #666;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th {
            background-color: #4f46e5;
            color: white;
            padding: 8px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }

        table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        table tr:hover {
            background-color: #f3f4f6;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        /* Summary boxes */
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .summary-box {
            display: table-cell;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            width: 25%;
        }

        .summary-box + .summary-box {
            margin-left: 10px;
        }

        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #4f46e5;
        }

        .summary-label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
            margin-top: 5px;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #666;
        }

        .footer-content {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
        }

        .footer-right {
            display: table-cell;
            text-align: right;
        }

        .page-number:after {
            content: counter(page);
        }

        /* Section titles */
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #4f46e5;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Alerts */
        .alert {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .alert-danger {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }

        .alert-warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            color: #92400e;
        }

        /* Page break */
        .page-break {
            page-break-after: always;
        }

        /* Money */
        .money {
            font-family: 'DejaVu Sans Mono', monospace;
        }

        /* Stock colors */
        .stock-out {
            color: #dc2626;
            font-weight: bold;
        }

        .stock-low {
            color: #f59e0b;
            font-weight: bold;
        }

        .stock-ok {
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <div class="company-name">{{ config('app.name', 'EasyVente') }}</div>
                    <div class="report-title">{{ $title }}</div>
                </div>
                <div class="header-right">
                    <div class="report-date">Généré le {{ $date }}</div>
                </div>
            </div>
        </div>

        @yield('content')
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                {{ config('app.name', 'EasyVente') }} - {{ $title }}
            </div>
            <div class="footer-right">
                Page <span class="page-number"></span>
            </div>
        </div>
    </div>
</body>
</html>
