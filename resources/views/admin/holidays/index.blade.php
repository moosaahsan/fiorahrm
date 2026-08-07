@extends('admin.layouts.app')

@section('title', 'Holiday Management matrix')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('breadcrumb')
<div class="mb-4 flex w-full items-center justify-between">
    <div>
        <h3 class="tw-page-title text-2xl">Holiday Intelligence Matrix</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb flex flex-wrap gap-1 bg-transparent p-0 m-0 text-xs font-bold uppercase tracking-wide">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-slate-500 hover:text-brand-600">Governance</a></li>
                <li class="breadcrumb-item active text-brand-600">Absence Configuration</li>
            </ol>
        </nav>
    </div>
    @can('create-holiday')
    <button class="tw-btn-primary rounded-full px-6 py-2.5 shadow-lg shadow-brand-500/30" data-toggle="modal" data-target="#addHolidayModal">
        <i class="bi bi-calendar-plus"></i> Add New Holiday
    </button>
    @endcan
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="tw-stat-card">
            <div class="icon-container">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div>
                <div class="stat-label">Total Volume</div>
                <div class="stat-value">{{ $summary['total'] }}</div>
            </div>
        </div>
        <div class="tw-stat-card">
            <div class="icon-container !bg-emerald-50 !text-emerald-500">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <div class="stat-label">Upcoming Events</div>
                <div class="stat-value">{{ $summary['upcoming'] }}</div>
            </div>
        </div>
        <div class="tw-stat-card">
            <div class="icon-container !bg-amber-50 !text-amber-500">
                <i class="bi bi-globe"></i>
            </div>
            <div>
                <div class="stat-label">Global Scope</div>
                <div class="stat-value">{{ $summary['global'] }}</div>
            </div>
        </div>
        <div class="tw-stat-card">
            <div class="icon-container !bg-slate-100 !text-slate-500">
                <i class="bi bi-diagram-3"></i>
            </div>
            <div>
                <div class="stat-label">Regional Scope</div>
                <div class="stat-value">{{ $summary['regional'] }}</div>
            </div>
        </div>
    </div>

    <div class="tw-command-hub">
        <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-12">
            <div class="md:col-span-3">
                <label class="tw-filter-label">Target Branch</label>
                <select id="filter-branch" class="tw-form-input h-12 py-0">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ session('active_branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="tw-filter-label">Regional Target (Team)</label>
                <select id="filter-team" class="tw-form-input h-12 py-0">
                    <option value="">All Regions / Teams</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="tw-filter-label">Event Taxonomy</label>
                <select id="filter-type" class="tw-form-input h-12 py-0">
                    <option value="">All Taxonomies</option>
                    <option value="Holiday">Holiday</option>
                    <option value="Event">Event</option>
                    <option value="Weekend">Weekend</option>
                </select>
            </div>
            <div class="text-right md:col-span-3">
                <button id="reset-filters" class="tw-btn-secondary rounded-xl px-5 py-2.5">
                    <i class="bi bi-arrow-counterclockwise mr-1"></i> Reset Matrix
                </button>
            </div>
        </div>
    </div>

    <div class="tw-directory-card overflow-hidden p-0">
        <div class="p-4">
            <table id="holidays-table" class="table w-100 tw-admin-table">
                <thead>
                    <tr>
                        <th>Holiday Entity</th>
                        <th>Office context</th>
                        <th>Scoped Coverage</th>
                        <th>Temporal Window</th>
                        <th>Classification</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addHolidayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content premium-modal-content">
            <div class="modal-identity-header">
                <div class="flex items-start justify-between pr-14">
                    <div>
                        <h4 class="mb-1 mt-0 font-display text-xl font-extrabold text-slate-900">Log New Holiday</h4>
                        <p class="mb-0 text-xs font-bold uppercase tracking-wide text-slate-500">Absence Governance Protocol</p>
                    </div>
                </div>
                <button type="button" class="close-modal-saas" data-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form action="{{ route('admin.holidays.store') }}" method="POST">
                @csrf
                <div class="modal-body p-6">
                    <div class="mb-4">
                        <label class="tw-filter-label">Holiday Designation (Name)</label>
                        <input type="text" name="note" class="tw-form-input" placeholder="e.g. Independence Day 2026" required>
                    </div>
                    <div class="mb-4">
                        <label class="tw-filter-label">Office Context (Branch)</label>
                        <select name="branch_id" class="tw-form-input h-12 py-0" required>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ session('active_branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="tw-filter-label">Start Cycle</label>
                            <input type="date" name="start_date" class="tw-form-input" required>
                        </div>
                        <div>
                            <label class="tw-filter-label">End Cycle</label>
                            <input type="date" name="end_date" class="tw-form-input" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="tw-filter-label">Event Taxonomy</label>
                        <select name="type" class="tw-form-input h-12 py-0" required>
                            <option value="Holiday">Holiday</option>
                            <option value="Event">Event</option>
                            <option value="Weekend">Weekend</option>
                        </select>
                    </div>
                    <div>
                        <label class="tw-filter-label">Scoped Target (Teams)</label>
                        <select name="team_ids[]" class="select2-premium tw-form-input" multiple="multiple">
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                        <small class="mt-2 block text-xs font-bold uppercase tracking-wide text-slate-500">
                            <i class="bi bi-info-circle mr-1"></i> Leave empty for branch-wide impact.
                        </small>
                    </div>
                </div>
                <div class="border-0 p-6 pt-0">
                    <button type="submit" class="tw-btn-dark h-14 w-full rounded-2xl text-base font-bold shadow-sm">Commit to Matrix</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editHolidayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content premium-modal-content">
            <div class="modal-identity-header">
                <div class="flex items-start justify-between pr-14">
                    <div>
                        <h4 class="mb-1 mt-0 font-display text-xl font-extrabold text-slate-900">Refine Holiday Logic</h4>
                        <p class="mb-0 text-xs font-bold uppercase tracking-wide text-slate-500">Record Management Protocol</p>
                    </div>
                </div>
                <button type="button" class="close-modal-saas" data-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form id="edit-holiday-form" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-6">
                    <div class="mb-4">
                        <label class="tw-filter-label">Holiday Designation</label>
                        <input type="text" name="note" id="edit-note" class="tw-form-input" required>
                    </div>
                    <div class="mb-4">
                        <label class="tw-filter-label">Office Context (Branch)</label>
                        <select name="branch_id" id="edit-branch" class="tw-form-input h-12 py-0" required>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="tw-filter-label">Start Cycle</label>
                            <input type="date" name="start_date" id="edit-start" class="tw-form-input" required>
                        </div>
                        <div>
                            <label class="tw-filter-label">End Cycle</label>
                            <input type="date" name="end_date" id="edit-end" class="tw-form-input" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="tw-filter-label">Event Taxonomy</label>
                        <select name="type" id="edit-type" class="tw-form-input h-12 py-0" required>
                            <option value="Holiday">Holiday</option>
                            <option value="Event">Event</option>
                            <option value="Weekend">Weekend</option>
                        </select>
                    </div>
                    <div>
                        <label class="tw-filter-label">Scoped Target (Teams)</label>
                        <select name="team_ids[]" id="edit-teams" class="select2-premium tw-form-input" multiple="multiple">
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="border-0 p-6 pt-0">
                    <button type="submit" class="tw-btn-dark h-14 w-full rounded-2xl text-base font-bold shadow-sm">Apply Strategic Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const table = $('#holidays-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.holidays.data') }}",
                data: function(d) {
                    d.branch_id = $('#filter-branch').val();
                    d.team_id = $('#filter-team').val();
                    d.type = $('#filter-type').val();
                }
            },
            columns: [
                {
                    data: 'note',
                    render: data => `<span class="holiday-note">${data || 'Unspecified'}</span>`
                },
                {
                    data: 'branch.name',
                    render: (data) => data ? `<span class="tw-badge border border-slate-200 bg-slate-50 px-3 py-2 text-slate-700"><i class="bi bi-geo-alt mr-1"></i> ${data}</span>` : '<span class="tw-badge-muted px-3 py-2">Global</span>'
                },
                {
                    data: 'team_info',
                    render: data => data.map(t => `<span class="team-chip">${t}</span>`).join('')
                },
                {
                    data: 'date_range',
                    render: data => `<div class="font-bold text-slate-600"><i class="bi bi-calendar-range mr-1"></i> ${data}</div>`
                },
                {
                    data: 'type',
                    render: data => `<span class="tw-badge border border-slate-200 bg-white px-3 py-2 text-xs font-extrabold uppercase tracking-wide">${data}</span>`
                },
                {
                    data: 'status',
                    render: data => {
                        const cls = data.toLowerCase();
                        return `<span class="status-pill ${cls}"><i class="bi bi-circle-fill" style="font-size:6px;"></i> ${data}</span>`;
                    }
                },
                {
                    data: 'id',
                    sortable: false,
                    render: (data, type, row) => {
                        let html = '<div class="d-flex gap-2">';
                        @can('edit-holiday')
                        html += `<button class="btn-saas-action edit-holiday" data-id="${data}" data-json='${JSON.stringify(row).replace(/'/g, "&apos;")}'><i class="bi bi-pencil-square"></i></button>`;
                        @endcan
                        @can('delete-holiday')
                        html += `<button class="btn-saas-action delete-holiday text-danger" data-id="${data}"><i class="bi bi-trash3"></i></button>`;
                        @endcan
                        html += '</div>';
                        return html;
                    }
                }
            ],
            language: {
                processing: '<i class="fas fa-spinner fa-spin fa-2x"></i>',
                search: "",
                searchPlaceholder: "Query Holiday Matrix...",
                lengthMenu: "_MENU_ entries"
            },
            order: [[3, 'desc']]
        });

        $('.dataTables_filter input').addClass('tw-form-input').css('width', '280px').css('padding', '8px 16px');

        $('#filter-branch, #filter-team, #filter-type').change(() => table.draw());
        $('#reset-filters').click(() => {
            $('#filter-branch, #filter-team, #filter-type').val('').trigger('change');
        });

        $(document).on('click', '.edit-holiday', function() {
            const data = $(this).data('json');
            $('#edit-holiday-form').attr('action', `/admin/holidays/${data.id}`);
            $('#edit-note').val(data.note);
            $('#edit-branch').val(data.branch_id);
            $('#edit-start').val(data.start_date.split('T')[0]);
            $('#edit-end').val(data.end_date.split('T')[0]);
            $('#edit-type').val(data.type);

            const teamIds = data.teams.map(t => t.id);
            $('#edit-teams').val(teamIds).trigger('change');

            $('#editHolidayModal').modal('show');
        });

        $(document).on('click', '.delete-holiday', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Terminate Holiday?',
                text: "This record will be purged from the global matrix.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Confirm Purge',
                cancelButtonText: 'Abort',
                background: '#fff',
                confirmButtonColor: '#0f172a'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(`/admin/holidays/${id}`, { _method: 'DELETE', _token: '{{ csrf_token() }}' }, () => {
                        table.draw();
                        toastr.success('Holiday purged successfully');
                    });
                }
            });
        });

        $('.select2-premium').select2({
            width: '100%',
            dropdownParent: $('#addHolidayModal')
        });

        $('#edit-teams').select2({
            width: '100%',
            dropdownParent: $('#editHolidayModal')
        });
    });
</script>
@endpush
