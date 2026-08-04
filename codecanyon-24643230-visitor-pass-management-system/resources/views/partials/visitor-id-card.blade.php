{{--
    Compact Visitor ID Card (Untitled4 style)
    Used on: admin/visitors/show + check-in/show
    Styles are embedded so PRINT also looks correct (no missing CSS).
--}}
@php
    $v = $visitingDetails;
    $visitor = $v->visitor ?? null;
    $employee = $v->employee ?? null;

    if (method_exists($v, 'hasVisitorImage') && $v->hasVisitorImage()) {
        $photo = $v->images;
    } elseif (!empty($v['photo'] ?? null)) {
        $photo = $v['photo'];
    } elseif ($visitor && \App\Enums\Status::MALE == $visitor->gender) {
        $photo = asset('/frontend/images/avatars/avatar5.png');
    } else {
        $photo = asset('/frontend/images/avatars/avatar4.png');
    }

    $logo = asset('images/' . setting('site_logo'));
@endphp

{{-- Embedded styles travel with the HTML into the print iframe --}}
<style>
.visitor-idcard{width:440px!important;max-width:100%!important;margin:0 auto!important;background:#fff!important;border-radius:12px!important;overflow:hidden!important;box-shadow:0 10px 30px rgba(58,54,148,.12)!important;font-family:"Plus Jakarta Sans","Segoe UI",Arial,Helvetica,sans-serif!important;color:#111827!important;box-sizing:border-box!important;display:block!important}
.visitor-idcard *,.visitor-idcard *::before,.visitor-idcard *::after{box-sizing:border-box!important}
.visitor-idcard__header{background:linear-gradient(90deg,#496FD7 0%,#46A5ED 100%)!important;padding:14px 16px!important;display:flex!important;flex-direction:row!important;justify-content:space-between!important;align-items:center!important;gap:10px!important;width:100%!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}
.visitor-idcard__brand{display:flex!important;flex-direction:row!important;align-items:center!important;gap:8px!important;min-width:0!important}
.visitor-idcard__logo-wrap{width:32px!important;height:32px!important;min-width:32px!important;max-width:32px!important;background:#fff!important;border-radius:8px!important;display:flex!important;align-items:center!important;justify-content:center!important;overflow:hidden!important;flex-shrink:0!important}
.visitor-idcard__logo-wrap img{width:24px!important;height:24px!important;max-width:24px!important;max-height:24px!important;object-fit:contain!important;display:block!important;margin:0!important}
.visitor-idcard__site-name{margin:0!important;padding:0!important;color:#fff!important;font-size:12px!important;font-weight:700!important;line-height:1.3!important;white-space:nowrap!important}
.visitor-idcard__site-meta{text-align:right!important;color:#fff!important;font-size:10px!important;font-weight:400!important;line-height:1.4!important;flex-shrink:0!important}
.visitor-idcard__site-meta p{margin:0 0 3px 0!important;padding:0!important;color:#fff!important;font-size:10px!important;font-weight:400!important}
.visitor-idcard__site-meta p:last-child{margin-bottom:0!important}
.visitor-idcard__body{display:flex!important;flex-direction:row!important;align-items:flex-start!important;gap:16px!important;padding:20px 18px 16px 18px!important;width:100%!important;background:#fff!important}
.visitor-idcard__photo{width:128px!important;height:128px!important;min-width:128px!important;max-width:128px!important;border-radius:14px!important;overflow:hidden!important;background:#eef2f7!important;flex-shrink:0!important}
.visitor-idcard__photo img{width:128px!important;height:128px!important;max-width:128px!important;max-height:128px!important;object-fit:cover!important;display:block!important;border-radius:14px!important;margin:0!important}
.visitor-idcard__info{flex:1 1 auto!important;min-width:0!important}
.visitor-idcard__name{margin:0!important;padding:0!important;color:#496FD7!important;font-size:20px!important;font-weight:700!important;line-height:1.2!important;word-break:break-word!important}
.visitor-idcard__reg{margin:3px 0 0 0!important;padding:0!important;color:#1f2937!important;font-size:13px!important;font-weight:400!important;line-height:1.3!important}
.visitor-idcard__field{margin-top:11px!important}
.visitor-idcard__label{margin:0!important;padding:0!important;color:#496FD7!important;font-size:13px!important;font-weight:700!important;line-height:1.25!important}
.visitor-idcard__value{margin:2px 0 0 0!important;padding:0!important;color:#1f2937!important;font-size:13px!important;font-weight:400!important;line-height:1.35!important;word-break:break-word!important}
.visitor-idcard__vehicle{border-top:1px solid #eef0f4!important;padding:14px 18px 18px 18px!important;background:#fff!important;width:100%!important}
.visitor-idcard__vehicle-title{margin:0 0 10px 0!important;padding:0!important;color:#496FD7!important;font-size:15px!important;font-weight:700!important;line-height:1.3!important}
.visitor-idcard__vehicle-row{display:flex!important;flex-direction:row!important;justify-content:space-between!important;align-items:center!important;gap:12px!important;margin:0 0 7px 0!important;width:100%!important}
.visitor-idcard__vehicle-key{color:#6b7280!important;font-size:13px!important;font-weight:400!important;margin:0!important;padding:0!important}
.visitor-idcard__vehicle-val{color:#111827!important;font-size:13px!important;font-weight:600!important;text-align:right!important;margin:0!important;padding:0!important}
.visitor-idcard__vehicle-val.is-ok{color:#111827!important;font-weight:700!important}
.visitor-idcard__vehicle-val.is-not-ok{color:#dc2626!important;font-weight:700!important}
.visitor-idcard__vehicle-remarks{margin-top:2px!important}
.visitor-idcard__vehicle-remarks .visitor-idcard__vehicle-key{display:block!important;margin-bottom:3px!important}
.visitor-idcard__vehicle-remarks-text{margin:0!important;padding:0!important;color:#6b7280!important;font-size:13px!important;font-weight:400!important;line-height:1.4!important;word-break:break-word!important}
@media print{
  .visitor-idcard{box-shadow:none!important;page-break-inside:avoid!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}
  .visitor-idcard__header{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;background:linear-gradient(90deg,#496FD7 0%,#46A5ED 100%)!important}
}
</style>

<div class="visitor-idcard">
    {{-- Blue header --}}
    <div class="visitor-idcard__header">
        <div class="visitor-idcard__brand">
            <div class="visitor-idcard__logo-wrap">
                <img src="{{ $logo }}" alt="logo" width="24" height="24">
            </div>
            <p class="visitor-idcard__site-name">{{ setting('site_name') }}</p>
        </div>
        <div class="visitor-idcard__site-meta">
            @if(setting('site_address'))
                <p>{{ setting('site_address') }}</p>
            @endif
            @if(setting('site_email'))
                <p>E-mail: {{ setting('site_email') }}</p>
            @endif
        </div>
    </div>

    {{-- Photo left + details right --}}
    <div class="visitor-idcard__body">
        <div class="visitor-idcard__photo">
            <img src="{{ $photo }}" alt="{{ optional($visitor)->name ?? 'visitor' }}" width="128" height="128">
        </div>
        <div class="visitor-idcard__info">
            <p class="visitor-idcard__name">{{ optional($visitor)->name }}</p>
            <p class="visitor-idcard__reg">ID#{{ $v->reg_no }}</p>

            <div class="visitor-idcard__field">
                <p class="visitor-idcard__label">Phone</p>
                <p class="visitor-idcard__value">+{{ optional($visitor)->country_code }}{{ optional($visitor)->phone }}</p>
            </div>

            <div class="visitor-idcard__field">
                <p class="visitor-idcard__label">Host</p>
                <p class="visitor-idcard__value">{{ optional($employee)->name }}</p>
            </div>

            @if(!empty($v->entry_gate_number))
            <div class="visitor-idcard__field">
                <p class="visitor-idcard__label">Entry Gate Number</p>
                <p class="visitor-idcard__value">{{ $v->entry_gate_number }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Vehicle --}}
    @if(!empty($v->vehicle_number) || !empty($v->vehicle_compliance) || !empty($v->vehicle_remarks))
    <div class="visitor-idcard__vehicle">
        <p class="visitor-idcard__vehicle-title">Vehicle Details</p>

        @if(!empty($v->vehicle_number))
        <div class="visitor-idcard__vehicle-row">
            <span class="visitor-idcard__vehicle-key">Vehicle Number</span>
            <span class="visitor-idcard__vehicle-val">{{ $v->vehicle_number }}</span>
        </div>
        @endif

        @if(!empty($v->vehicle_compliance))
        <div class="visitor-idcard__vehicle-row">
            <span class="visitor-idcard__vehicle-key">Vehicle Compliance</span>
            <span class="visitor-idcard__vehicle-val {{ $v->vehicle_compliance == 'Ok' ? 'is-ok' : 'is-not-ok' }}">{{ $v->vehicle_compliance }}</span>
        </div>
        @endif

        @if(!empty($v->vehicle_remarks))
        <div class="visitor-idcard__vehicle-remarks">
            <span class="visitor-idcard__vehicle-key">Vehicle Remarks</span>
            <p class="visitor-idcard__vehicle-remarks-text">{{ $v->vehicle_remarks }}</p>
        </div>
        @endif
    </div>
    @endif
</div>
