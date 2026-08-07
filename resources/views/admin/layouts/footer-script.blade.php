
<!-- <script src="{{ URL::asset('assets/js/jquery.min.js') }}"></script> -->
<script src="{{ URL::asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ URL::asset('assets/js/metisMenu.min.js') }}"></script>
<script src="{{ URL::asset('assets/js/jquery.slimscroll.js') }}"></script>
<script src="{{ URL::asset('assets/js/waves.min.js') }}"></script>

@yield('script')

<!-- App js-->
<script src="{{ URL::asset('assets/js/app.js') }}"></script>
<script src="{{ URL::asset('assets/js/custom.js') }}"></script>



{{-- <script src="{{ URL::asset('plugins/sweet-alert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('assets/pages/sweet-alert.init.js') }}"></script> --}}
{{-- <script src="/js/sweetalert.min.js"></script> --}}
<!-- Responsive-table-->
<script src="{{ URL::asset('plugins/RWD-Table-Patterns/dist/js/rwd-table.min.js') }}"></script>
<!-- Required datatable js -->
<script src="{{ URL::asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>
<!-- Buttons examples -->
<script src="{{ URL::asset('plugins/datatables/dataTables.buttons.min.js') }}"></script>
<script src="{{ URL::asset('plugins/datatables/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('plugins/datatables/jszip.min.js') }}"></script>
<script src="{{ URL::asset('plugins/datatables/pdfmake.min.js') }}"></script>
<script src="{{ URL::asset('plugins/datatables/vfs_fonts.js') }}"></script>
<script src="{{ URL::asset('plugins/datatables/buttons.html5.min.js') }}"></script>
<script src="{{ URL::asset('plugins/datatables/buttons.print.min.js') }}"></script>
<script src="{{ URL::asset('plugins/datatables/buttons.colVis.min.js') }}"></script>
<!-- Responsive examples -->
<script src="{{ URL::asset('plugins/datatables/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('plugins/datatables/responsive.bootstrap4.min.js') }}"></script>

<!-- Datatable init js -->
<script src="{{ URL::asset('assets/pages/datatables.init.js') }}"></script>
@if (isset($slug) && $slug == 'admin')
    <script src="{{ URL::asset('assets/pages/datatables_admin.init.js') }}"></script>
@endif
{{-- <script src="{{ URL::asset('plugins/select2/js/select2.full.min.js') }}"></script> --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- ---------birthday slider----------- -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.6.0/slick.js"></script>




{{-- moment (used by attendance page) --}}
<script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/min/moment.min.js"></script>

{{-- DataTables core, THEN Bootstrap 5 integration --}}
<!-- <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script> -->

{{-- flatpickr (calendar) --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  // Robust init: runs immediately if DOM is ready, otherwise on DOMContentLoaded
  (function initFlatpickr() {
    function mount() {
      if (typeof flatpickr === 'function') {
        flatpickr('.datepicker', {
          dateFormat: 'Y-m-d',
          yearRange: '1900:' + new Date().getFullYear(),
        });
      }
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', mount);
    } else {
      mount();
    }
  })();
</script>

{{-- SweetAlert2 + your mixin --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  window.swalWithBootstrapButtons = Swal.mixin({
    customClass: { confirmButton: 'btn btn-success', cancelButton: 'btn btn-danger' },
    buttonsStyling: true
  });
</script>

{{-- Slick carousel (depends on jQuery) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.6.0/slick.js"></script>
<script>
  $(function () {
    $('.birthday-slider').slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      autoplay: true,
      autoplaySpeed: 3000,
      arrows: false,
      dots: false,
      pauseOnHover: false,
      responsive: [
        { breakpoint: 768, settings: { slidesToShow: 1 } },
        { breakpoint: 520, settings: { slidesToShow: 1 } }
      ]
    });
  });
</script>

{{-- Your global app JS --}}
<script src="{{ URL::asset('assets/js/app.js') }}"></script>
<script src="{{ URL::asset('assets/js/custom.js') }}"></script>

{{-- Optional: your page-level sections (if you use them) --}}
@yield('script')
@yield('script-bottom')

{{-- IMPORTANT: render Blade pushed scripts AFTER all vendor libs --}}
@stack('scripts')

{{-- Optional: other page-specific inline scripts from your earlier footer (kept as-is) --}}
<script>
  // Break form logic (unchanged)
  const breakTypeEl = document.getElementById('break_type');
  if (breakTypeEl) {
    breakTypeEl.addEventListener('change', function () {
      var reasonContainer = document.getElementById('reason-container');
      if (!reasonContainer) return;
      reasonContainer.style.display = (this.value === 'official') ? 'block' : 'none';
    });
  }

  const breakForm = document.getElementById('breakForm');
  if (breakForm) {
    breakForm.addEventListener('submit', function (e) {
      var breakType = document.getElementById('break_type')?.value || '';
      var reasonField = document.getElementById('reason');
      if (breakType === 'official' && (!reasonField || !reasonField.value.trim())) {
        e.preventDefault();
        alert('Please provide a reason for the official break.');
      }
    });
  }

@if(isset($total_count))
    $(function () {
      var tableId = "{{ $slug === 'admin' ? 'datatable-buttons' : 'datatable' }}";
      let page_length = "{{$total_count}}";
      if ($.fn.DataTable.isDataTable('#' + tableId)) {
        $('#' + tableId).DataTable().destroy();
      }
      $('#' + tableId).DataTable({
        columnDefs: [{ type: 'date-eu', targets: 0 }],
        order: [[0, 'desc']],
        pageLength: page_length,
      });
    });
@endif
</script>
