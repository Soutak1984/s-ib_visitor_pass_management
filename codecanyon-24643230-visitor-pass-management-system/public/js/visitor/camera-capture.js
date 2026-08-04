/**
 * Admin visitor camera capture (create / edit pages)
 */
"use strict";

(function () {
    var stream = null;
    var video = document.getElementById('adminCameraVideo');
    var canvas = document.getElementById('adminCameraCanvas');
    var select = document.getElementById('adminCameraSelect');
    var capturedInput = document.getElementById('captured_image');
    var preview = document.getElementById('previewImage');
    var fileInput = document.getElementById('customFile');

    if (!video || !canvas || !select) {
        return;
    }

    function stopStream() {
        if (stream) {
            stream.getTracks().forEach(function (track) {
                track.stop();
            });
            stream = null;
        }
        video.srcObject = null;
    }

    function startCamera(deviceId) {
        stopStream();
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Camera is not supported in this browser. Use Browse File instead.');
            return;
        }

        var constraints = {
            video: deviceId
                ? { deviceId: { exact: deviceId }, width: { ideal: 1280 }, height: { ideal: 720 } }
                : { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false
        };

        navigator.mediaDevices.getUserMedia(constraints)
            .then(function (mediaStream) {
                stream = mediaStream;
                video.srcObject = mediaStream;
                video.play();
            })
            .catch(function (err) {
                console.error(err);
                alert('Unable to access camera. Allow camera permission or use Browse File.');
            });
    }

    function loadCameras() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
            return;
        }
        navigator.mediaDevices.enumerateDevices().then(function (devices) {
            var cameras = devices.filter(function (d) {
                return d.kind === 'videoinput';
            });
            select.innerHTML = '';
            if (!cameras.length) {
                select.innerHTML = '<option value="">No camera found</option>';
                return;
            }
            cameras.forEach(function (cam, index) {
                var opt = document.createElement('option');
                opt.value = cam.deviceId;
                opt.textContent = cam.label || ('Camera ' + (index + 1));
                select.appendChild(opt);
            });
            startCamera(select.value);
        });
    }

    function showUploadMode() {
        document.getElementById('uploadBox').classList.remove('d-none');
        document.getElementById('cameraBox').classList.add('d-none');
        document.getElementById('btnUseUpload').classList.add('active');
        document.getElementById('btnUseCamera').classList.remove('active');
        stopStream();
        if (capturedInput) {
            capturedInput.value = '';
        }
    }

    function showCameraMode() {
        document.getElementById('uploadBox').classList.add('d-none');
        document.getElementById('cameraBox').classList.remove('d-none');
        document.getElementById('btnUseCamera').classList.add('active');
        document.getElementById('btnUseUpload').classList.remove('active');
        if (fileInput) {
            fileInput.value = '';
        }
        // Permission prompt needs getUserMedia first so labels appear
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: true, audio: false })
                .then(function (s) {
                    s.getTracks().forEach(function (t) { t.stop(); });
                    loadCameras();
                })
                .catch(function () {
                    loadCameras();
                });
        } else {
            alert('Camera is not supported in this browser.');
            showUploadMode();
        }
    }

    document.getElementById('btnUseUpload') && document.getElementById('btnUseUpload').addEventListener('click', showUploadMode);
    document.getElementById('btnUseCamera') && document.getElementById('btnUseCamera').addEventListener('click', showCameraMode);

    select.addEventListener('change', function () {
        startCamera(select.value);
    });

    document.getElementById('btnCapturePhoto') && document.getElementById('btnCapturePhoto').addEventListener('click', function () {
        if (!video.videoWidth) {
            alert('Camera is not ready yet. Please wait a moment.');
            return;
        }
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        var dataUrl = canvas.toDataURL('image/png');
        if (capturedInput) {
            capturedInput.value = dataUrl;
        }
        if (preview) {
            preview.src = dataUrl;
        }
        if (fileInput) {
            fileInput.value = '';
        }
    });

    document.getElementById('btnRetakePhoto') && document.getElementById('btnRetakePhoto').addEventListener('click', function () {
        if (capturedInput) {
            capturedInput.value = '';
        }
        if (preview) {
            preview.src = preview.getAttribute('data-default') || preview.src;
        }
        startCamera(select.value);
    });

    // Stop camera when leaving page
    window.addEventListener('beforeunload', stopStream);
})();
