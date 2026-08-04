<!-- General JS Scripts -->
<script src="{{ asset('assets/modules/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('assets/modules/popper.js/dist/popper.min.js') }}"></script>
<script src="{{ asset('assets/modules/bootstrap/dist/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/modules/jquery.nicescroll/dist/jquery.nicescroll.min.js') }}"></script>
<script src="{{ asset('assets/modules/moment/min/moment.min.js') }}"></script>
<script src="{{ asset('assets/js/dropzone.min.js') }}"></script>
<script src="{{ asset('assets/js/stisla.js') }}"></script>

<!-- JS Libraies -->
<script src="{{ asset('assets/modules/izitoast/dist/js/iziToast.min.js') }}"></script>
@yield('scripts')

<!-- Template JS File -->
<script src="{{ asset('assets/js/scripts.js') }}"></script>
<script src="{{ asset('js/custom.js') }}"></script>
<script src="https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.3.2/firebase.js"></script>

<script type="text/javascript">
    @if(session('success'))
    iziToast.success({
        title: 'Success',
        message: '{{ session('
        success ') }}',
        position: 'topRight'
    });
    @endif

    @if(session('error'))
    iziToast.error({
        title: 'Error',
        message: '{{ session('
        error ') }}',
        position: 'topRight'
    });
    @endif
    var beep = document.getElementById("myAudio1");

    function sound() {
        beep.play();
    }
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // web_token
        var firebaseConfig = {
            apiKey: "{{ setting('apiKey') }}",
            authDomain: "{{ setting('authDomain') }}",
            projectId: "{{ setting('projectId') }}",
            storageBucket: "{{ setting('storageBucket') }}",
            messagingSenderId: "{{ setting('messagingSenderId') }}",
            appId: "{{ setting('appId') }}",
            measurementId: "{{ setting('measurementId') }}"
        };
        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();
        startFCM();

        function startFCM() {
            messaging.requestPermission()
                .then(function() {
                    return messaging.getToken()
                })
                .then(function(response) {
                    $.ajax({
                        url: '{{ route("admin.store.token") }}',
                        type: 'POST',
                        data: {
                            token: response
                        },
                        dataType: 'JSON',
                        success: function(response) {

                        },
                        error: function(error) {

                        },
                    });
                }).catch(function(error) {

                });
        }
        messaging.onMessage(function(payload) {
            const title = payload.notification.title;
            const body = payload.notification.body;
            sound();
            new Notification(title, {
                body: body,
            });
        });
    });
</script>