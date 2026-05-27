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

    // ── Campaign queue playback ─────────────────────────────────────────
    function initCampaign( campaign ) {
        var items = Array.prototype.slice.call( campaign.querySelectorAll( ".rsplr-campaign__item" ) );
        var queue = [];
        var currentIndex = 0;
        var stateObserver = null;
        var initObserver = null;
        var endedLock = false;

        if ( items.length < 1 || campaign.dataset.rsplrCampaignReady === "1" ) {
            return;
        }

        campaign.dataset.rsplrCampaignReady = "1";

        try {
            queue = JSON.parse( campaign.dataset.rsplrCampaignQueue || "[]" );
        } catch ( e ) {
            queue = [];
        }

        function getLabel( index ) {
            var item = queue[index] || {};
            var role = item.role || ( items[index] ? items[index].dataset.rsplrCampaignRole : "" );
            var title = item.title || "";
            var label = role ? role.charAt( 0 ).toUpperCase() + role.slice( 1 ) : "Video";

            return title ? label + ": " + title : label;
        }

        function stopObservers() {
            if ( stateObserver ) {
                stateObserver.disconnect();
                stateObserver = null;
            }

            if ( initObserver ) {
                initObserver.disconnect();
                initObserver = null;
            }
        }

        function tryPlay( item ) {
            var playButton = item.querySelector( ".plyr__control--overlaid, [data-plyr='play']" );
            var iframe = item.querySelector( "iframe" );

            if ( playButton ) {
                playButton.click();
                return;
            }

            if ( iframe && iframe.src && iframe.src.indexOf( "autoplay=1" ) === -1 ) {
                if ( iframe.src.indexOf( "autoplay=0" ) !== -1 ) {
                    iframe.src = iframe.src.replace( "autoplay=0", "autoplay=1" );
                } else {
                    iframe.src += iframe.src.indexOf( "?" ) === -1 ? "?autoplay=1" : "&autoplay=1";
                }
            }
        }

        function stopItem( item ) {
            var plyrEl = item.querySelector( ".plyr" );
            var playButton = item.querySelector( "[data-plyr='play']" );

            if ( plyrEl && playButton && plyrEl.classList.contains( "plyr--playing" ) ) {
                playButton.click();
            }
        }

        function updateControls() {
            var status = campaign.querySelector( ".rsplr-campaign__status" );
            var prev = campaign.querySelector( "[data-rsplr-campaign-prev]" );
            var next = campaign.querySelector( "[data-rsplr-campaign-next]" );

            if ( status ) {
                status.textContent = getLabel( currentIndex );
            }

            if ( prev ) {
                prev.disabled = currentIndex <= 0;
            }

            if ( next ) {
                next.disabled = currentIndex >= items.length - 1;
            }
        }

        function watchCurrentItem() {
            var item = items[currentIndex];

            stopObservers();

            function attach( plyrEl ) {
                if ( ! plyrEl ) {
                    return false;
                }

                stateObserver = new MutationObserver( function () {
                    if ( ! endedLock && plyrEl.classList.contains( "plyr--ended" ) ) {
                        endedLock = true;

                        if ( currentIndex < items.length - 1 ) {
                            activate( currentIndex + 1, true );
                        }
                    }
                } );

                stateObserver.observe( plyrEl, {
                    attributes: true,
                    attributeFilter: ["class"]
                } );

                return true;
            }

            if ( attach( item.querySelector( ".plyr" ) ) ) {
                return;
            }

            initObserver = new MutationObserver( function () {
                var plyrEl = item.querySelector( ".plyr" );

                if ( attach( plyrEl ) ) {
                    initObserver.disconnect();
                    initObserver = null;
                }
            } );

            initObserver.observe( item, { childList: true, subtree: true } );
        }

        function activate( index, autoplay ) {
            var previousItem;

            if ( index < 0 || index >= items.length ) {
                return;
            }

            previousItem = items[currentIndex];

            if ( previousItem && index !== currentIndex ) {
                stopItem( previousItem );
            }

            currentIndex = index;
            endedLock = false;
            campaign.dataset.rsplrCampaignCurrent = String( currentIndex );

            items.forEach( function ( item, itemIndex ) {
                var active = itemIndex === currentIndex;

                item.classList.toggle( "is-active", active );

                if ( active ) {
                    item.removeAttribute( "hidden" );
                } else {
                    item.setAttribute( "hidden", "hidden" );
                }
            } );

            updateControls();
            watchCurrentItem();

            if ( autoplay ) {
                window.setTimeout( function () {
                    tryPlay( items[currentIndex] );
                }, 300 );
            }
        }

        function addControls() {
            var controls;
            var status;
            var prev;
            var next;

            if ( items.length < 2 || campaign.querySelector( ".rsplr-campaign__controls" ) ) {
                return;
            }

            controls = document.createElement( "div" );
            controls.className = "rsplr-campaign__controls";

            status = document.createElement( "span" );
            status.className = "rsplr-campaign__status";

            prev = document.createElement( "button" );
            prev.type = "button";
            prev.className = "rsplr-campaign__button";
            prev.dataset.rsplrCampaignPrev = "1";
            prev.textContent = "Previous";

            next = document.createElement( "button" );
            next.type = "button";
            next.className = "rsplr-campaign__button";
            next.dataset.rsplrCampaignNext = "1";
            next.textContent = "Next";

            prev.addEventListener( "click", function () {
                activate( currentIndex - 1, false );
            } );

            next.addEventListener( "click", function () {
                activate( currentIndex + 1, false );
            } );

            controls.appendChild( status );
            controls.appendChild( prev );
            controls.appendChild( next );
            campaign.appendChild( controls );
        }

        addControls();
        activate( 0, false );
    }

    document.querySelectorAll( ".rsplr-campaign" ).forEach( initCampaign );

} );

