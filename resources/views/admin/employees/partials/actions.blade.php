<div class="action-btns d-flex flex-row flex-nowrap">
    @can('edit-employee')
        <a href="{{ route('admin.employees.edit', $employee->id) }}" title="Edit Profile" class="btn-saas-action btn-edit">
            <i class="fa fa-edit"></i>
        </a>
    @endcan

    @can('view-employee')
        <a href="{{ route('admin.employees.show', $employee->id) }}" title="View Spotlight" class="btn-saas-action btn-view">
            <i class="fa fa-eye"></i>
        </a>
    @endcan

    @if(!$employee->resign_date)
        @can('offboard-employee')
            <a href="#" title="Offboard Employee" class="btn-saas-action btn-resign resign-employee"
                data-id="{{ $employee->id }}" data-name="{{ $employee->name }}">
                <i class="fa fa-user-slash"></i>
            </a>
        @endcan
    @else
        @can('create-employee')
            <a href="#" title="Recall / Re-onboard" class="btn-saas-action btn-recall rejoin-employee"
                data-id="{{ $employee->id }}" data-name="{{ $employee->name }}" data-joining="{{ $employee->joining_date }}">
                <i class="fa fa-user-plus"></i>
            </a>
        @endcan
    @endif

    @can('delete-employee')
        <a href="#" title="Delete Permanent" class="btn-saas-action btn-delete delete-employee"
            data-id="{{ $employee->id }}" data-name="{{ $employee->name }}">
            <i class="fa fa-trash"></i>
        </a>
    @endcan

    @can('manage-leave-balances')
        <a href="#" title="Manage Leave Balances" class="btn-saas-action btn-leaves trigger_ajax_modal"
            data-id="{{ $employee->id }}" data-action="manage_leaves">
            <i class="fa fa-calendar-check"></i>
        </a>
    @endcan
</div>