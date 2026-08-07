{{-- resources/views/components/datatable.blade.php --}}

<div class="card">
    <div class="card-body">

        @if (isset($filters))
            {{ $filters }}
        @endif


        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-bordered table-hover nowrap" id="{{ $id ?? 'datatable' }}" style="width:100%;">
                <thead class="bg-primary text-white text-center">
                    <tr>
                        @foreach ($columns as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="text-center">
                    {{ $slot }}
                </tbody>
            </table>
        </div>

        {{-- Pagination if needed --}}
        <div class="mt-3">
            {{ $pagination ?? '' }}
        </div>

    </div>
</div>