<div class="d-flex align-items-center justify-content-center gap-2">
    @if($canEdit)
    <a href="javascript:void(0)" class="btn-action-premium btn-action-edit edit_shift" data-id="{{ $shift->id }}" title="Edit Configuration">
        <i class="fas fa-edit small"></i>
    </a>
    @endif
    @if($canDelete)
    <a href="javascript:void(0)" class="btn-action-premium btn-action-delete delete_shift" data-id="{{ $shift->id }}" title="Delete Shift">
        <i class="fas fa-trash-alt small"></i>
    </a>
    @endif
</div>