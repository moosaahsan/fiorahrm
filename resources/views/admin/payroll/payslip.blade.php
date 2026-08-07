<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $item->employee->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/bs4-compat.css', 'resources/css/payslip.css'])
</head>
<body class="payslip-page">
    <div class="payslip-container">
        <div class="text-center mb-4 no-print">
            <button onclick="window.print()" class="btn btn-dark rounded-pill px-4 shadow-sm">
                <i class="fas fa-print mr-2"></i> Print Official Document
            </button>
        </div>

        <div class="payslip-card position-relative">
            <div class="watermark">OFFICIAL</div>
            
            <div class="payslip-header d-flex justify-content-between align-items-start">
                <div style="z-index: 1;">
                    <h1 class="fw-800 mb-1" style="font-weight: 800; font-size: 2.5rem;">PAYSLIP</h1>
                    <p class="mb-0 opacity-75 fw-bold">{{ date('F Y', mktime(0, 0, 0, $item->payroll->month, 1, $item->payroll->year)) }}</p>
                </div>
                <div class="text-end" style="z-index: 1;">
                    <h4 class="fw-bold mb-1">{{ $item->employee->branch->name ?? 'Enterprise HRM' }}</h4>
                    <p class="small mb-0 opacity-75">Ledger UID: #{{ str_pad($item->payroll->id, 6, '0', STR_PAD_LEFT) }}</p>
                </div>
            </div>

            <div class="payslip-body">
                <div class="row mb-5">
                    <div class="col-7">
                        <div class="section-title">Recipient Information</div>
                        <h4 class="fw-bold text-dark mb-1">{{ $item->employee->name }}</h4>
                        <div class="text-muted mb-0 fw-semibold">{{ $item->employee->position }}</div>
                        <div class="text-muted small">Employee ID: EMP-{{ str_pad($item->employee->id, 4, '0', STR_PAD_LEFT) }}</div>
                    </div>
                    <div class="col-5 text-end">
                        <div class="section-title">Disbursement Summary</div>
                        <p class="small text-muted mb-1">Issue Date</p>
                        <h5 class="fw-bold">{{ now()->format('d M, Y') }}</h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="section-title">Earnings (Credits)</div>
                        <table class="table table-borderless table-sm salary-table">
                            <tbody>
                                @foreach($item->earnings_detail as $earn)
                                <tr>
                                    <td class="fw-semibold text-slate-600">{{ $earn['name'] }}</td>
                                    <td class="text-end fw-bold">Rs. {{ number_format($earn['amount'], 2) }}</td>
                                </tr>
                                @endforeach
                                <tr class="border-top">
                                    <td class="fw-800 text-dark pt-3" style="font-weight: 800;">GROSS PAYABLE</td>
                                    <td class="text-end fw-800 text-dark pt-3" style="font-weight: 800;">Rs. {{ number_format($item->gross_salary, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="section-title">Deductions (Debits)</div>
                        <table class="table table-borderless table-sm salary-table">
                            <tbody>
                                @if(empty($item->deductions_detail))
                                    <tr><td class="text-muted italic small py-4 text-center">No statutory deductions recorded.</td></tr>
                                @else
                                    @foreach($item->deductions_detail as $deduct)
                                    <tr class="text-danger">
                                        <td class="fw-semibold">{{ $deduct['name'] }}</td>
                                        <td class="text-end fw-bold">-{{ number_format($deduct['amount'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                @endif
                                <tr class="border-top">
                                    <td class="fw-800 pt-3" style="font-weight: 800;">TOTAL DEDUCTIONS</td>
                                    <td class="text-end fw-800 pt-3" style="font-weight: 800;">Rs. {{ number_format($item->total_deductions, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="total-box mt-5 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-800 text-slate-800" style="font-weight: 800;">NET DISBURSEMENT</h5>
                        <p class="text-muted small mb-0 fw-semibold">Final amount credited after institutional audits.</p>
                    </div>
                    <div class="text-end">
                        <div class="net-amount">Rs. {{ number_format($item->net_salary, 2) }}</div>
                    </div>
                </div>

                <div class="mt-5 text-center text-muted small pt-5 border-top opacity-50 fw-semibold">
                    ** Electronic Document: This is an automated financial record and does not require a physical signature for internal institutional use. **
                </div>
            </div>
        </div>
    </div>
</body>
</html>
