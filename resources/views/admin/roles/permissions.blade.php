@extends('admin.layouts.app')

@section('title', 'Permission Matrix Hub')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row align-items-center mb-5">
        <div class="col-md-7">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0 mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}" class="text-muted text-decoration-none small text-uppercase ls-1 fw-bold">Governance</a></li>
                    <li class="breadcrumb-item active small fw-bold text-indigo text-uppercase ls-1">Matrix Protocol</li>
                </ol>
            </nav>
            <h1 class="tw-page-title mb-0 text-3xl">Modify Access Layer: <span class="text-brand-600">{{ $role->name }}</span></h1>
            <p class="text-muted fw-bold mt-2"><i class="bi bi-shield-check mr-1"></i> Configure granular capabilities and environmental permissions for this identity.</p>
        </div>
        <div class="col-md-5">
            <div class="search-container">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="matrix-search" class="search-input shadow-sm" placeholder="Filter capabilities (e.g. 'delete', 'view')...">
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <div class="matrix-sidebar shadow-sm">
                <div class="mb-4">
                    <label class="text-uppercase ls-2 small fw-800 text-muted mb-3 d-block">Module Index</label>
                    <div class="module-nav">
                        @foreach($permissions as $module => $items)
                            <a href="#module-{{ \Illuminate\Support\Str::slug($module ?: 'General') }}" class="module-link">
                                <i class="bi bi-{{ $loop->index % 2 == 0 ? 'grid' : 'layers' }}"></i>
                                <span>{{ $module ?: 'General' }}</span>
                                <span class="tw-badge-muted ml-auto rounded-pill border">{{ count($items) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 p-3 rounded-4 bg-light border border-dashed">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-info-circle text-indigo"></i>
                        <span class="fw-800 small text-uppercase ls-1">Matrix Fact</span>
                    </div>
                    <p class="small text-muted mb-0 fw-600">Permissions are synchronized instantly across all users assigned to this role.</p>
                </div>
            </div>
        </div>

        <!-- Main Matrix Content -->
        <div class="col-lg-9">
            <form id="matrix-form">
                @csrf
                @foreach($permissions as $module => $items)
                    <div class="permission-card" id="module-{{ \Illuminate\Support\Str::slug($module ?: 'General') }}">
                        <div class="card-header-premium">
                            <h3 class="card-title-premium">
                                <i class="bi bi-collection-play text-indigo"></i>
                                {{ $module ?: 'General' }} Protocol Suite
                            </h3>
                            <div class="d-flex align-items-center gap-3">
                                <span class="small fw-800 text-muted text-uppercase ls-1 mr-2 module-count">0 / {{ count($items) }} Scoped</span>
                                <button type="button" class="tw-btn-secondary text-sm fw-bold rounded-pill border px-3 select-all-module">
                                    Toggle All
                                </button>
                            </div>
                        </div>
                        <div class="switch-container">
                            @foreach($items as $permission)
                                @php
                                    $active = in_array($permission->id, $rolePermissions);
                                    $displayName = str_replace(['-', '_'], ' ', $permission->name);
                                    
                                    // Smart Icon Logic
                                    $icon = 'bi-shield';
                                    if (Str::contains($permission->name, 'view')) $icon = 'bi-eye';
                                    elseif (Str::contains($permission->name, ['create', 'add'])) $icon = 'bi-plus-circle';
                                    elseif (Str::contains($permission->name, 'edit')) $icon = 'bi-pencil-square';
                                    elseif (Str::contains($permission->name, ['delete', 'remove', 'terminate'])) $icon = 'bi-trash3';
                                    elseif (Str::contains($permission->name, ['manage', 'control'])) $icon = 'bi-gear-wide-connected';
                                    elseif (Str::contains($permission->name, ['approve', 'reject'])) $icon = 'bi-check2-circle';
                                @endphp
                                <div class="permission-item {{ $active ? 'active' : '' }}" onclick="togglePermission(this)">
                                    <div class="permission-info">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi {{ $icon }} {{ $active ? 'text-indigo' : 'text-muted' }}" style="font-size: 1.1rem"></i>
                                            <span class="permission-name text-capitalize">{{ $displayName }}</span>
                                        </div>
                                        <span class="permission-description">Access authorization for {{ strtolower($displayName) }} suite.</span>
                                    </div>
                                    <label class="saas-switch mb-0">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                                               {{ $active ? 'checked' : '' }} class="perm-checkbox">
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </form>
        </div>
    </div>
</div>

<!-- Floating Sync Hub -->
<div class="floating-action-bar" id="sync-hub" style="display: none;">
    <div class="d-flex align-items-center">
        <div class="spinner-border spinner-border-sm text-indigo mr-3 d-none" id="sync-spinner"></div>
        <span class="fw-bold"><span id="changes-count">0</span> Protocol adjustments detected</span>
    </div>
    <div style="width: 1px; height: 24px; background: rgba(255,255,255,0.2);"></div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sync" onclick="submitMatrix()">
            Commit Protocol
        </button>
        <button type="button" class="tw-btn-secondary text-sm rounded-pill px-3 border-0" onclick="location.reload()">
            Discard
        </button>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function togglePermission(el) {
        const checkbox = $(el).find('input[type="checkbox"]');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    }

    function updateModuleCounts() {
        $('.permission-card').each(function() {
            const total = $(this).find('.perm-checkbox').length;
            const checked = $(this).find('.perm-checkbox:checked').length;
            $(this).find('.module-count').text(`${checked} / ${total} Scoped`);
            
            // Update item styling
            $(this).find('.permission-item').each(function() {
                if ($(this).find('input').is(':checked')) {
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });
        });
    }

    function submitMatrix() {
        const $btn = $('.btn-sync');
        const $spinner = $('#sync-spinner');
        
        $btn.prop('disabled', true).text('Synchronizing...');
        $spinner.removeClass('d-none');
        
        $.ajax({
            url: "{{ route('admin.roles.update_permissions', $role->id) }}",
            method: 'POST',
            data: $('#matrix-form').serialize(),
            success: function(response) {
                toastr.success(response.message);
                $('#sync-hub').fadeOut();
                initialState = $('#matrix-form').serialize();
            },
            error: function() {
                toastr.error('Sync failure: Access layer could not be committed.');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Commit Protocol');
                $spinner.addClass('d-none');
            }
        });
    }

    let initialState = '';

    $(document).ready(function() {
        initialState = $('#matrix-form').serialize();
        updateModuleCounts();

        // Detect Changes
        $(document).on('change', '.perm-checkbox', function() {
            const currentState = $('#matrix-form').serialize();
            if (currentState !== initialState) {
                $('#sync-hub').fadeIn().addClass('has-changes');
                // Calculate actual changes count (crude but fast)
                const diff = currentState.split('permissions%5B%5D=').length - initialState.split('permissions%5B%5D=').length;
                $('#changes-count').text(Math.abs(diff) || 'Multiple');
            } else {
                $('#sync-hub').fadeOut();
            }
            updateModuleCounts();
        });

        // Select All Handler
        $('.select-all-module').click(function(e) {
            e.stopPropagation();
            const card = $(this).closest('.permission-card');
            const checkboxes = card.find('.perm-checkbox');
            const allChecked = checkboxes.length === card.find('.perm-checkbox:checked').length;
            
            checkboxes.prop('checked', !allChecked).trigger('change');
        });

        // Matrix Search Filter
        $('#matrix-search').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('.permission-item').each(function() {
                const name = $(this).find('.permission-name').text().toLowerCase();
                if (name.indexOf(value) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            
            // Hide empty cards
            $('.permission-card').each(function() {
                const hasVisible = $(this).find('.permission-item:visible').length > 0;
                $(this).toggle(hasVisible);
            });
        });

        // Smooth Scroll Sidebar
        $('.module-link').click(function(e) {
            e.preventDefault();
            const target = $(this).attr('href');
            $('html, body').animate({
                scrollTop: $(target).offset().top - 100
            }, 500);
            $('.module-link').removeClass('active');
            $(this).addClass('active');
        });

        // Scroll Spy (simple)
        $(window).scroll(function() {
            const scrollPos = $(document).scrollTop();
            $('.permission-card').each(function() {
                const top = $(this).offset().top - 120;
                const bottom = top + $(this).outerHeight();
                if (scrollPos >= top && scrollPos <= bottom) {
                    const id = $(this).attr('id');
                    $('.module-link').removeClass('active');
                    $(`.module-link[href="#${id}"]`).addClass('active');
                }
            });
        });

        // Prevent label click from double triggering
        $('.saas-switch').click(function(e) {
            e.stopPropagation();
        });
    });
</script>
@endpush
