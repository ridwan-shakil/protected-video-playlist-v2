window.addEventListener( "load", function () {
    document.querySelectorAll( ".pvp-video-wrapper" ).forEach( function ( wrapper ) {

        // ── Disable controls ──────────────────────────────────────────────────
        if ( wrapper.dataset.disableVolume === "1" )     { wrapper.classList.add( "pvp-hide-volume" ); }
        if ( wrapper.dataset.disableControls === "1" )   { wrapper.classList.add( "pvp-hide-controls" ); }
        if ( wrapper.dataset.disableFullscreen === "1" ) { wrapper.classList.add( "pvp-hide-fullscreen" ); }
        if ( wrapper.dataset.disablePlaybutton === "1" ) { wrapper.classList.add( "pvp-hide-playbutton" ); }

        if ( wrapper.dataset.disableAutoplay === "1" ) {
            var iframe = wrapper.querySelector( "iframe" );
            if ( iframe ) {
                iframe.src = iframe.src.replace( "autoplay=1", "autoplay=0" );
            }
        }

        // ── Overlay handling ──────────────────────────────────────────────────
        var overlayEl = wrapper.querySelector( ".pvp-overlay-text" );
        if ( ! overlayEl ) {
            return;
        }


        // Always hide on page load
        overlayEl.style.display = "none";

        var onPause = overlayEl.dataset.overlayOnPause === "1";
        var onEnd   = overlayEl.dataset.overlayOnEnd   === "1";

        // Parse time ranges
        var timeRanges = [];
        try {
            timeRanges = JSON.parse( overlayEl.dataset.timeRanges || '[]' );
        } catch ( e ) {
            timeRanges = [];
        }


        var allZero = timeRanges.every( function ( r ) {
            return parseInt( r.start ) === 0 && parseInt( r.end ) === 0;
        } );
        var hasTimeRanges = ! allZero && timeRanges.length > 0;


        // Shared state — tracks whether each trigger wants overlay shown
        var showByPause     = false;
        var showByEnd       = false;
        var showByTimeRange = false;


        function updateOverlayVisibility() {
            if ( showByPause || showByEnd || showByTimeRange ) {
                overlayEl.style.display = "flex";
            } else {
                overlayEl.style.display = "none";
            }
        }


        // var startTime  = parseFloat( wrapper.dataset.overlayStart )   || 0;
        // var endTime    = parseFloat( wrapper.dataset.overlayEnd )     || 0;
        // var onPause    = wrapper.dataset.overlayOnPause === "1";
        // var onEnd      = wrapper.dataset.overlayOnEnd   === "1";



        // ── Pause and end screen triggers ─────────────────────────────────────
        if ( onPause || onEnd ) {
            var plyrInitObserver = new MutationObserver( function ( mutations, obs ) {
                var plyrEl = wrapper.querySelector( ".plyr" );
                if ( ! plyrEl ) {
                    return;
                }
                obs.disconnect();

                var stateObserver = new MutationObserver( function () {
                    var isPaused  = plyrEl.classList.contains( "plyr--paused" );
                    var isStopped = plyrEl.classList.contains( "plyr--stopped" );
                    var isEnded   = plyrEl.classList.contains( "plyr--ended" );

                    // showByPause = onPause && isPaused && ! isStopped;
                    showByPause = onPause && isPaused;
                    showByEnd   = onEnd   && isEnded;

                    updateOverlayVisibility();
                } );

                stateObserver.observe( plyrEl, {
                    attributes:      true,
                    attributeFilter: ["class"]
                } );
            } );

            plyrInitObserver.observe( wrapper, { childList: true, subtree: true } );
        }

        // ── Time-based triggers ───────────────────────────────────────────────
        if ( hasTimeRanges ) {
            setInterval( function () {
                var iframe = wrapper.querySelector( "iframe" );
                if ( ! iframe ) { return; }
                iframe.contentWindow.postMessage(
                    JSON.stringify( { event: "listening", id: 1 } ),
                    "*"
                );
            }, 500 );

            window.addEventListener( "message", function ( event ) {
                try {
                    var data = JSON.parse( event.data );
                    if ( data.event !== "infoDelivery" || ! data.info ) { return; }

                    var currentTime = data.info.currentTime;
                    if ( currentTime === undefined ) { return; }

                    showByTimeRange = timeRanges.some( function ( range ) {
                        var start = parseFloat( range.start ) || 0;
                        var end   = parseFloat( range.end )   || 0;
                        if ( start === 0 && end === 0 ) { return false; }
                        return currentTime >= start && ( end === 0 || currentTime <= end );
                    } );

                    updateOverlayVisibility();
                } catch ( e ) {}
            } );

        } else if ( ! onPause && ! onEnd ) {
            // No triggers at all — always show
            overlayEl.style.display = "flex";
        }

    } );

    // ── Move overlay into Plyr on fullscreen ──────────────────────────────
document.addEventListener('fullscreenchange', function () {

    document.querySelectorAll('.pvp-video-wrapper').forEach(function(wrapper) {

        var plyrEl   = wrapper.querySelector('.plyr');
        // var logoEl   = wrapper.querySelector('.pvp-logo');
        var logoImg   = wrapper.querySelector('.pvp-logo');
        var logoEl    = logoImg ? ( logoImg.closest('a') || logoImg ) : null;
        var overlayEl = wrapper.querySelector('.pvp-overlay-text');

        if (!plyrEl) {
            return;
        }

        // ENTER fullscreen
        if (document.fullscreenElement === plyrEl) {

            if (logoEl) {
                plyrEl.appendChild(logoEl);
            }

            if (overlayEl) {
                plyrEl.appendChild(overlayEl);
            }

        // EXIT fullscreen
        } else {

            if (logoEl) {
                wrapper.appendChild(logoEl);
            }

            if (overlayEl) {
                wrapper.appendChild(overlayEl);
            }
        }
    });
});

} );

