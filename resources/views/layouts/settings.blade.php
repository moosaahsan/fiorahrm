<div class="page-title-box">
    <div class="row align-items-center">
        @yield('breadcrumb')
        @hasSection('button')
            <div class="col-sm-6">
                <div class="float-sm-right text-sm-right page-header-actions">
                    @yield('button')
                </div>
            </div>
        @endif
    </div>
</div>
<!-- end row -->
