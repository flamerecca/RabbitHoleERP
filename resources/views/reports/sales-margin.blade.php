<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <title>{{ __('Sales margin report') }}</title>
    <style>
        @font-face { font-family: 'NotoSansTC'; font-style: normal; font-weight: normal; src: url('{{ resource_path('fonts/NotoSansTC.ttf') }}') format('truetype'); }
        @font-face { font-family: 'NotoSansTC'; font-style: normal; font-weight: bold; src: url('{{ resource_path('fonts/NotoSansTC.ttf') }}') format('truetype'); }
        body { font-family: 'NotoSansTC', sans-serif; font-size: 10px; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 3px 5px; }
        th { background: #eee; }
        .num { text-align: right; }
        .total td { font-weight: bold; background: #f6f6f6; }
    </style>
</head>
<body>
    @php
        $groupLabels = ['product' => __('Product'), 'customer' => __('Customer'), 'sales_order' => __('Sales order'), 'month' => __('Month')];
        $rate = fn (?string $value): string => $value === null ? '-' : number_format((float) $value * 100, 2).'%';
    @endphp
    <h1>{{ __('Sales margin report') }}</h1>
    <p>{{ $team->name }}</p>
    <p>{{ $criteria['from']->toDateString() }} ~ {{ $criteria['to']->toDateString() }}，{{ __('Group by') }}：{{ $groupLabels[$criteria['group_by']] }}</p>
    <p>{{ __('Returns are attributed to the period of the original shipment.') }} {{ __('Amounts are in the base currency.') }}</p>
    <p>{{ $generatedAt->format('Y-m-d H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>{{ __('Group') }}</th>
                <th class="num">{{ __('Shipped revenue') }}</th>
                <th class="num">{{ __('Returned revenue') }}</th>
                <th class="num">{{ __('Net revenue') }}</th>
                <th class="num">{{ __('Net cost') }}</th>
                <th class="num">{{ __('Gross margin') }}</th>
                <th class="num">{{ __('Margin rate') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report['rows'] as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="num">{{ number_format((float) $row['shipped_revenue'], 4) }}</td>
                    <td class="num">{{ number_format((float) $row['returned_revenue'], 4) }}</td>
                    <td class="num">{{ number_format((float) $row['net_revenue'], 4) }}</td>
                    <td class="num">{{ number_format((float) $row['net_cost'], 4) }}</td>
                    <td class="num">{{ number_format((float) $row['gross_margin'], 4) }}</td>
                    <td class="num">{{ $rate($row['margin_rate']) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>{{ __('Total') }}</td>
                <td class="num">{{ number_format((float) $report['totals']['shipped_revenue'], 4) }}</td>
                <td class="num">{{ number_format((float) $report['totals']['returned_revenue'], 4) }}</td>
                <td class="num">{{ number_format((float) $report['totals']['net_revenue'], 4) }}</td>
                <td class="num">{{ number_format((float) $report['totals']['net_cost'], 4) }}</td>
                <td class="num">{{ number_format((float) $report['totals']['gross_margin'], 4) }}</td>
                <td class="num">{{ $rate($report['totals']['margin_rate']) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
