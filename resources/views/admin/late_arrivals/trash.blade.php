@extends('admin.layouts.app')

@section('title', 'Late Arrivals Recycle Bin')

@section('breadcrumb')
    <div class="col-sm-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('admin.late_arrivals.index') }}" class="text-muted text-decoration-none small text-uppercase ls-1 fw-bold">Late Arrivals</a></li>
                        <li class="breadcrumb-item active small fw-bold text-danger text-uppercase ls-1" aria-current="page">Recycle Bin</li>
                    </ol>
                </nav>
                <h3 class="page-title mb-0" style="font-size: 2rem;">Archived Incidents</h3>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('admin.late_arrivals.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2" style="border-radius: 50px; padding: 10px 24px; font-weight: 700;">
                    <i class="bi bi-arrow-left"></i> Back to Logs
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid late-trash-page">
        <div class="premium-table-card">
            <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="fw-extrabold mb-0 text-dark" style="font-family: 'Outfit'; font-weight: 800;">Purged Records</h5>
                    <p class="text-muted small mb-0">These records can be restored to the active matrix</p>
                </div>
            </div>
            <div class="card-body p-1">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="trash-table">
                        <thead>
                            <tr>
                                <th>Incident ID</th>
                                <th>Personnel</th>
                                <th>Date</th>
                                <th>Late Variance</th>
                                <th>Root Cause</th>
                                <th>Deleted At</th>
                                <th class="text-end">Recovery</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lateArrivals as $late)
                                <tr>
                                    <td><span class="fw-bold text-muted small">#{{ $late->id }}</span></td>
                                    <td>
                                        <div class="fw-extrabold text-dark" style="font-family: 'Outfit'; font-size: 0.95rem;">
                                            {{ $late->employee->name ?? 'Unknown' }}
                                        </div>
                                        <div class="small text-muted">AST-{{ $late->emp_id }}</div>
                                    </td>
                                    <td class="fw-bold">{{ \Carbon\Carbon::parse($late->date)->format('d M, Y') }}</td>
                                    <td>
                                        @php
                                            $hours = floor($late->late_minutes / 60);
                                            $minutes = $late->late_minutes % 60;
                                            $formatted = ($hours ? $hours . 'h ' : '') . ($minutes ? $minutes . 'm' : '');
                                        @endphp
                                        <span class="saas-status-badge late"><i class="bi bi-clock"></i> {{ $formatted }}</span>
                                    </td>
                                    <td class="text-muted small" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $late->late_reason ?? 'No reason provided' }}
                                    </td>
                                    <td class="text-muted small fw-bold">
                                        {{ \Carbon\Carbon::parse($late->deleted_at)->format('d M, Y • h:i A') }}
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('admin.late_arrivals.restore', $late->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-saas-restore">
                                                <i class="bi bi-arrow-counterclockwise"></i> Restore
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-top">
                    {{ $lateArrivals->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#trash-table').DataTable({
                paging: false,
                searching: true,
                responsive: true,
                dom: '<"p-4 d-flex justify-content-between align-items-center"f>rt',
                language: {
                    search: "",
                    searchPlaceholder: "Search archived logs..."
                }
            });
            $('.dataTables_filter input').addClass('form-control').css({
                'border-radius': '12px',
                'padding': '10px 18px',
                'border': '2px solid #f1f5f9',
                'min-width': '250px'
            });
        });
    </script>
@endpush