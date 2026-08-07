@php
    use App\Support\MetaCompliance;

    $metaPixelId = config('services.meta.pixel_id', '2121056755424403');
    $metaAllowsTracking = MetaCompliance::allowsMetaTracking(request());
    $metaCapiEnabled = (bool) config('services.meta.enabled')
        && filled(config('services.meta.access_token'))
        && $metaAllowsTracking;
    $metaCapiUrl = url('/meta/capi');
@endphp
@if ($metaAllowsTracking && filled($metaPixelId))
<!-- Meta Pixel Code (non-European visitors only — religious special-category compliance) -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('consent', 'grant');
fbq('init', @json($metaPixelId), {}, { autoConfig: false });
(function () {
    function metaCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : '';
    }
    function metaEventId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'pv_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
    }
    /** Drop labels that can encode interest / religious intent. */
    function sanitizeCustomData(data) {
        if (!data || typeof data !== 'object') return {};
        var allow = {
            value: 1, currency: 1, content_ids: 1, content_type: 1,
            num_items: 1, order_id: 1, status: 1, predicted_ltv: 1
        };
        var clean = {};
        for (var k in data) {
            if (Object.prototype.hasOwnProperty.call(data, k) && allow[k]) {
                clean[k] = data[k];
            }
        }
        return clean;
    }
    function cleanSourceUrl(href) {
        try {
            var u = new URL(href || window.location.href);
            return u.origin + u.pathname;
        } catch (e) {
            return window.location.origin + '/';
        }
    }
    window.almonajahMetaTrack = function (eventName, customData, eventId) {
        eventId = eventId || metaEventId();
        customData = sanitizeCustomData(customData || {});
        try {
            fbq('track', eventName, customData, { eventID: eventId });
        } catch (e) {}
        @if ($metaCapiEnabled)
        try {
            var payload = JSON.stringify({
                event_name: eventName,
                event_id: eventId,
                event_source_url: cleanSourceUrl(window.location.href),
                fbp: metaCookie('_fbp'),
                fbc: metaCookie('_fbc'),
                custom_data: customData
            });
            if (navigator.sendBeacon) {
                navigator.sendBeacon(@json($metaCapiUrl), new Blob([payload], { type: 'application/json' }));
            } else {
                fetch(@json($metaCapiUrl), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: payload,
                    keepalive: true,
                    credentials: 'same-origin'
                });
            }
        } catch (e) {}
        @endif
        return eventId;
    };
    window.almonajahMetaTrack('PageView');
})();
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={{ urlencode($metaPixelId) }}&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
@else
<script>
/* Meta tracking disabled for European Region visitors (special-category compliance). */
window.almonajahMetaTrack = function () { return null; };
</script>
@endif
