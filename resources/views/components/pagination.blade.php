@if ($paginator->hasPages())
    <div class="d-flex justify-content-end">
        {{ $paginator->links() }}
    </div>
@endif
