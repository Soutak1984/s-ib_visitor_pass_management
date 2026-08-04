<!-- Stack for additional scripts -->
@stack('scripts')

<!-- JS Libraries -->
<script src="{{ asset('frontend/js/jquery.js') }}"></script>
<script src="{{ asset('frontend/js/script.js') }}"></script>
<script src="{{ asset('assets/modules/izitoast/dist/js/iziToast.min.js') }}"></script>

<!-- Custom Scripts -->
@yield('scripts')

<!-- CSRF Token for AJAX requests -->
<script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // iziToast Notifications for success and error messages
    @if(session('success'))
        iziToast.success({
            title: 'Success',
            message: "{{ session('success') }}",
            position: 'topRight'
        });
    @endif

    @if(session('error'))
        iziToast.error({
            title: 'Error',
            message: "{{ session('error') }}",
            position: 'topRight'
        });
    @endif
</script>
